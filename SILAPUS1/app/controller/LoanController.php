<?php

require_once __DIR__ . '/../models/Loan.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../../config/Database.php';

class LoanController
{
    private $loanModel;
    private $bookModel;

    public function __construct()
    {
        $this->loanModel = new Loan();
        $this->bookModel = new Book();
    }

    /** Semua peminjaman (khusus admin) */
    public function index(): void
    {
        $this->loanModel->updateOverdueStatuses();

        $status = $_GET['status'] ?? null;
        $loans = $this->loanModel->getAll($status);

        $this->respond(true, 'Daftar peminjaman berhasil diambil', $loans);
    }

    /** Peminjaman milik user yang sedang login (siswa) */
    public function myLoans(int $userId): void
    {
        $this->loanModel->updateOverdueStatuses();

        $status = $_GET['status'] ?? null;
        $loans = $this->loanModel->getByUser($userId, $status);

        $this->respond(true, 'Riwayat peminjaman berhasil diambil', $loans);
    }

    /** Siswa mengajukan pinjam buku */
    public function store(int $userId, array $data): void
    {
        $bookId = (int) ($data['book_id'] ?? 0);

        if (!$bookId) {
            $this->respond(false, 'Buku wajib dipilih', null, 422);
            return;
        }

        $book = $this->bookModel->getById($bookId);
        if (!$book) {
            $this->respond(false, 'Buku tidak ditemukan', null, 404);
            return;
        }

        $activeCount = $this->loanModel->countActiveByUser($userId);
        $maxLoan = $this->loanModel->getMaxLoanPerUser();

        if ($activeCount >= $maxLoan) {
            $this->respond(false, "Kamu sudah mencapai batas maksimal $maxLoan buku dipinjam", null, 409);
            return;
        }

        $db = Database::getConnection();

        try {
            $db->beginTransaction();

            // Kunci baris buku ini supaya tidak ada peminjaman lain yang
            // mengurangi stok buku yang sama secara bersamaan (race condition).
            $lockedBook = $this->bookModel->getByIdForUpdate($bookId);

            if (!$lockedBook || (int) $lockedBook['stok'] <= 0) {
                $db->rollBack();
                $this->respond(false, 'Stok buku habis', null, 409);
                return;
            }

            $id = $this->loanModel->create($userId, $bookId);

            if (!$this->bookModel->decreaseStock($bookId)) {
                $db->rollBack();
                $this->respond(false, 'Stok buku habis', null, 409);
                return;
            }

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->respond(false, 'Terjadi kesalahan saat memproses peminjaman', null, 500);
            return;
        }

        $this->respond(true, 'Peminjaman berhasil diajukan', ['id' => $id], 201);
    }

    /** Admin menandai buku sudah dikembalikan */
    public function markReturned(int $id): void
    {
        $loan = $this->loanModel->getById($id);

        if (!$loan) {
            $this->respond(false, 'Data peminjaman tidak ditemukan', null, 404);
            return;
        }

        if ($loan['status'] === 'dikembalikan') {
            $this->respond(false, 'Buku ini sudah ditandai dikembalikan', null, 409);
            return;
        }

        $db = Database::getConnection();

        try {
            $db->beginTransaction();
            $this->loanModel->markReturned($id);
            $this->bookModel->increaseStock($loan['book_id']);
            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->respond(false, 'Terjadi kesalahan saat memproses pengembalian', null, 500);
            return;
        }

        $this->respond(true, 'Buku berhasil ditandai sebagai dikembalikan');
    }

    private function respond(bool $success, string $message, $data = null, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ]);
    }
}
