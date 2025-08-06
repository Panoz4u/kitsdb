<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require dirname(__FILE__) . '/config.php';

// conteggi di esempio
$pdo = getDb();
$totalKits      = $pdo->query('SELECT COUNT(*) FROM kits')->fetchColumn();
$totalPhotos    = $pdo->query('SELECT COUNT(*) FROM photos')->fetchColumn();
?>


<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>
  <header class="section" style="background:var(--main-purple); color:var(--white);">
    <div class="container row">
      <div class="col"><h1>KitsDB Dashboard</h1></div>
      <nav class="col" style="text-align:right;">
        <a href="logout.php" class="btn btn-secondary">Logout</a>
      </nav>
    </div>
  </header>

  <section class="section">
    <div class="container row">
      <div class="col-3">
        <div class="card">
          <h3>Total Kits</h3>
          <p><?= $totalKits ?></p>
        </div>
      </div>
      <div class="col-3">
        <div class="card">
          <h3>Total Photos</h3>
          <p><?= $totalPhotos ?></p>
        </div>
      </div>
      <div class="col-3">
        <div class="card">
          <h3><a href="kit_add.php" class="btn btn-primary">Add Kit</a></h3>
        </div>
      </div>
      <div class="col-3">
        <div class="card">
          <h3><a href="kits_list.php" class="btn btn-secondary">Manage Kits</a></h3>
        </div>
      </div>
    </div>
  </section>
</body>
</html>
