<?php
require "auth.php";
require "database.php";
require_login();

// buat id tiket unik (14 karakter), dicek dulu ke database
function buat_id_tiket($db) {
  do {
    $id = 'TK' . strtoupper(bin2hex(random_bytes(4)));
    $cek = mysqli_query($db, "SELECT id_tiket FROM tiket WHERE id_tiket = '$id'");
  } while (mysqli_num_rows($cek) > 0);
  return $id;
}

// halaman ini hanya menerima data dari form pilih kursi
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  header("Location: index.php");
  exit;
}

$id_pemesanan = "";
$id_jadwal = "";
$kursi_dipilih = [];
if (isset($_POST['id_pemesanan'])) { $id_pemesanan = $_POST['id_pemesanan']; }
if (isset($_POST['id_jadwal']))    { $id_jadwal = $_POST['id_jadwal']; }
if (isset($_POST['nomor_kursi']))  { $kursi_dipilih = $_POST['nomor_kursi']; }

if ($id_pemesanan == "" || $id_jadwal == "" || count($kursi_dipilih) == 0) {
  header("Location: index.php");
  exit;
}

$id_user = (int)$_SESSION['user']['id'];
$id_pemesanan_aman = esc($id_pemesanan);
$id_jadwal_aman = esc($id_jadwal);

// ambil data pemesanan milik user, lengkap dengan jadwal, film, dan harga
$hasil = mysqli_query($db, "SELECT p.jumlah_tiket, j.tanggal, j.jam_mulai, j.jam_selesai,
    f.judul, f.harga, f.id_studio, s.nama_studio
  FROM pemesanan p
  JOIN jadwal_tayang j ON j.id_jadwal = '$id_jadwal_aman'
  JOIN film f ON f.id_film = j.id_film
  JOIN studio s ON s.id_studio = f.id_studio
  WHERE p.id_pemesanan = '$id_pemesanan_aman' AND p.id_penonton = $id_user");
$data = mysqli_fetch_assoc($hasil);

if (!$data) {
  header("Location: index.php");
  exit;
}

$kuota = (int)$data['jumlah_tiket'];
$harga = (int)$data['harga'];
$url_kursi = "pilih_kursi.php?id_pemesanan=$id_pemesanan&id_jadwal=$id_jadwal&id_studio=" . $data['id_studio'];

// jumlah kursi yang dipilih harus sama dengan jumlah tiket
if (count($kursi_dipilih) != $kuota) {
  header("Location: " . $url_kursi);
  exit;
}

// pastikan kursi belum dipakai orang lain di jadwal ini
foreach ($kursi_dipilih as $nomor) {
  $nomor_aman = esc($nomor);
  $cek = mysqli_query($db, "SELECT nomor_kursi FROM tiket WHERE id_jadwal = '$id_jadwal_aman' AND nomor_kursi = '$nomor_aman'");
  if (mysqli_num_rows($cek) > 0) {
    header("Location: " . $url_kursi);
    exit;
  }
}

// simpan satu tiket untuk setiap kursi
foreach ($kursi_dipilih as $nomor) {
  $nomor_aman = esc($nomor);
  $id_tiket = buat_id_tiket($db);
  mysqli_query($db, "INSERT INTO tiket (id_tiket, nomor_kursi, id_pemesanan, id_jadwal, harga)
    VALUES ('$id_tiket', '$nomor_aman', '$id_pemesanan_aman', '$id_jadwal_aman', $harga)");
}

$total = $harga * $kuota;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Konfirmasi - Bioskop</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
  <?php include "navbar.php"; ?>

  <main class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Konfirmasi Pemesanan</h1>

    <div class="rounded-2xl border bg-white p-6 space-y-3">
      <div class="flex justify-between"><span>Film</span><span class="font-medium"><?= h($data['judul']) ?></span></div>
      <div class="flex justify-between"><span>Studio</span><span><?= h($data['nama_studio']) ?></span></div>
      <div class="flex justify-between"><span>Tanggal</span><span><?= h($data['tanggal']) ?></span></div>
      <div class="flex justify-between"><span>Jam</span><span><?= h(substr($data['jam_mulai'], 0, 5)) ?> - <?= h(substr($data['jam_selesai'], 0, 5)) ?></span></div>
      <div class="flex justify-between"><span>Kursi</span><span><?= h(implode(', ', $kursi_dipilih)) ?></span></div>
      <div class="flex justify-between"><span>Jumlah</span><span><?= $kuota ?> tiket</span></div>
      <div class="flex justify-between"><span>Harga Satuan</span><span>Rp <?= number_format($harga, 0, ',', '.') ?></span></div>
      <div class="flex justify-between text-lg font-semibold"><span>Total</span><span>Rp <?= number_format($total, 0, ',', '.') ?></span></div>
    </div>

    <a href="pesanan_saya.php" class="inline-block mt-4 mr-2 bg-white border text-gray-700 px-4 py-2 rounded-xl">Pesanan Saya</a>
    <a href="pembayaran.php?id_pemesanan=<?= h($id_pemesanan) ?>" class="inline-block mt-4 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl">Bayar Sekarang</a>
    <a href="index.php" class="inline-block mt-4 ml-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl">Selesai</a>
  </main>
</body>
</html>