<?php
require "auth.php";
require "database.php";
require_login();

// data yang wajib ada di URL
$id_pemesanan = "";
$id_jadwal = "";
$id_studio = "";
if (isset($_GET['id_pemesanan'])) { $id_pemesanan = $_GET['id_pemesanan']; }
if (isset($_GET['id_jadwal']))    { $id_jadwal = $_GET['id_jadwal']; }
if (isset($_GET['id_studio']))    { $id_studio = $_GET['id_studio']; }

if ($id_pemesanan == "" || $id_jadwal == "" || $id_studio == "") {
  header("Location: index.php");
  exit;
}

// pastikan pemesanan ini milik user yang login
$stmt = $db->prepare("SELECT * FROM pemesanan WHERE id_pemesanan = ? AND id_penonton = ?");
$stmt->bind_param("si", $id_pemesanan, $_SESSION['user']['id']);
$stmt->execute();
$pemesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pemesanan) {
  header("Location: index.php");
  exit;
}

// semua kursi di studio
$stmt = $db->prepare("SELECT nomor_kursi FROM kursi WHERE id_studio = ? ORDER BY nomor_kursi");
$stmt->bind_param("s", $id_studio);
$stmt->execute();
$kursi = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// kursi yang sudah dipakai di jadwal ini
$terpakai = [];
$stmt = $db->prepare("SELECT nomor_kursi FROM tiket WHERE id_jadwal = ?");
$stmt->bind_param("s", $id_jadwal);
$stmt->execute();
$hasil = $stmt->get_result();
while ($baris = $hasil->fetch_assoc()) {
  $terpakai[] = $baris['nomor_kursi'];
}
$stmt->close();

$kuota = (int)$pemesanan['jumlah_tiket'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pilih Kursi - Bioskop</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    // batasi jumlah kursi yang boleh dipilih
    function batasiKursi(checkbox, max) {
      var dipilih = document.querySelectorAll('input[name="nomor_kursi[]"]:checked').length;
      if (dipilih > max) {
        alert("Kamu hanya boleh memilih " + max + " kursi.");
        checkbox.checked = false;
      }
    }
  </script>
</head>
<body class="bg-gray-50 text-gray-900">
  <?php include "navbar.php"; ?>

  <main class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Pilih Kursi</h1>
    <p class="text-gray-600 mb-4">Silakan pilih <?= $kuota ?> kursi.</p>

    <form action="konfirmasi.php" method="post" class="space-y-4">
      <input type="hidden" name="id_pemesanan" value="<?= h($id_pemesanan) ?>">
      <input type="hidden" name="id_jadwal" value="<?= h($id_jadwal) ?>">

      <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
        <?php foreach ($kursi as $k): ?>
          <?php $dipakai = in_array($k['nomor_kursi'], $terpakai); ?>
          <label>
            <input type="checkbox" name="nomor_kursi[]" value="<?= h($k['nomor_kursi']) ?>"
                   class="peer sr-only"
                   <?php if ($dipakai) { echo 'disabled'; } ?>
                   onclick="batasiKursi(this, <?= $kuota ?>)">

            <?php if ($dipakai): ?>
              <div class="px-3 py-2 border rounded-xl text-center text-sm bg-gray-200 text-gray-400 cursor-not-allowed">
                <?= h($k['nomor_kursi']) ?>
              </div>
            <?php else: ?>
              <div class="px-3 py-2 border rounded-xl text-center text-sm bg-white hover:bg-gray-50 peer-checked:bg-indigo-600 peer-checked:text-white">
                <?= h($k['nomor_kursi']) ?>
              </div>
            <?php endif; ?>
          </label>
        <?php endforeach; ?>
      </div>

      <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded-xl">
        Lanjutkan
      </button>
    </form>
  </main>
</body>
</html>
