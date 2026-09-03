<?php

require_once __DIR__ . '/../models/Loan.php';

class ReportController
{
    private $loanModel;

    public function __construct()
    {
        $this->loanModel = new Loan();
    }

    /**
     * Laporan peminjaman & denda, dengan filter tanggal (from/to) dan status,
     * disertai ringkasan total yang dihitung dari baris yang sama supaya
     * angka ringkasan selalu konsisten dengan daftar yang ditampilkan.
     */
    public function index(): void
    {
        $from   = $_GET['from'] ?? null;
        $to     = $_GET['to'] ?? null;
        $status = $_GET['status'] ?? null;

        $rows = $this->loanModel->getReport($from, $to, $status);

        $summary = [
            'total_peminjaman' => count($rows),
            'total_dikembalikan' => 0,
            'total_terlambat' => 0,
            'total_denda' => 0,
        ];

        foreach ($rows as $row) {
            if ($row['status'] === 'dikembalikan') {
                $summary['total_dikembalikan']++;
            }
            if ($row['status'] === 'terlambat') {
                $summary['total_terlambat']++;
            }
            $summary['total_denda'] += (float) $row['denda_tampil'];
        }

        $this->respond(true, 'Laporan berhasil diambil', [
            'rows'    => $rows,
            'summary' => $summary,
        ]);
    }

    /** Admin menandai denda sebuah peminjaman sudah dibayar lunas */
    public function markLunas(int $id): void
    {
        $loan = $this->loanModel->getById($id);
        if (!$loan) {
            $this->respond(false, 'Data peminjaman tidak ditemukan', null, 404);
            return;
        }

        if ((float) $loan['denda'] <= 0) {
            $this->respond(false, 'Peminjaman ini tidak memiliki denda', null, 409);
            return;
        }

        $this->loanModel->markDendaLunas($id);
        $this->respond(true, 'Denda ditandai sudah lunas');
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
