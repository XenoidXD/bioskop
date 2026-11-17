<?php
$_halaman = basename($_SERVER['PHP_SELF']);
function nav_link($href, $label, $aktif_class, $kelas_normal) {
  global $_halaman;
  $aktif = basename($href) === $_halaman;
  $kelas = $aktif ? $aktif_class . ' underline underline-offset-4' : $kelas_normal;
  return '<a href="' . $href . '" class="' . $kelas . '">' . $label . '</a>';
}
?>
<header class="sticky top-0 z-30 bg-white border-b">
  <nav class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
    <a href="index.php" class="font-bold text-xl">Bioskop</a>

    <div class="flex items-center gap-4 text-sm">
      <?php if (isset($_SESSION['user'])): ?>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
          <span class="text-gray-600">Hi, Administrator</span>
          <?= nav_link('admin.php', 'Admin Panel', 'text-purple-700 font-semibold', 'text-purple-600 font-medium') ?>
          <?= nav_link('index.php', 'Halaman User', 'text-indigo-700 font-semibold', 'text-indigo-600') ?>
          <a href="logout.php" class="text-red-600">Keluar</a>
        <?php else: ?>
          <span class="text-gray-600">Hi, <?= h($_SESSION['user']['nama']) ?></span>
          <?= nav_link('pesanan_saya.php', 'Pesanan Saya', 'text-indigo-700 font-semibold', 'text-indigo-600') ?>
          <?= nav_link('profile.php', 'Profil', 'text-indigo-700 font-semibold', 'text-indigo-600') ?>
          <a href="logout.php" class="text-red-600">Keluar</a>
        <?php endif; ?>

      <?php else: ?>
        <?= nav_link('login.php', 'Masuk', 'text-indigo-700 font-semibold', 'text-indigo-600') ?>
        <?= nav_link('register.php', 'Daftar', 'text-indigo-700 font-semibold', 'text-indigo-600') ?>
      <?php endif; ?>
    </div>
  </nav>
</header>
