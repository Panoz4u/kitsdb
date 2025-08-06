<?php
// kit_add.php
session_start();
// Protect: only admin
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require dirname(__FILE__) . '/config.php';

$pdo = getDb();
$error = '';
$success = '';

// Fetch lookup data
try {
    $teams   = $pdo->query("SELECT team_id,name FROM teams ORDER BY name")->fetchAll();
    $brands  = $pdo->query("SELECT brand_id,name FROM brands ORDER BY name")->fetchAll();
    $seasons = $pdo->query("SELECT season_id,name FROM seasons ORDER BY name DESC")->fetchAll();
    $sizes   = $pdo->query("SELECT size_id,name FROM sizes ORDER BY size_id")->fetchAll();
    $conds   = $pdo->query("SELECT condition_id,name,stars FROM conditions ORDER BY stars DESC")->fetchAll();
    $colors  = $pdo->query("SELECT color_id,name FROM colors ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract and sanitize inputs
    $team_id      = $_POST['team_id'];
    $season_id    = $_POST['season_id'];
    $number       = intval($_POST['number']);
    $player_name  = trim($_POST['player_name']);
    // Brand autocomplete: split value to ID if present
    $brand_raw    = $_POST['brand_id'];
    $brand_id     = null;
    if (!empty($brand_raw)) {
        $parts = explode('|', $brand_raw);
        $brand_id = intval($parts[0]);
    }
    // Size and condition from hidden fields
    $size_id      = intval($_POST['size_id']);
    $condition_id = intval($_POST['condition_id']);
    $notes        = trim($_POST['notes']);

    // Insert kit
    $insertKit = $pdo->prepare(
        "INSERT INTO kits
         (team_id,season,number,player_name,brand_id,size_id,condition_id,notes,created_at)
         VALUES
         (?,(SELECT name FROM seasons WHERE season_id=?),?,?,?,?,?,NOW())"
    );
    $insertKit->execute(array(
        $team_id,
        $season_id,
        $number,
        $player_name,
        $brand_id,
        $size_id,
        $condition_id,
        $notes
    ));
    $kit_id = $pdo->lastInsertId();

    // Handle photo upload
    if (!empty($_FILES['photos']['name'][0])) {
        $uploadDir = __DIR__ . '/uploads/';
        for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
            $tmp      = $_FILES['photos']['tmp_name'][$i];
            $original = basename($_FILES['photos']['name'][$i]);
            $filename = time() . '_' . $i . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '', $original);
            $dst      = $uploadDir . $filename;
            if (move_uploaded_file($tmp, $dst)) {
                $classId = intval($_POST['photo_classification'][$i]);
                $title   = trim($_POST['photo_title'][$i]);
                $pdo->prepare(
                    'INSERT INTO photos (kit_id,filename,title,classification_id) VALUES (?,?,?,?)'
                )->execute(array($kit_id, $filename, $title, $classId));
            }
        }
    }
    $success = "Kit inserted with ID $kit_id";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Kit</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/styles.css">
  <style>
    .container { padding: var(--space-md); }
    .inline-row { display: flex; gap: var(--space-sm); }
    .inline-row .small { flex: 0 0 80px; }
    .toggle-btn {
      display: inline-block;
      width: 2.5rem;
      height: 2.5rem;
      margin: 0.25rem;
      border: 2px solid var(--border-color);
      border-radius: 0.25rem;
      text-align: center;
      line-height: 2.5rem;
      cursor: pointer;
      transition: background .2s, border-color .2s;
    }
    .toggle-btn.active {
      background: var(--action);
      color: var(--white);
      border-color: var(--action);
    }
    .file-label {
      display: inline-block;
      padding: 0.75rem 1.5rem;
      background: var(--highlight);
      color: var(--black);
      border-radius: 0.375rem;
      cursor: pointer;
      margin-bottom: var(--space-md);
    }
    input[type="file"] { display: none; }
    .swatch {
      display: inline-block;
      width: 1rem;
      height: 1rem;
      margin-left: 0.5rem;
      vertical-align: middle;
      border: 1px solid var(--gray-600);
    }
  </style>
</head>
<body>
  <header class="section" style="background:var(--main-purple);color:var(--white);">
    <div class="container row">
      <div class="col"><h1>Add Kit</h1></div>
      <nav class="col" style="text-align:right;"><a href="dashboard.php" class="btn btn-secondary">Dashboard</a></nav>
    </div>
  </header>
  <section class="section">
    <div class="container">
      <?php if ($error): ?>
        <div class="error"><?=htmlspecialchars($error)?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="card" style="border-color:var(--highlight);"><?=htmlspecialchars($success)?></div>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <label for="team">Team</label>
        <input list="teams-list" id="team" placeholder="Type to search..." required>
        <datalist id="teams-list">
          <?php foreach($teams as $t): ?>
            <option data-id="<?=$t['team_id']?>" value="<?=htmlspecialchars($t['name'])?>">
          <?php endforeach; ?>
        </datalist>
        <input type="hidden" name="team_id" id="team_id">

        <label for="season">Season</label>
        <select name="season_id" id="season" required>
          <?php foreach($seasons as $s): ?>
            <option value="<?=$s['season_id']?>"><?=htmlspecialchars($s['name'])?></option>
          <?php endforeach; ?>
        </select>

        <div class="inline-row">
          <div class="small">
            <label for="number">No.</label>
            <input type="number" id="number" name="number" min="0" max="999" required>
          </div>
          <div style="flex:1;">
            <label for="player">Player Name</label>
            <input type="text" id="player" name="player_name">
          </div>
        </div>

        <label for="brand">Brand</label>
        <input list="brands-list" id="brand" placeholder="Type to search...">
        <datalist id="brands-list">
          <?php foreach($brands as $b): ?>
            <option data-id="<?=$b['brand_id']?>" value="<?=htmlspecialchars($b['name'])?>">
          <?php endforeach; ?>
        </datalist>
        <input type="hidden" name="brand_id" id="brand_id">

        <label>Size</label>
        <div id="sizes-container">
          <?php foreach($sizes as $sz): ?>
            <div class="toggle-btn" data-id="<?=$sz['size_id']?>"><?=htmlspecialchars($sz['name'])?></div>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="size_id" id="size_id">

        <label>Condition</label>
        <div id="conds-container">
          <?php foreach($conds as $c): ?>
            <?php $stars = str_repeat('★',$c['stars']); ?>
            <div class="toggle-btn" data-id="<?=$c['condition_id']?>"><?=$stars?></div>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="condition_id" id="condition_id">

<?php for ($ci = 1; $ci <= 3; $ci++): ?>
  <label for="color<?= $ci ?>">Color <?= $ci ?></label>
  <select name="color<?= $ci ?>_id" id="color<?= $ci ?>" required>
    <option value="">– none –</option>
    <?php foreach ($colors as $col): ?>
      <option value="<?= $col['color_id'] ?>">
        <?= htmlspecialchars($col['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <span class="swatch" id="swatch<?= $ci ?>"></span>
<?php endfor; ?>
