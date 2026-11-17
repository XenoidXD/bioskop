<?php
include "database.php";
include "auth.php";

$message = "";

if (isset($_POST['confirm'])) {
  $email = esc($_POST['email']);
  $password = esc($_POST['password']);
  $nama = esc($_POST['nama']);
  $nohp = esc($_POST['nohp']);

  $sql = "INSERT INTO penonton (email, password, nama, nomor_hp) VALUES ('$email', '$password', '$nama', '$nohp')";

  if (mysqli_query($db, $sql)) {
    header("Location: login.php");
    exit;
  } else {
    $message = "Gagal";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register - Bioskop</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-900">
  <?php include "navbar.php"; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <div class="mx-auto max-w-md">
      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold">Daftar</h1>
        <p class="text-gray-600 mt-1">Lengkapi data berikut.</p>
      </div>

      <div class="rounded-2xl border bg-white shadow-sm p-6">
        <form action="register.php" method="POST" class="space-y-4">
          <div class="space-y-1.5">
            <label for="email" class="text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" required
              class="w-full rounded-xl border px-3 py-2" />
          </div>

          <div class="space-y-1.5">
            <label for="nama" class="text-sm font-medium">Nama Lengkap</label>
            <input id="nama" name="nama" type="text" required
              class="w-full rounded-xl border px-3 py-2" />
          </div>

          <div class="space-y-1.5">
            <label for="nohp" class="text-sm font-medium">Nomor HP</label>
            <input id="nohp" name="nohp" type="tel" pattern="[0-9]{8,15}" placeholder="08xxxxxxxxxx" required
              class="w-full rounded-xl border px-3 py-2" />
          </div>

          <div class="space-y-1.5">
            <label for="password" class="text-sm font-medium">Password</label>
            <input id="password" name="password" type="password" minlength="6" required
              class="w-full rounded-xl border px-3 py-2" />
          </div>

          <p class="text-sm text-red-600"><?= h($message) ?></p>

          <button name="confirm" type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2.5 rounded-xl">
            Daftar
          </button>
        </form>
      </div>

      <p class="text-sm text-gray-600 text-center mt-6">
        Sudah punya akun? <a href="login.php" class="text-indigo-600 font-medium">Masuk</a>
      </p>
    </div>
  </main>

  <?php include "footer.html"; ?>
</body>
</html>