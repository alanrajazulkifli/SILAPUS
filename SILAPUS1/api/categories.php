<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../app/controller/CategoryController.php';
require_once __DIR__ . '/../config/Auth.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $controller = new CategoryController();

    switch ($method) {
        case 'GET':
            Auth::check();
            $controller->index();
            break;

        case 'POST':
            Auth::checkRole('admin');
            $controller->store($data);
            break;

        case 'PUT':
            Auth::checkRole('admin');
            if (!$id) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID kategori wajib disertakan']);
                break;
            }
            $controller->update($id, $data);
            break;

        case 'DELETE':
            Auth::checkRole('admin');
            if (!$id) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID kategori wajib disertakan']);
                break;
            }
            $controller->destroy($id);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()]);
}
