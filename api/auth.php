<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../app/controller/AuthController.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $controller = new AuthController();

    if ($method === 'POST' && $action === 'login') {
        $controller->login($data);
    } elseif ($method === 'POST' && $action === 'register') {
        $controller->register($data);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint tidak ditemukan']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()]);
}
