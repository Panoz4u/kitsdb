<?php
require_once 'auth.php';
require_once 'config.php';

requireAdmin();
$pdo = getDb();
$stmt = $pdo->query('SELECT nation_id, name FROM nations ORDER BY name');
$nations = $stmt->fetchAll();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nations List - KITSDB</title>
  <link rel="stylesheet" href="css/styles.css">
  <style>
    .nations-table {
      background: var(--surface);
      border-radius: 0.5rem;
      padding: 1.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      max-width: 600px;
      margin: 0 auto;
    }
    
    .nations-table table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .nations-table th,
    .nations-table td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid var(--border-color);
      color: var(--primary-text);
    }
    
    .nations-table th {
      background: var(--background);
      font-weight: 600;
      color: var(--highlight-yellow);
    }
    
    .nations-table tr:hover {
      background: rgba(220, 247, 99, 0.1);
    }
  </style>
</head>
<body>
  <?php include 'includes/admin_header.php'; ?>
  
  <div class="container">
    <h1>Nations List</h1>
    <?php if (!$nations): ?>
      <div class="nations-table">
        <p>No nations found.</p>
      </div>
    <?php else: ?>
      <div class="nations-table">
        <table>
          <tr><th>ID</th><th>Name</th></tr>
          <?php foreach ($nations as $n): ?>
            <tr>
              <td><?= htmlspecialchars($n['nation_id']) ?></td>
              <td><?= htmlspecialchars($n['name']) ?></td>
            </tr>
          <?php endforeach ?>
        </table>
      </div>
    <?php endif ?>
  </div>
</body>
</html>