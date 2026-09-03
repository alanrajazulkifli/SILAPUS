<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../app/controller/LoanController.php';
require_once __DIR__ . '/../config/Auth.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $controller = new LoanController();

    switch ($method) {
        case 'GET':
            $payload = Auth::check();
            if ($payload['role'] === 'admin' && !isset($_GET['mine'])) {
                // Admin melihat semua peminjaman
                $controller->index();
            } else {
                // Siswa hanya melihat peminjaman miliknya sendiri
                $controller->myLoans((int) $payload['id']);
            }
            break;

        case 'POST':
            // Siswa mengajukan peminjaman
            $payload = Auth::checkRole('siswa');
            $controller->store((int) $payload['id'], $data);
            break;

        case 'PUT':
            // Admin menandai buku sudah dikembalikan
            Auth::checkRole('admin');
            if (!$id) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID peminjaman wajib disertakan']);
                break;
            }
            $controller->markReturned($id);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()]);
}
