<?php
require "auth.php";
require "database.php";
require_login();

// film yang sedang tayang
$sekarang = $db->query("SELECT f.*, s.nama_studio FROM film f
  LEFT JOIN studio s ON s.id_studio = f.id_studio
  WHERE CURDATE() BETWEEN f.mulai_tayang AND f.selesai_tayang
  ORDER BY f.judul");

// film yang akan tayang
$akan = $db->query("SELECT * FROM film WHERE mulai_tayang > CURDATE() ORDER BY mulai_tayang ASC");

// film yang sudah pernah tayang
$lewat = $db->query("SELECT * FROM film WHERE selesai_tayang < CURDATE() ORDER BY selesai_tayang DESC");
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - Bioskop</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
  <?php include "navbar.php"; ?>

  <main class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl md:text-3xl font-bold mb-6">Daftar Film</h1>

    <?php if (!empty($_GET['ok'])): ?>
      <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= h($_GET['ok']) ?></div>
    <?php elseif (!empty($_GET['err'])): ?>
      <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= h($_GET['err']) ?></div>
    <?php endif; ?>

    <!-- SEDANG TAYANG -->
    <section class="mb-10">
      <h2 class="text-xl md:text-2xl font-bold mb-3">Sedang Tayang</h2>
      <?php if ($sekarang->num_rows == 0): ?>
        <div class="rounded-xl border bg-white p-4 text-gray-600">Belum ada film.</div>
      <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php while ($f = $sekarang->fetch_assoc()): ?>
            <article class="rounded-2xl border bg-white shadow-sm overflow-hidden">
              <div class="h-40 bg-gray-200 grid place-items-center"><span class="text-5xl">🎬</span></div>
              <div class="p-4 space-y-2">
                <div class="flex items-start justify-between gap-3">
                  <h3 class="font-semibold text-lg"><?= h($f['judul']) ?></h3>
                  <span class="text-xs px-2 py-1 rounded-full bg-gray-100 border"><?= h($f['genre']) ?></span>
                </div>
                <p class="text-xs text-gray-500">
                  Durasi: <?= h(substr($f['durasi'], 0, 5)) ?> • <?= (int)$f['rating'] ?>+
                  <?php if (!empty($f['nama_studio'])): ?> • Studio: <?= h($f['nama_studio']) ?><?php endif; ?>
                </p>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                  <div class="flex items-center gap-3">
                    <a href="edit_film.php?id=<?= h($f['id_film']) ?>" class="text-purple-600 font-medium">Edit →</a>
                    <form method="post" action="hapus_film.php" onsubmit="return confirm('Hapus film <?= h(addslashes($f['judul'])) ?>?')">
                      <input type="hidden" name="id_film" value="<?= h($f['id_film']) ?>">
                      <button type="submit" class="text-red-500 font-medium">Hapus</button>
                    </form>
                  </div>
                <?php else: ?>
                  <a href="order.php?id=<?= h($f['id_film']) ?>" class="text-indigo-600 font-medium">Pesan →</a>
                <?php endif; ?>
              </div>
            </article>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- AKAN TAYANG -->
    <section class="mb-10">
      <h2 class="text-xl md:text-2xl font-bold mb-3">Akan Tayang</h2>
      <?php if ($akan->num_rows == 0): ?>
        <div class="rounded-xl border bg-white p-4 text-gray-600">Tidak ada jadwal mendatang.</div>
      <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php while ($f = $akan->fetch_assoc()): ?>
            <article class="rounded-2xl border bg-white shadow-sm overflow-hidden">
              <div class="h-40 bg-gray-200 grid place-items-center"><span class="text-5xl">🎬</span></div>
              <div class="p-4 space-y-2">
                <div class="flex items-start justify-between gap-3">
                  <h3 class="font-semibold text-lg"><?= h($f['judul']) ?></h3>
                  <span class="text-xs px-2 py-1 rounded-full bg-gray-100 border"><?= h($f['genre']) ?></span>
                </div>
                <p class="text-xs text-gray-500">Mulai <?= h($f['mulai_tayang']) ?></p>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                  <div class="flex items-center gap-3">
                    <a href="edit_film.php?id=<?= h($f['id_film']) ?>" class="text-purple-600 font-medium">Edit →</a>
                    <form method="post" action="hapus_film.php" onsubmit="return confirm('Hapus film <?= h(addslashes($f['judul'])) ?>?')">
                      <input type="hidden" name="id_film" value="<?= h($f['id_film']) ?>">
                      <button type="submit" class="text-red-500 font-medium">Hapus</button>
                    </form>
                  </div>
                <?php else: ?>
                  <a href="order.php?id=<?= h($f['id_film']) ?>" class="text-indigo-600 font-medium">Lihat Jadwal →</a>
                <?php endif; ?>
              </div>
            </article>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- SUDAH PERNAH TAYANG -->
    <section>
      <h2 class="text-xl md:text-2xl font-bold mb-3">Sudah Pernah Tayang</h2>
      <?php if ($lewat->num_rows == 0): ?>
        <div class="rounded-xl border bg-white p-4 text-gray-600">Belum ada arsip.</div>
      <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php while ($f = $lewat->fetch_assoc()): ?>
            <article class="rounded-2xl border bg-white shadow-sm overflow-hidden opacity-75">
              <div class="h-40 bg-gray-200 grid place-items-center"><span class="text-5xl">🎬</span></div>
              <div class="p-4 space-y-2">
                <div class="flex items-start justify-between gap-3">
                  <h3 class="font-semibold text-lg"><?= h($f['judul']) ?></h3>
                  <span class="text-xs px-2 py-1 rounded-full bg-gray-100 border"><?= h($f['genre']) ?></span>
                </div>
                <p class="text-xs text-gray-500">Selesai <?= h($f['selesai_tayang']) ?></p>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                  <div class="flex items-center gap-3">
                    <a href="edit_film.php?id=<?= h($f['id_film']) ?>" class="text-purple-600 font-medium">Edit →</a>
                    <form method="post" action="hapus_film.php" onsubmit="return confirm('Hapus film <?= h(addslashes($f['judul'])) ?>?')">
                      <input type="hidden" name="id_film" value="<?= h($f['id_film']) ?>">
                      <button type="submit" class="text-red-500 font-medium">Hapus</button>
                    </form>
                  </div>
                <?php else: ?>
                  <span class="text-gray-400 font-medium">Tidak tersedia</span>
                <?php endif; ?>
              </div>
            </article>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <?php include "footer.html"; ?>
</body>
</html>
