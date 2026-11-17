<?php
require "auth.php";
require "database.php";
require_admin();

function hapus_data($db, $sql, $nama) {
  try {
    mysqli_query($db, $sql);
    $terhapus = mysqli_affected_rows($db);
    if ($terhapus > 0) {
      return [$nama . " dihapus.", ""];
    } else {
      return ["", $nama . " tidak ditemukan."];
    }
  } catch (mysqli_sql_exception $e) {
    return ["", "Gagal menghapus " . strtolower($nama) . ": masih dipakai data lain (jadwal/tiket). Hapus data terkait dulu."];
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_film'])) {
  $id = esc(trim($_POST['id_film']));
  [$ok, $err] = hapus_data($db, "DELETE FROM film WHERE id_film = '$id'", "Film");
  // redirect kembali ke index dengan pesan via query string
  if ($ok) {
    header("Location: index.php?ok=" . urlencode($ok));
  } else {
    header("Location: index.php?err=" . urlencode($err));
  }
  exit;
}

header("Location: index.php");
exit;
