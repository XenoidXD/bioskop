<?php
include "database.php";
include "auth.php";

$message = "";
$next = "index.php";
if (isset($_GET['next'])) {
  $next = $_GET['next'];
}
if (isset($_POST['next'])) {
  $next = $_POST['next'];
}

if (isset($_POST['confirm'])) {
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  // login admin (sesuai spesifikasi tugas)
  if ($email == 'Admin' && $password == 'admin123') {
    $_SESSION['user'] = [
      'id' => 0,
      'email' => 'Admin',
      'nama' => 'Administrator'
    ];
    $_SESSION['role'] = 'admin';
    header("Location: admin.php");
    exit;
  }

  // login penonton biasa
  $stmt = $db->prepare("SELECT id_penonton, email, nama, password FROM penonton WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($user && $password == $user['password']) {
    $nama = $user['nama'];
    if ($nama == '') {
      $nama = $user['email'];
    }

    $_SESSION['user'] = [
      'id' => (int)$user['id_penonton'],
      'email' => $user['email'],
      'nama' => $nama
    ];
    header("Location: " . $next);
    exit;
  } else {
    $message = "Email atau password salah!";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Bioskop</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-900">
  <?php include "navbar.php"; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <div class="mx-auto max-w-md">
      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold">Login</h1>
        <p class="text-gray-600 mt-1">Masukkan email dan password.</p>
      </div>

      <div class="rounded-2xl border bg-white shadow-sm p-6">
        <?php if ($message != ""): ?>
          <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <?= h($message) ?>
          </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-4">
          <input type="hidden" name="next" value="<?= h($next) ?>">

          <div class="space-y-1.5">
            <label for="email" class="text-sm font-medium">Email</label>
            <input name="email" id="email" type="text" required
              class="w-full rounded-xl border px-3 py-2" />
          </div>

          <div class="space-y-1.5">
            <label for="password" class="text-sm font-medium">Password</label>
            <input name="password" id="password" type="password" required minlength="6"
              class="w-full rounded-xl border px-3 py-2" />
          </div>

          <button name="confirm" type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2.5 rounded-xl">
            Masuk
          </button>
        </form>
      </div>

      <p class="text-sm text-gray-600 text-center mt-6">
        Belum punya akun? <a href="register.php" class="text-indigo-600 font-medium">Daftar</a>
      </p>
    </div>
  </main>

  <?php include "footer.html"; ?>
</body>
</html>
