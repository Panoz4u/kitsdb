<?php
// diagnostics at top of nations.php

require dirname(__FILE__) . '/config.php';
$pdo = getDb();
$stmt = $pdo->query('SELECT nation_id, name FROM nations ORDER BY name');
$nations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Lista Nazioni</title>
  <style>
    body { font-family: sans-serif; margin: 2rem; }
    table { border-collapse: collapse; width: 100%; max-width: 400px; }
    th, td { border: 1px solid #ccc; padding: 0.5rem; }
  </style>
</head>
<body>
  <h1>Elenco Nazioni</h1>
  <?php if (!$nations): ?>
    <p>Nessuna nazione trovata.</p>
  <?php else: ?>
    <table>
      <tr><th>ID</th><th>Nome</th></tr>
      <?php foreach ($nations as $n): ?>
        <tr>
          <td><?= htmlspecialchars($n['nation_id']) ?></td>
          <td><?= htmlspecialchars($n['name']) ?></td>
        </tr>
      <?php endforeach ?>
    </table>
  <?php endif ?>
</body>
</html>