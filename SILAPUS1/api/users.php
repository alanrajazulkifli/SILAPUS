<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../app/controller/UserController.php';
require_once __DIR__ . '/../config/Auth.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    // Semua endpoint anggota khusus admin
    $payload = Auth::checkRole('admin');

    $controller = new UserController();

    switch ($method) {
        case 'GET':
            $controller->index();
            break;

        case 'POST':
            $controller->store($data);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID anggota wajib disertakan']);
                break;
            }
            $controller->update($id, $data);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID anggota wajib disertakan']);
                break;
            }
            $controller->destroy($id, (int) $payload['id']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()]);
}
