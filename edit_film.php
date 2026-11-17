<?php
require "auth.php";
require "database.php";
require_admin();

function ke_detik($waktu) {
  $bagian = explode(':', $waktu);
  $detik = $bagian[0] * 3600 + $bagian[1] * 60;
  if (isset($bagian[2])) $detik += $bagian[2];
  return $detik;
}

function hitung_jam_selesai($mulai, $durasi) {
  return gmdate('H:i:s', (ke_detik($mulai) + ke_detik($durasi)) % 86400);
}

$ok = "";
$err = "";

// ==== UPDATE FILM ====
if (isset($_POST['update_film'])) {
  $id_film  = trim($_POST['id_film']);
  $id_studio = trim($_POST['id_studio_ref']);
  $judul    = trim($_POST['judul']);
  $genre    = trim($_POST['genre']);
  $durasi   = trim($_POST['durasi']);
  $rating   = (int)$_POST['rating'];
  $harga    = (int)$_POST['harga'];
  $sinopsis = trim($_POST['sinopsis']);
  $mulai    = trim($_POST['mulai_tayang']);
  $selesai  = trim($_POST['selesai_tayang']);

  if ($id_film == '' || $id_studio == '' || $judul == '' || $durasi == '' || $mulai == '' || $selesai == '' || $harga <= 0) {
    $err = "Lengkapi field wajib film (termasuk harga).";
  } else {
    $stmt = $db->prepare("UPDATE film SET id_studio = ?, judul = ?, genre = ?, durasi = ?, rating = ?, harga = ?, sinopsis = ?, mulai_tayang = ?, selesai_tayang = ? WHERE id_film = ?");
    $stmt->bind_param("ssssiissss", $id_studio, $judul, $genre, $durasi, $rating, $harga, $sinopsis, $mulai, $selesai, $id_film);

    if ($stmt->execute()) {
      $stmt->close();

      // recalculate jam_selesai semua jadwal film ini
      $stmt = $db->prepare("SELECT id_jadwal, jam_mulai FROM jadwal_tayang WHERE id_film = ?");
      $stmt->bind_param("s", $id_film);
      $stmt->execute();
      $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      $stmt->close();

      foreach ($rows as $r) {
        $js = hitung_jam_selesai($r['jam_mulai'], $durasi);
        $stmt = $db->prepare("UPDATE jadwal_tayang SET jam_selesai = ? WHERE id_jadwal = ?");
        $stmt->bind_param("ss", $js, $r['id_jadwal']);
        $stmt->execute();
        $stmt->close();
      }

      $ok = "Film berhasil diupdate.";
    } else {
      $stmt->close();
      $err = "Gagal mengupdate film.";
    }
  }
}

// ambil data film
if (empty($_GET['id']) && empty($_POST['id_film'])) {
  header("Location: admin.php");
  exit;
}
$film_id = !empty($_POST['id_film']) ? trim($_POST['id_film']) : trim($_GET['id']);
$stmt = $db->prepare("SELECT * FROM film WHERE id_film = ?");
$stmt->bind_param("s", $film_id);
$stmt->execute();
$film = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$film) {
  header("Location: admin.php");
  exit;
}

$studios = $db->query("SELECT id_studio, nama_studio FROM studio ORDER BY nama_studio")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Edit Film - <?= h($film['judul']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
  <?php include "navbar.php"; ?>

  <main class="max-w-2xl mx-auto px-4 py-8">
    <div class="flex items-center gap-3 mb-6">
      <a href="index.php" class="text-sm text-gray-500 hover:text-gray-700">← Halaman User</a>
      <span class="text-gray-300">/</span>
      <h1 class="text-2xl font-bold">Edit Film</h1>
    </div>

    <?php if ($ok != ""): ?>
      <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= h($ok) ?></div>
    <?php endif; ?>
    <?php if ($err != ""): ?>
      <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= h($err) ?></div>
    <?php endif; ?>

    <section class="rounded-2xl border bg-white p-6">
      <form method="post" class="space-y-4">
        <input type="hidden" name="update_film" value="1">
        <input type="hidden" name="id_film" value="<?= h($film['id_film']) ?>">

        <div>
          <label class="text-sm font-medium">ID Film</label>
          <input class="w-full border rounded-xl px-3 py-2 bg-gray-100 mt-1" value="<?= h($film['id_film']) ?>" readonly>
        </div>

        <div>
          <label class="text-sm font-medium">Studio</label>
          <select name="id_studio_ref" class="w-full border rounded-xl px-3 py-2 mt-1" required>
            <?php foreach ($studios as $s): ?>
              <option value="<?= h($s['id_studio']) ?>" <?= $s['id_studio'] == $film['id_studio'] ? 'selected' : '' ?>>
                <?= h($s['id_studio'] . ' - ' . $s['nama_studio']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="text-sm font-medium">Judul</label>
          <input name="judul" class="w-full border rounded-xl px-3 py-2 mt-1" value="<?= h($film['judul']) ?>" required>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-medium">Genre</label>
            <input name="genre" class="w-full border rounded-xl px-3 py-2 mt-1" value="<?= h($film['genre']) ?>">
          </div>
          <div>
            <label class="text-sm font-medium">Durasi (HH:MM:SS)</label>
            <input name="durasi" class="w-full border rounded-xl px-3 py-2 mt-1" value="<?= h($film['durasi']) ?>" required>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-medium">Rating Umur</label>
            <input name="rating" type="number" min="0" class="w-full border rounded-xl px-3 py-2 mt-1" value="<?= h($film['rating']) ?>">
          </div>
          <div>
            <label class="text-sm font-medium">Harga (Rupiah)</label>
            <input name="harga" type="number" min="0" class="w-full border rounded-xl px-3 py-2 mt-1" value="<?= h($film['harga']) ?>" required>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-medium">Mulai Tayang</label>
            <input name="mulai_tayang" type="date" class="w-full border rounded-xl px-3 py-2 mt-1" value="<?= h($film['mulai_tayang']) ?>" required>
          </div>
          <div>
            <label class="text-sm font-medium">Selesai Tayang</label>
            <input name="selesai_tayang" type="date" class="w-full border rounded-xl px-3 py-2 mt-1" value="<?= h($film['selesai_tayang']) ?>" required>
          </div>
        </div>

        <div>
          <label class="text-sm font-medium">Sinopsis</label>
          <textarea name="sinopsis" rows="4" class="w-full border rounded-xl px-3 py-2 mt-1"><?= h($film['sinopsis']) ?></textarea>
        </div>

        <div class="flex gap-3 pt-2">
          <button class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-xl">Simpan Perubahan</button>
          <a href="index.php" class="px-5 py-2 rounded-xl border text-gray-600 hover:bg-gray-100">Batal</a>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
