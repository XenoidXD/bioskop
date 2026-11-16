<header class="sticky top-0 z-30 bg-white border-b">
  <nav class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
    <a href="index.php" class="font-bold text-xl">Bioskop</a>

    <div class="flex items-center gap-4 text-sm">
      <?php if (isset($_SESSION['user'])): ?>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
          <span class="text-gray-600">Hi, Administrator</span>
          <a href="logout.php" class="text-red-600">Keluar</a>
        <?php else: ?>
          <span class="text-gray-600">Hi, <?= h($_SESSION['user']['nama']) ?></span>
          <a href="pesanan_saya.php" class="text-indigo-600">Pesanan Saya</a>
          <a href="profile.php" class="text-indigo-600">Profil</a>
          <a href="logout.php" class="text-red-600">Keluar</a>
        <?php endif; ?>

      <?php else: ?>
        <a href="login.php" class="text-indigo-600">Masuk</a>
        <a href="register.php" class="text-indigo-600">Daftar</a>
      <?php endif; ?>
    </div>
  </nav>
</header>
