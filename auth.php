<?php
session_start();

// fungsi untuk menampilkan teks dengan aman di HTML
function h($teks) {
  return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

// halaman ini hanya boleh dibuka kalau sudah login
function require_login() {
  if (empty($_SESSION['user'])) {
    $next = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login.php?next=" . $next);
    exit;
  }
}

// halaman ini hanya boleh dibuka admin
function require_admin() {
  if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
  }
}
