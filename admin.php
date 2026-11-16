<?php
require "auth.php";
require "database.php";
require_admin();

// ubah "HH:MM" atau "HH:MM:SS" menjadi jumlah detik
function ke_detik($waktu) {
  $bagian = explode(':', $waktu);
  $detik = $bagian[0] * 3600 + $bagian[1] * 60;
  if (isset($bagian[2])) {
    $detik = $detik + $bagian[2];
  }
  return $detik;
}

// jam selesai = jam mulai + durasi (kalau lewat tengah malam, kembali ke 00:00)
function hitung_jam_selesai($mulai, $durasi) {
  $total = (ke_detik($mulai) + ke_detik($durasi)) % 86400;
  return gmdate('H:i:s', $total);
}

$ok = "";
$err = "";

// ===== TAMBAH STUDIO =====
if (isset($_POST['add_studio'])) {
  $id = trim($_POST['id_studio']);
  $nama = trim($_POST['nama_studio']);
  $kapasitas = (int)$_POST['kapasitas'];

  if ($id == '' || $nama == '' || $kapasitas <= 0) {
    $err = "Lengkapi id, nama, kapasitas.";
  } else {
    $stmt = $db->prepare("INSERT INTO studio (id_studio, nama_studio, kapasitas) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $id, $nama, $kapasitas);
    if ($stmt->execute()) {
      $ok = "Studio ditambahkan.";
    } else {
      $err = "Gagal menambah studio.";
    }
    $stmt->close();
  }
}

// ===== TAMBAH FILM =====
if (isset($_POST['add_film'])) {
  $id_film = trim($_POST['id_film']);
  $id_studio = trim($_POST['id_studio_ref']);
  $judul = trim($_POST['judul']);
  $genre = trim($_POST['genre']);
  $durasi = trim($_POST['durasi']);
  $rating = (int)$_POST['rating'];
  $harga = (int)$_POST['harga'];
  $sinopsis = trim($_POST['sinopsis']);
  $mulai = trim($_POST['mulai_tayang']);
  $selesai = trim($_POST['selesai_tayang']);

  if ($id_film == '' || $id_studio == '' || $judul == '' || $durasi == '' || $mulai == '' || $selesai == '' || $harga <= 0) {
    $err = "Lengkapi field wajib film (termasuk harga).";
  } else {
    $stmt = $db->prepare("INSERT INTO film
      (id_film, id_studio, judul, genre, durasi, rating, harga, sinopsis, mulai_tayang, selesai_tayang)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssiisss", $id_film, $id_studio, $judul, $genre, $durasi, $rating, $harga, $sinopsis, $mulai, $selesai);

    if ($stmt->execute()) {
      $ok = "Film ditambahkan.";
    } else {
      $err = "Gagal menambah film.";
    }
    $stmt->close();
  }
}

// ===== TAMBAH JADWAL =====
if (isset($_POST['add_jadwal'])) {
  $id_jadwal = trim($_POST['id_jadwal']);
  $id_film = trim($_POST['id_film']);
  $tanggal = trim($_POST['tanggal']);
  $jam_mulai = trim($_POST['jam_mulai']);

  if ($id_jadwal == '' || $id_film == '' || $tanggal == '' || $jam_mulai == '') {
    $err = "Lengkapi ID Jadwal, Film, Tanggal, dan Jam Mulai.";
  } else {
    // ambil durasi film
    $stmt = $db->prepare("SELECT durasi FROM film WHERE id_film = ?");
    $stmt->bind_param("s", $id_film);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || empty($row['durasi'])) {
      $err = "Durasi film tidak ditemukan.";
    } else {
      $jam_selesai = hitung_jam_selesai($jam_mulai, $row['durasi']);

      $stmt = $db->prepare("INSERT INTO jadwal_tayang (id_jadwal, id_film, tanggal, jam_mulai, jam_selesai)
        VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("sssss", $id_jadwal, $id_film, $tanggal, $jam_mulai, $jam_selesai);

      if ($stmt->execute()) {
        $ok = "Jadwal berhasil ditambahkan.";
      } else {
        $err = "Gagal menambah jadwal.";
      }
      $stmt->close();
    }
  }
}

// data untuk dropdown (diambil setelah proses di atas, jadi selalu terbaru)
$studios = $db->query("SELECT id_studio, nama_studio FROM studio ORDER BY nama_studio")->fetch_all(MYSQLI_ASSOC);
$films = $db->query("SELECT id_film, judul, durasi FROM film ORDER BY judul")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin - Bioskop</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
  <?php include "navbar.php"; ?>

  <main class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl md:text-3xl font-bold mb-6">Admin Panel</h1>

    <?php if ($ok != ""): ?>
      <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= h($ok) ?></div>
    <?php endif; ?>
    <?php if ($err != ""): ?>
      <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= h($err) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- TAMBAH STUDIO -->
      <section class="rounded-2xl border bg-white p-6">
        <h2 class="font-semibold text-lg mb-4">Tambah Studio</h2>
        <form method="post" class="space-y-3">
          <input type="hidden" name="add_studio" value="1">
          <div><label class="text-sm">ID Studio</label><input name="id_studio" class="w-full border rounded-xl px-3 py-2" placeholder="ST01" required></div>
          <div><label class="text-sm">Nama Studio</label><input name="nama_studio" class="w-full border rounded-xl px-3 py-2" required></div>
          <div><label class="text-sm">Kapasitas</label><input name="kapasitas" type="number" min="1" class="w-full border rounded-xl px-3 py-2" required></div>
          <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl">Simpan</button>
        </form>
      </section>

      <!-- TAMBAH FILM -->
      <section class="rounded-2xl border bg-white p-6">
        <h2 class="font-semibold text-lg mb-4">Tambah Film</h2>
        <form method="post" class="space-y-3">
          <input type="hidden" name="add_film" value="1">
          <div><label class="text-sm">ID Film</label><input name="id_film" class="w-full border rounded-xl px-3 py-2" placeholder="F001" required></div>
          <div>
            <label class="text-sm">Studio</label>
            <select name="id_studio_ref" class="w-full border rounded-xl px-3 py-2" required>
              <option value="">-- pilih studio --</option>
              <?php foreach ($studios as $s): ?>
                <option value="<?= h($s['id_studio']) ?>"><?= h($s['id_studio'] . ' - ' . $s['nama_studio']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div><label class="text-sm">Judul</label><input name="judul" class="w-full border rounded-xl px-3 py-2" required></div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="text-sm">Genre</label><input name="genre" class="w-full border rounded-xl px-3 py-2"></div>
            <div><label class="text-sm">Durasi (HH:MM:SS)</label><input name="durasi" class="w-full border rounded-xl px-3 py-2" placeholder="01:45:00" required></div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="text-sm">Rating Umur</label><input name="rating" type="number" min="0" value="13" class="w-full border rounded-xl px-3 py-2"></div>
            <div><label class="text-sm">Harga (Rupiah)</label><input name="harga" type="number" min="0" placeholder="50000" class="w-full border rounded-xl px-3 py-2" required></div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="text-sm">Mulai Tayang</label><input name="mulai_tayang" type="date" class="w-full border rounded-xl px-3 py-2" required></div>
            <div><label class="text-sm">Selesai Tayang</label><input name="selesai_tayang" type="date" class="w-full border rounded-xl px-3 py-2" required></div>
          </div>
          <div><label class="text-sm">Sinopsis</label><textarea name="sinopsis" rows="3" class="w-full border rounded-xl px-3 py-2"></textarea></div>
          <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl">Simpan</button>
        </form>
      </section>

      <!-- TAMBAH JADWAL -->
      <section class="rounded-2xl border bg-white p-6">
        <h2 class="font-semibold text-lg mb-4">Tambah Jadwal Tayang</h2>
        <form method="post" class="space-y-3">
          <input type="hidden" name="add_jadwal" value="1">

          <div>
            <label class="text-sm">ID Jadwal</label>
            <input name="id_jadwal" class="w-full border rounded-xl px-3 py-2" required>
          </div>

          <div>
            <label class="text-sm">Film</label>
            <select name="id_film" id="filmSelect" class="w-full border rounded-xl px-3 py-2" required>
              <?php foreach ($films as $f): ?>
                <option value="<?= h($f['id_film']) ?>" data-dur="<?= h($f['durasi']) ?>">
                  <?= h($f['judul']) ?> (<?= h(substr($f['durasi'], 0, 5)) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="text-sm">ID Film (terpilih)</label>
            <input type="text" id="idFilmShown" class="w-full border rounded-xl px-3 py-2 bg-gray-100" readonly>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm">Tanggal</label>
              <input type="date" name="tanggal" class="w-full border rounded-xl px-3 py-2" required>
            </div>
            <div>
              <label class="text-sm">Jam Mulai</label>
              <input type="time" name="jam_mulai" id="jamMulai" class="w-full border rounded-xl px-3 py-2" required>
            </div>
          </div>

          <div>
            <label class="text-sm">Jam Selesai (otomatis)</label>
            <input type="text" id="jamSelesai" class="w-full border rounded-xl px-3 py-2 bg-gray-100" readonly>
          </div>

          <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl">Simpan Jadwal</button>
        </form>

        <script>
          var filmSelect = document.getElementById('filmSelect');
          var idFilmShown = document.getElementById('idFilmShown');
          var jamMulai = document.getElementById('jamMulai');
          var jamSelesai = document.getElementById('jamSelesai');

          // "HH:MM:SS" -> detik
          function keDetik(waktu) {
            var bagian = waktu.split(':');
            var detik = Number(bagian[0]) * 3600 + Number(bagian[1]) * 60;
            if (bagian[2]) {
              detik = detik + Number(bagian[2]);
            }
            return detik;
          }

          function dua(angka) {
            if (angka < 10) {
              return '0' + angka;
            }
            return '' + angka;
          }

          // detik -> "HH:MM:SS"
          function keJam(detik) {
            detik = detik % 86400;
            var jam = Math.floor(detik / 3600);
            var menit = Math.floor((detik % 3600) / 60);
            var sisa = detik % 60;
            return dua(jam) + ':' + dua(menit) + ':' + dua(sisa);
          }

          // tampilkan ID film terpilih dan jam selesai
          function hitung() {
            idFilmShown.value = filmSelect.value;

            var durasi = '00:00:00';
            if (filmSelect.selectedIndex >= 0) {
              durasi = filmSelect.options[filmSelect.selectedIndex].getAttribute('data-dur');
            }
            var mulai = jamMulai.value;
            if (mulai == '') {
              mulai = '00:00';
            }
            jamSelesai.value = keJam(keDetik(mulai) + keDetik(durasi));
          }

          filmSelect.addEventListener('change', hitung);
          jamMulai.addEventListener('input', hitung);
          hitung();
        </script>
      </section>

      <!-- TAMBAH KURSI -->
      <section class="rounded-2xl border bg-white p-6">
        <h2 class="font-semibold text-lg mb-4">Tambah Kursi</h2>
        <form method="post" class="space-y-3">
          <input type="hidden" name="add_kursi" value="1">
          <div><label class="text-sm">ID Kursi</label><input name="id_kursi" class="w-full border rounded-xl px-3 py-2" placeholder="K001" required></div>
          <div>
            <label class="text-sm">Studio</label>
            <select name="id_studio_for_seat" class="w-full border rounded-xl px-3 py-2" required>
              <option value="">-- pilih studio --</option>
              <?php foreach ($studios as $s): ?>
                <option value="<?= h($s['id_studio']) ?>"><?= h($s['id_studio'] . ' - ' . $s['nama_studio']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div><label class="text-sm">Nomor Kursi</label><input name="nomor_kursi" class="w-full border rounded-xl px-3 py-2" placeholder="A1 / 1 / B12" required></div>
          <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl">Simpan</button>
        </form>
      </section>

    </div>
  </main>
</body>
</html>
