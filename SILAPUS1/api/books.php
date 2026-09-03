<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../app/controller/BookController.php';
require_once __DIR__ . '/../config/Auth.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $controller = new BookController();

    switch ($method) {
        case 'GET':
            // Melihat daftar/detail buku boleh diakses siswa & admin yang sudah login
            Auth::check();
            $id ? $controller->show($id) : $controller->index();
            break;

        case 'POST':
            // Hanya admin yang boleh menambah buku
            Auth::checkRole('admin');
            $controller->store($data);
            break;

        case 'PUT':
            Auth::checkRole('admin');
            if (!$id) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID buku wajib disertakan']);
                break;
            }
            $controller->update($id, $data);
            break;

        case 'DELETE':
            Auth::checkRole('admin');
            if (!$id) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID buku wajib disertakan']);
                break;
            }
            $controller->destroy($id);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    }
} catch (\Throwable $e) {
    // Pengaman terakhir: apapun error yang lolos dari controller/model,
    // tetap keluar sebagai JSON, bukan HTML/teks error mentah yang
    // merusak parsing di frontend.
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()]);
}
