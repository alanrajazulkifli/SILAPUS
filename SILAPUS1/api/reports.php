<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../app/controller/ReportController.php';
require_once __DIR__ . '/../config/Auth.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
    $action = $_GET['action'] ?? null;

    // Laporan & denda khusus admin
    Auth::checkRole('admin');

    $controller = new ReportController();

    switch ($method) {
        case 'GET':
            $controller->index();
            break;

        case 'PUT':
            if (!$id || $action !== 'lunas') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid']);
                break;
            }
            $controller->markLunas($id);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()]);
}
