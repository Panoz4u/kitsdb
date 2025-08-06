<?php
// login.php
session_start();
require dirname(__FILE__) . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT user_id, password_hash, role FROM users WHERE username = ?');
    // usa array(...) al posto di [...]
    $stmt->execute(array($username));
    $user = $stmt->fetch();

    // verifica bcrypt tramite crypt()
    if ($user && crypt($password, $user['password_hash']) === $user['password_hash']) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role']    = $user['role'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username o password non validi.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>
  <section class="section">
    <div class="container col-4" style="margin: 0 auto;">
      <div class="card">
        <h2>Admin Login</h2>
        <?php if ($error): ?>
          <div class="error" style="color:var(--action-red); text-align:center;">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif ?>
        <form method="post" novalidate>
          <label for="username">Username</label>
          <input id="username" type="text" name="username" required autofocus>
          <label for="password">Password</label>
          <input id="password" type="password" name="password" required>
          <button type="submit" class="btn btn-primary">Entra</button>
        </form>
      </div>
    </div>
  </section>
</body>
</html>
