<?php
// Konfigurasi Database
define('DB_SERVER', 'localhost'); // Sesuaikan jika server database Anda berbeda
define('DB_USERNAME', 'root');   // Ganti dengan username database Anda
define('DB_PASSWORD', '');       // Ganti dengan password database Anda
define('DB_NAME', 'rocsawdss');  // NAMA DATABASE ANDA (sesuai permintaan Anda)

// Buat koneksi ke database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Set charset untuk koneksi
$conn->set_charset("utf8");

// Setel laporan kesalahan untuk debugging (HANYA UNTUK PENGEMBANGAN!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>