<?php

/**
 * File ini HANYA jaga-jaga kalau ada yang mengakses root proyek langsung
 * (mis. http://localhost/perpus-kelompok-4/). Entry point sebenarnya
 * ada di public/index.php - front controller aplikasi.
 */

header('Location: public/index.php');
exit;
