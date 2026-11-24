<?php
require "auth.php";
require "database.php";
require_login();

$id_user = (int)$_SESSION['user']['id'];
$success = "";
$error = "";

// ===== PROSES BATALKAN PESANAN =====
if (isset($_POST['cancel_order'])) {
  $id_pesan = trim($_POST['id_pemesanan']);

  if ($id_pesan == "") {
    $error = "ID pemesanan tidak valid.";
  } else {
    $id_pesan_aman = esc($id_pesan);

    // pastikan pesanan milik user
    $hasil = mysqli_query($db, "SELECT id_pemesanan FROM pemesanan WHERE id_pemesanan = '$id_pesan_aman' AND id_penonton = $id_user");
    $punya = mysqli_fetch_assoc($hasil);

    if (!$punya) {
      $error = "Pemesanan tidak ditemukan atau bukan milik Anda.";
    } else {
      // cek status pembayaran terakhir
      $hasil = mysqli_query($db, "SELECT status_bayar FROM pembayaran WHERE id_pemesanan = '$id_pesan_aman' ORDER BY id_pembayaran DESC LIMIT 1");
      $bayar = mysqli_fetch_assoc($hasil);

      if ($bayar && $bayar['status_bayar'] == 'SUKSES') {
        $error = "Pemesanan sudah dibayar dan tidak bisa dibatalkan.";
      } else {
        // hapus tiket, pembayaran, dan pemesanan dalam satu transaksi
        try {
          mysqli_begin_transaction($db);

          mysqli_query($db, "DELETE FROM tiket WHERE id_pemesanan = '$id_pesan_aman'");
          mysqli_query($db, "DELETE FROM pembayaran WHERE id_pemesanan = '$id_pesan_aman'");
          mysqli_query($db, "DELETE FROM pemesanan WHERE id_pemesanan = '$id_pesan_aman' AND id_penonton = $id_user");

          mysqli_commit($db);
          $success = "Pemesanan berhasil dibatalkan dan dihapus.";
        } catch (Exception $e) {
          mysqli_rollback($db);
          $error = "Gagal membatalkan pesanan. Silakan coba lagi.";
        }
      }
    }
  }
}

// ===== AMBIL DAFTAR PESANAN =====
$hasil = mysqli_query($db, "SELECT * FROM pemesanan WHERE id_penonton = $id_user ORDER BY tanggal_pesan DESC");
$daftar = mysqli_fetch_all($hasil, MYSQLI_ASSOC);

$items = [];
foreach ($daftar as $p) {
  $id_pesan_aman = esc($p['id_pemesanan']);

  // tiket milik pesanan ini (beserta jadwal, film, studio)
  $hasil = mysqli_query($db, "SELECT t.nomor_kursi, t.harga, j.tanggal, j.jam_mulai, j.jam_selesai, f.judul, s.nama_studio
    FROM tiket t
    JOIN jadwal_tayang j ON j.id_jadwal = t.id_jadwal
    JOIN film f ON f.id_film = j.id_film
    JOIN studio s ON s.id_studio = f.id_studio
    WHERE t.id_pemesanan = '$id_pesan_aman'
    ORDER BY t.nomor_kursi");
  $tiket = mysqli_fetch_all($hasil, MYSQLI_ASSOC);

  // pembayaran terakhir
  $hasil = mysqli_query($db, "SELECT metode_bayar, status_bayar, total_bayar FROM pembayaran
    WHERE id_pemesanan = '$id_pesan_aman' ORDER BY id_pembayaran DESC LIMIT 1");
  $bayar = mysqli_fetch_assoc($hasil);

  // data tampilan, defaultnya "-"
  $p['judul'] = '-';
  $p['nama_studio'] = '-';
  $p['tgl_tayang'] = '-';
  $p['jam'] = '-';
  $p['kursi'] = '-';
  $p['ada_tiket'] = (count($tiket) > 0);

  $daftar_kursi = [];
  $total = 0;
  foreach ($tiket as $t) {
    $daftar_kursi[] = $t['nomor_kursi'];
    $total += $t['harga'];
  }

  if ($p['ada_tiket']) {
    $p['judul'] = $tiket[0]['judul'];
    $p['nama_studio'] = $tiket[0]['nama_studio'];
    $p['tgl_tayang'] = $tiket[0]['tanggal'];
    $p['jam'] = substr($tiket[0]['jam_mulai'], 0, 5) . ' - ' . substr($tiket[0]['jam_selesai'], 0, 5);
    $p['kursi'] = implode(', ', $daftar_kursi);
  }

  // total: pakai total pembayaran kalau sudah ada, kalau belum pakai total harga tiket
  $p['total'] = $total;
  $p['metode_bayar'] = '';
  $p['status_bayar'] = '';
  if ($bayar) {
    $p['total'] = (int)$bayar['total_bayar'];
    $p['metode_bayar'] = $bayar['metode_bayar'];
    $p['status_bayar'] = $bayar['status_bayar'];
  }

  $items[] = $p;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pesanan Saya - Bioskop</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
  <?php include "navbar.php"; ?>

  <main class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl md:text-3xl font-bold">Pesanan Saya</h1>
    <p class="text-gray-600 mt-1 mb-6">Riwayat pemesanan tiket milik akunmu.</p>

    <?php if ($success != ""): ?>
      <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= h($success) ?></div>
    <?php endif; ?>
    <?php if ($error != ""): ?>
      <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (count($items) == 0): ?>
      <div class="rounded-2xl border bg-white p-6 text-gray-600">Belum ada pemesanan.</div>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($items as $it): ?>
          <?php $lunas = ($it['status_bayar'] == 'SUKSES'); ?>

          <article class="rounded-2xl border bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
              <div>
                <div class="text-sm text-gray-500">ID Pemesanan</div>
                <div class="font-semibold"><?= h($it['id_pemesanan']) ?></div>
              </div>
              <div class="text-right text-sm text-gray-500">Dipesan: <?= h($it['tanggal_pesan']) ?></div>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div class="space-y-1">
                <div class="flex justify-between"><span>Film</span><span class="font-medium"><?= h($it['judul']) ?></span></div>
                <div class="flex justify-between"><span>Studio</span><span><?= h($it['nama_studio']) ?></span></div>
                <div class="flex justify-between"><span>Tanggal Tayang</span><span><?= h($it['tgl_tayang']) ?></span></div>
              </div>
              <div class="space-y-1">
                <div class="flex justify-between"><span>Jam</span><span><?= h($it['jam']) ?></span></div>
                <div class="flex justify-between"><span>Kursi</span><span><?= h($it['kursi']) ?></span></div>
                <div class="flex justify-between"><span>Jumlah Tiket</span><span><?= (int)$it['jumlah_tiket'] ?></span></div>
              </div>
            </div>

            <div class="mt-3 flex items-center justify-between">
              <div class="text-sm">
                <?php if ($it['status_bayar'] != ''): ?>
                  <span class="text-gray-500">Pembayaran: </span>
                  <span class="font-medium"><?= h($it['metode_bayar']) ?></span> -
                  <?php
                    $warna = 'text-yellow-600';
                    if ($lunas) { $warna = 'text-green-600'; }
                    if ($it['status_bayar'] == 'GAGAL') { $warna = 'text-red-600'; }
                  ?>
                  <span class="<?= $warna ?>"><?= h($it['status_bayar']) ?></span>
                <?php elseif ($it['ada_tiket']): ?>
                  <span class="text-red-600">Belum dibayar</span>
                <?php else: ?>
                  <span class="text-amber-600">Belum pilih kursi, batalkan dan ulangi pesanan</span>
                <?php endif; ?>
              </div>
              <div class="text-base font-semibold">Total: Rp <?= number_format($it['total'], 0, ',', '.') ?></div>
            </div>

            <?php if (!$lunas): ?>
              <div class="mt-3 flex items-center gap-2">
                <?php if ($it['ada_tiket']): ?>
                  <a href="pembayaran.php?id_pemesanan=<?= h($it['id_pemesanan']) ?>"
                     class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-sm">
                    Bayar Sekarang
                  </a>
                <?php endif; ?>

                <form method="post" onsubmit="return confirm('Yakin batalkan pesanan ini?');">
                  <input type="hidden" name="id_pemesanan" value="<?= h($it['id_pemesanan']) ?>">
                  <button name="cancel_order" type="submit"
                          class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-sm">
                    Batalkan Pesanan
                  </button>
                </form>
              </div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <a href="index.php" class="inline-block mt-6 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl">
      Kembali ke Beranda
    </a>
  </main>
</body>
</html>