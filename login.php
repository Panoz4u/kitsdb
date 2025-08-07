<?php
session_start();
require dirname(__FILE__) . '/config.php';

$error = '';

// Se già loggato, redirect al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT user_id, username, password_hash, role FROM users WHERE username = ?');
    $stmt->execute(array($username));
    $user = $stmt->fetch();

    if ($user && crypt($password, $user['password_hash']) === $user['password_hash']) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KITSDB</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--background);
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            background: var(--surface);
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.6);
        }
        .login-title {
            text-align: center;
            font-family: var(--font-display);
            font-size: 2.5rem;
            color: var(--highlight-yellow);
            margin-bottom: 2rem;
            font-weight: 700;
        }
        .error-message {
            background: rgba(222, 60, 75, 0.1);
            border: 1px solid var(--action-red);
            color: var(--action-red);
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            color: var(--primary-text);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .login-btn {
            width: 100%;
            background: var(--action-red);
            color: var(--primary-text);
            border: none;
            padding: 1rem;
            border-radius: 0.375rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .login-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(222, 60, 75, 0.3);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h1 class="login-title">KITSDB</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" required autofocus
                           placeholder="Inserisci il tuo username">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Inserisci la tua password">
                </div>
                
                <button type="submit" class="login-btn">Accedi</button>
            </form>
        </div>
    </div>
</body>
</html>