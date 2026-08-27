<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Models/BukuModel.php';

class BukuController{
  private $buku;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->buku = new Buku($db);
    }   
}
?>