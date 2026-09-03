<?php

require_once __DIR__ . '/../../config/Database.php';

class Loan
{
    private $db;
    const MAX_LOAN_PER_USER = 3;
    const LOAN_DURATION_DAYS = 14;
    const DENDA_PER_HARI = 1000; // Rp 1.000 / hari keterlambatan

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function baseQuery(): string
    {
        return 'SELECT loans.*, users.nama AS nama_siswa, books.judul, books.isbn
                FROM loans
                JOIN users ON loans.user_id = users.id
                JOIN books ON loans.book_id = books.id';
    }

    public function getAll(?string $status = null): array
    {
        $sql = $this->baseQuery();
        $params = [];

        if ($status) {
            $sql .= ' WHERE loans.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY loans.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getByUser(int $userId, ?string $status = null): array
    {
        $sql = $this->baseQuery() . ' WHERE loans.user_id = :user_id';
        $params = ['user_id' => $userId];

        if ($status) {
            $sql .= ' AND loans.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY loans.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countActiveByUser(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM loans WHERE user_id = :user_id AND status IN ('dipinjam', 'terlambat')"
        );
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetch()['total'];
    }

    public function getMaxLoanPerUser(): int
    {
        return self::MAX_LOAN_PER_USER;
    }

    public function create(int $userId, int $bookId): int
    {
        $tanggalPinjam = date('Y-m-d');
        $batasKembali = date('Y-m-d', strtotime('+' . self::LOAN_DURATION_DAYS . ' days'));

        $stmt = $this->db->prepare(
            'INSERT INTO loans (user_id, book_id, tanggal_pinjam, batas_kembali, status)
             VALUES (:user_id, :book_id, :tanggal_pinjam, :batas_kembali, "dipinjam")'
        );
        $stmt->execute([
            'user_id'        => $userId,
            'book_id'        => $bookId,
            'tanggal_pinjam' => $tanggalPinjam,
            'batas_kembali'  => $batasKembali,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array|false
     */
    public function getById(int $id)
    {
        $stmt = $this->db->prepare($this->baseQuery() . ' WHERE loans.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function markReturned(int $id): bool
    {
        $loan = $this->getById($id);
        $tanggalKembali = date('Y-m-d');
        $denda = 0;

        if ($loan) {
            $hariTerlambat = $this->hitungHariTerlambat($loan['batas_kembali'], $tanggalKembali);
            $denda = $hariTerlambat * self::DENDA_PER_HARI;
        }

        $stmt = $this->db->prepare(
            'UPDATE loans SET status = "dikembalikan", tanggal_kembali = :tanggal_kembali, denda = :denda WHERE id = :id'
        );
        return $stmt->execute([
            'tanggal_kembali' => $tanggalKembali,
            'denda'           => $denda,
            'id'              => $id,
        ]);
    }

    /**
     * Menandai denda dari sebuah peminjaman sebagai sudah dibayar.
     */
    public function markDendaLunas(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE loans SET denda_lunas = 1 WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Selisih hari antara batas_kembali dan tanggal_kembali (dibulatkan ke bawah,
     * tidak pernah negatif - kalau kembali tepat waktu atau lebih cepat, hasilnya 0).
     */
    private function hitungHariTerlambat(string $batasKembali, string $tanggalKembali): int
    {
        $batas = new DateTime($batasKembali);
        $kembali = new DateTime($tanggalKembali);

        if ($kembali <= $batas) {
            return 0;
        }

        return (int) $batas->diff($kembali)->days;
    }

    /**
     * Data laporan peminjaman untuk halaman Laporan/Denda, dengan filter
     * rentang tanggal pinjam dan status. Untuk peminjaman yang masih
     * berstatus 'terlambat' (belum dikembalikan), denda dihitung secara
     * live (estimasi berjalan) karena kolom `denda` baru terisi permanen
     * saat buku benar-benar dikembalikan (lihat markReturned()).
     */
    public function getReport(?string $from = null, ?string $to = null, ?string $status = null): array
    {
        $this->updateOverdueStatuses();

        $sql = 'SELECT loans.*, users.nama AS nama_siswa, books.judul, books.isbn,
                CASE
                    WHEN loans.status = "dikembalikan" THEN loans.denda
                    WHEN loans.status = "terlambat" THEN DATEDIFF(CURDATE(), loans.batas_kembali) * ' . self::DENDA_PER_HARI . '
                    ELSE 0
                END AS denda_tampil,
                CASE
                    WHEN loans.status = "dikembalikan" THEN GREATEST(DATEDIFF(loans.tanggal_kembali, loans.batas_kembali), 0)
                    WHEN loans.status = "terlambat" THEN GREATEST(DATEDIFF(CURDATE(), loans.batas_kembali), 0)
                    ELSE 0
                END AS hari_terlambat_tampil
                FROM loans
                JOIN users ON loans.user_id = users.id
                JOIN books ON loans.book_id = books.id';

        $conditions = [];
        $params = [];

        if ($from) {
            $conditions[] = 'loans.tanggal_pinjam >= :from';
            $params['from'] = $from;
        }
        if ($to) {
            $conditions[] = 'loans.tanggal_pinjam <= :to';
            $params['to'] = $to;
        }
        if ($status) {
            $conditions[] = 'loans.status = :status';
            $params['status'] = $status;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY loans.batas_kembali DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Menandai peminjaman yang lewat batas_kembali sebagai 'terlambat'.
     * Sebaiknya dipanggil sebelum menampilkan data peminjaman.
     */
    public function updateOverdueStatuses(): void
    {
        $stmt = $this->db->prepare(
            "UPDATE loans SET status = 'terlambat'
             WHERE status = 'dipinjam' AND batas_kembali < :today"
        );
        $stmt->execute(['today' => date('Y-m-d')]);
    }
}
