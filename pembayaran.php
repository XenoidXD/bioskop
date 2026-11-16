<?php
require "auth.php";
require "database.php";
require_login();

$id_pemesanan = "";
if (isset($_GET['id_pemesanan'])) {
  $id_pemesanan = $_GET['id_pemesanan'];
}
if (isset($_POST['id_pemesanan'])) {
  $id_pemesanan = $_POST['id_pemesanan'];
}
if ($id_pemesanan == "") {
  header("Location: index.php");
  exit;
}

// pastikan pesanan milik user yang login
$stmt = $db->prepare("SELECT pe.nama, pe.email FROM pemesanan p
  JOIN penonton pe ON pe.id_penonton = p.id_penonton
  WHERE p.id_pemesanan = ? AND p.id_penonton = ?");
$stmt->bind_param("si", $id_pemesanan, $_SESSION['user']['id']);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pesanan) {
  header("Location: index.php");
  exit;
}

// info film dan jadwal untuk ditampilkan
$stmt = $db->prepare("SELECT j.tanggal, j.jam_mulai, j.jam_selesai, f.judul, s.nama_studio
  FROM tiket t
  JOIN jadwal_tayang j ON j.id_jadwal = t.id_jadwal
  JOIN film f ON f.id_film = j.id_film
  JOIN studio s ON s.id_studio = f.id_studio
  WHERE t.id_pemesanan = ?
  LIMIT 1");
$stmt->bind_param("s", $id_pemesanan);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// total bayar = jumlah harga semua tiket
$stmt = $db->prepare("SELECT COALESCE(SUM(harga), 0) AS total FROM tiket WHERE id_pemesanan = ?");
$stmt->bind_param("s", $id_pemesanan);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// daftar metode pembayaran (sesuai ENUM di database)
$metode_list = ['Gopay', 'OVO', 'Shopee-Pay', 'Bank Transfer', 'QRIS'];

$ok = "";
$err = "";

if (isset($_POST['bayar'])) {
  $metode = $_POST['metode_bayar'];

  if (!in_array($metode, $metode_list)) {
    $err = "Metode bayar tidak valid.";
  } elseif ($total <= 0) {
    $err = "Total bayar tidak valid.";
  } else {
    // id pembayaran: 'PB' + 6 digit angka
    $id_bayar = 'PB' . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $status = 'SUKSES'; // untuk demo

    $stmt = $db->prepare("INSERT INTO pembayaran (id_pembayaran, id_pemesanan, metode_bayar, total_bayar, status_bayar)
      VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $id_bayar, $id_pemesanan, $metode, $total, $status);

    if ($stmt->execute()) {
      $ok = "Pembayaran berhasil. ID Pembayaran: " . $id_bayar;
    } else {
      $err = "Gagal menyimpan pembayaran.";
    }
    $stmt->close();
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pembayaran - Bioskop</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
  <?php include "navbar.php"; ?>

  <main class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Pembayaran</h1>

    <?php if ($ok != ""): ?>
      <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= h($ok) ?></div>
    <?php endif; ?>
    <?php if ($err != ""): ?>
      <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= h($err) ?></div>
    <?php endif; ?>

    <div class="rounded-2xl border bg-white p-6 space-y-3 mb-6">
      <div class="flex justify-between"><span>ID Pemesanan</span><span class="font-medium"><?= h($id_pemesanan) ?></span></div>
      <div class="flex justify-between"><span>Nama</span><span><?= h($pesanan['nama'] ?? $pesanan['email']) ?></span></div>
      <?php if ($info): ?>
        <div class="flex justify-between"><span>Film</span><span><?= h($info['judul']) ?></span></div>
        <div class="flex justify-between"><span>Studio</span><span><?= h($info['nama_studio']) ?></span></div>
        <div class="flex justify-between"><span>Tanggal</span><span><?= h($info['tanggal']) ?></span></div>
        <div class="flex justify-between"><span>Jam</span><span><?= h(substr($info['jam_mulai'], 0, 5)) ?> - <?= h(substr($info['jam_selesai'], 0, 5)) ?></span></div>
      <?php endif; ?>
      <div class="flex justify-between text-lg font-semibold"><span>Total Bayar</span><span>Rp <?= number_format($total, 0, ',', '.') ?></span></div>
    </div>

    <form method="post" class="rounded-2xl border bg-white p-6 space-y-4">
      <input type="hidden" name="id_pemesanan" value="<?= h($id_pemesanan) ?>">

      <div>
        <label class="text-sm font-medium">Metode Pembayaran</label>
        <select name="metode_bayar" class="mt-1 w-full border rounded-xl px-3 py-2" required>
          <option value="">-- pilih --</option>
          <?php foreach ($metode_list as $m): ?>
            <option><?= h($m) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button name="bayar" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-xl">Bayar</button>
    </form>

    <div class="mt-6 flex gap-2">
      <a href="pesanan_saya.php" class="bg-white border text-gray-700 px-4 py-2 rounded-xl">Lihat Pesanan Saya</a>
      <a href="index.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl">Beranda</a>
    </div>
  </main>
</body>
</html>
