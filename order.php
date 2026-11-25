<?php
require "auth.php";
require "database.php";

$id_film = "";
if (isset($_GET['id'])) {
  $id_film = $_GET['id'];
}

$film = null;
$jadwal = [];
$error = "";
$id_film_aman = esc($id_film);

// ambil data film beserta studionya
if ($id_film != "") {
  $hasil = mysqli_query($db, "SELECT f.*, s.nama_studio, s.kapasitas FROM film f
    LEFT JOIN studio s ON s.id_studio = f.id_studio
    WHERE f.id_film = '$id_film_aman'");
  $film = mysqli_fetch_assoc($hasil);
}

// harga dan total (untuk ringkasan)
$harga = 0;
if ($film) {
  $harga = (int)$film['harga'];
}
$jumlah = 1;
if (isset($_POST['jumlah_tiket'])) {
  $jumlah = min(62,max(1, (int)$_POST['jumlah_tiket']));
}
$total = $harga * $jumlah;

// proses tombol "Buat Pemesanan"
if ($film && isset($_POST['buat_pesan'])) {
  $id_jadwal = "";
  if (isset($_POST['id_jadwal'])) {
    $id_jadwal = $_POST['id_jadwal'];
  }

  $id_user = 0;
  if (isset($_SESSION['user'])) {
    $id_user = (int)$_SESSION['user']['id'];
  }

  if ($id_user == 0) {
    $error = "Silakan login terlebih dahulu.";
  } elseif ($id_jadwal == "") {
    $error = "Pilih jadwal terlebih dahulu.";
  } else {
    $id_pesan = 'PM' . substr(time() . mt_rand(100, 999), -8);

    $sql = "INSERT INTO pemesanan (id_pemesanan, id_penonton, tanggal_pesan, jumlah_tiket)
      VALUES ('$id_pesan', $id_user, NOW(), $jumlah)";

    if (mysqli_query($db, $sql)) {
      $id_studio = $film['id_studio'];
      header("Location: pilih_kursi.php?id_pemesanan=$id_pesan&id_jadwal=$id_jadwal&id_studio=$id_studio");
      exit;
    } else {
      $error = "Gagal menyimpan pemesanan.";
    }
  }
}

// ambil jadwal tayang mulai hari ini
if ($film) {
  $hasil = mysqli_query($db, "SELECT * FROM jadwal_tayang
    WHERE id_film = '$id_film_aman' AND tanggal >= CURDATE()
    ORDER BY tanggal ASC, jam_mulai ASC");
  $jadwal = mysqli_fetch_all($hasil, MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pemesanan - Bioskop</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
  <?php include "navbar.php"; ?>

  <main class="max-w-6xl mx-auto px-4 py-6">
    <?php if (!$film): ?>
      <div class="rounded-xl border bg-white p-4">
        Film tidak ditemukan. <a class="text-indigo-600" href="index.php">Kembali</a>
      </div>
    <?php else: ?>

      <?php if ($error != ""): ?>
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          <?= h($error) ?>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">

          <!-- INFO FILM -->
          <div class="rounded-2xl border bg-white p-4">
            <div class="flex items-start gap-4">
              <div class="w-28 h-36 rounded-lg bg-gray-200 grid place-items-center text-3xl">🎬</div>
              <div>
                <h1 class="text-2xl font-bold"><?= h($film['judul']) ?></h1>
                <div class="text-sm text-gray-600 mt-1">
                  <?= h($film['genre']) ?> • Durasi <?= h(substr($film['durasi'], 0, 5)) ?> • <?= (int)$film['rating'] ?>+
                </div>
                <div class="text-sm text-gray-600 mt-1">
                  Studio: <?= h($film['nama_studio']) ?>
                  <?php if (!empty($film['kapasitas'])): ?>(kapasitas <?= (int)$film['kapasitas'] ?>)<?php endif; ?>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                  Tayang: <?= h($film['mulai_tayang']) ?> s.d. <?= h($film['selesai_tayang']) ?>
                </p>
              </div>
            </div>
          </div>

          <!-- PILIH JADWAL -->
          <div class="rounded-2xl border bg-white p-4">
            <h2 class="font-semibold mb-3">Pilih Jadwal</h2>

            <?php if (count($jadwal) == 0): ?>
              <div class="text-sm text-gray-600">Belum ada jadwal dari hari ini.</div>
            <?php else: ?>
              <form method="post" class="space-y-4">
                <div class="flex flex-wrap gap-2">
                  <?php foreach ($jadwal as $j): ?>
                    <label class="inline-flex items-center gap-2">
                      <input type="radio" name="id_jadwal" value="<?= h($j['id_jadwal']) ?>" required>
                      <span class="px-3 py-2 rounded-lg border text-sm bg-white">
                        <?= h($j['tanggal']) ?> • <?= h(substr($j['jam_mulai'], 0, 5)) ?>
                      </span>
                    </label>
                  <?php endforeach; ?>
                </div>

                <div class="flex items-center gap-3">
                  <label class="text-sm">Jumlah Tiket</label>
                  <input type="number" name="jumlah_tiket" min="1" value="1" class="w-20 border rounded-lg px-2 py-1 text-center" />
                </div>

                <button name="buat_pesan" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded-xl">
                  Buat Pemesanan
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- RINGKASAN -->
        <aside>
          <div class="rounded-2xl border bg-white p-4">
            <h3 class="font-semibold mb-3">Ringkasan</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between"><span>Film</span><span class="font-medium"><?= h($film['judul']) ?></span></div>
              <div class="flex justify-between"><span>Studio</span><span><?= h($film['nama_studio']) ?></span></div>
              <div class="flex justify-between"><span>Harga Satuan</span><span>Rp <?= number_format($harga, 0, ',', '.') ?></span></div>
              <div class="flex justify-between text-base font-semibold"><span>Total</span><span>Rp <?= number_format($total, 0, ',', '.') ?></span></div>
              <div class="text-xs text-gray-500">Pilih jadwal dan jumlah tiket, lalu klik "Buat Pemesanan".</div>
            </div>
          </div>
        </aside>
      </div>

    <?php endif; ?>
  </main>
</body>
</html>