<?php

/**
 * File ini HANYA jaga-jaga kalau ada yang mengakses root proyek langsung
 * (mis. http://localhost/SILAPUS1/). Entry point sebenarnya
 * ada di public/index.php - front controller aplikasi.
 */
require_once __DIR__ . '/config/Database.php';

require_once  __DIR__ . '/public/index.php';
exit;
