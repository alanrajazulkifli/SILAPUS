<?php

require_once __DIR__ . '/../models/Loan.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../../config/db.php';

class LoanController
{
    private $loanModel;
    private $BukuModel;

    public function __construct()
    {
        $this->loanModel = new Loan();
        $this->BukuModel = new Buku();
    }

    /** Semua peminjaman (khusus admin) */
    public function index(): void
    {
        $this->loanModel->updateOverdueStatuses();

        $status = $_GET['status'] ?? null;
        $loans = $this->loanModel->getAll($status);

        $this->respond(true, 'Daftar peminjaman berhasil diambil', $loans);
    }


    public function myLoans(int $userId): void
    {
        $this->loanModel->updateOverdueStatuses();

        $status = $_GET['status'] ?? null;
        $loans = $this->loanModel->getByUser($userId, $status);

        $this->respond(true, 'Riwayat peminjaman berhasil diambil', $loans);
    }


    public function store(int $userId, array $data): void
    {
        $BukuId = (int) ($data['buku_id'] ?? 0);

        if (!$BukuId) {
            $this->respond(false, 'Buku wajib dipilih', null, 422);
            return;
        }

        $buku = $this->BukuModel->getById($BukuId);
        if (!$buku) {
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


            $lockedBuku = $this->BukuModel->getByIdForUpdate($BukuId);

            if (!$lockedBuku || (int) $lockedBuku['stok'] <= 0) {
                $db->rollBack();
                $this->respond(false, 'Stok buku habis', null, 409);
                return;
            }

            $id = $this->loanModel->create($userId, $BukuId);

            if (!$this->BukuModel->decreaseStock($BukuId)) {
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
            $this->BukuModel->increaseStock($loan['buku_id']);
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
