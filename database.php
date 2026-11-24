<?php
$db = mysqli_connect("localhost", "root", "", "database_bioskop");

if (!$db) {
  die("Koneksi ke database gagal");
}

// bersihkan teks sebelum dimasukkan ke query (cegah SQL injection)
function esc($teks) {
  global $db;
  return mysqli_real_escape_string($db, (string)$teks);
}
?>