<?php
require "auth.php";
require "database.php";
require_login();

$id_user = $_SESSION['user']['id'];
$message = "";
$success = "";

// ambil data user saat ini
$stmt = $db->prepare("SELECT email, nama, nomor_hp FROM penonton WHERE id_penonton = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current) {
  header("Location: logout.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = trim($_POST['email']);
  $nama = trim($_POST['nama']);
  $nohp = trim($_POST['nomor_hp']);
  $password = $_POST['password'];

  // kalau dikosongkan, pakai data lama
  if ($email == '') { $email = $current['email']; }
  if ($nama == '')  { $nama = $current['nama']; }
  if ($nohp == '')  { $nohp = $current['nomor_hp']; }

  // cek apakah ada yang berubah
  $berubah = false;
  if ($email != $current['email'])   { $berubah = true; }
  if ($nama != $current['nama'])     { $berubah = true; }
  if ($nohp != $current['nomor_hp']) { $berubah = true; }
  if ($password != '')               { $berubah = true; }

  if (!$berubah) {
    $message = "Tidak ada perubahan data.";
  } else {
    // kalau email diganti, pastikan belum dipakai orang lain
    if ($email != $current['email']) {
      $stmt = $db->prepare("SELECT id_penonton FROM penonton WHERE email = ? AND id_penonton <> ?");
      $stmt->bind_param("si", $email, $id_user);
      $stmt->execute();
      if ($stmt->get_result()->num_rows > 0) {
        $message = "Email sudah digunakan pengguna lain.";
      }
      $stmt->close();
    }

    if ($message == "") {
      // password hanya diubah kalau diisi
      if ($password != '') {
        $stmt = $db->prepare("UPDATE penonton SET email = ?, nama = ?, nomor_hp = ?, password = ? WHERE id_penonton = ?");
        $stmt->bind_param("ssssi", $email, $nama, $nohp, $password, $id_user);
      } else {
        $stmt = $db->prepare("UPDATE penonton SET email = ?, nama = ?, nomor_hp = ? WHERE id_penonton = ?");
        $stmt->bind_param("sssi", $email, $nama, $nohp, $id_user);
      }

      if ($stmt->execute()) {
        $success = "Profil berhasil diperbarui.";

        // perbarui session supaya navbar ikut berubah
        $_SESSION['user']['email'] = $email;
        if ($nama == '') {
          $_SESSION['user']['nama'] = $email;
        } else {
          $_SESSION['user']['nama'] = $nama;
        }

        // perbarui isi form
        $current['email'] = $email;
        $current['nama'] = $nama;
        $current['nomor_hp'] = $nohp;
      } else {
        $message = "Gagal memperbarui profil.";
      }
      $stmt->close();
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Profil - Bioskop</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
  <?php include "navbar.php"; ?>

  <main class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl md:text-3xl font-bold">Profil Saya</h1>
    <p class="text-gray-600 mt-1 mb-6">Ubah informasi akunmu di sini.</p>

    <?php if ($success != ""): ?>
      <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= h($success) ?></div>
    <?php endif; ?>
    <?php if ($message != ""): ?>
      <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= h($message) ?></div>
    <?php endif; ?>

    <div class="rounded-2xl border bg-white shadow-sm p-6 max-w-xl">
      <form method="post" class="space-y-4" autocomplete="off">
        <div class="space-y-1.5">
          <label class="text-sm font-medium" for="email">Email</label>
          <input id="email" name="email" type="email" required value="<?= h($current['email']) ?>"
                 class="w-full rounded-xl border px-3 py-2">
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium" for="nama">Nama Lengkap</label>
          <input id="nama" name="nama" type="text" required value="<?= h($current['nama']) ?>"
                 class="w-full rounded-xl border px-3 py-2">
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium" for="nomor_hp">Nomor HP</label>
          <input id="nomor_hp" name="nomor_hp" type="tel" pattern="[0-9]{8,15}" required
                 placeholder="08xxxxxxxxxx" value="<?= h($current['nomor_hp']) ?>"
                 class="w-full rounded-xl border px-3 py-2">
          <p class="text-xs text-gray-500">Angka saja, 8-15 digit.</p>
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium" for="password">Password Baru (opsional)</label>
          <input id="password" name="password" type="password" minlength="6"
                 placeholder="Kosongkan jika tidak ingin mengubah"
                 class="w-full rounded-xl border px-3 py-2">
          <p class="text-xs text-gray-500">Isi hanya jika ingin mengganti password.</p>
        </div>

        <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2.5 rounded-xl">
          Simpan Perubahan
        </button>
      </form>
    </div>
  </main>
</body>
</html>
