<?php
require "auth.php";
require "database.php";
require_admin();

function hapus_data($db, $sql, $id, $nama) {
  try {
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $terhapus = $stmt->affected_rows;
    $stmt->close();
    if ($terhapus > 0) {
      return [$nama . " dihapus.", ""];
    } else {
      return ["", $nama . " tidak ditemukan."];
    }
  } catch (mysqli_sql_exception $e) {
    return ["", "Gagal menghapus " . strtolower($nama) . ": masih dipakai data lain(film/jadwal/tiket). Hapus data terkait dulu."];
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_film'])) {
  $id = trim($_POST['id_film']);
  [$ok, $err] = hapus_data($db, "DELETE FROM film WHERE id_film = ?", $id, "Film");
  if ($ok) {
    header("Location: index.php?ok=" . urlencode($ok));
  } else {
    header("Location: index.php?err=" . urlencode($err));
  }
  exit;
}

header("Location: index.php");
exit;
