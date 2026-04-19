<?php
// ============================================================
// ADMIN — GUESTBOOK MANAGER
// Change the code below before uploading.
// ============================================================
define('ADMIN_CODE',     'a198');             // <-- change this
define('WRONG_CODE_URL', 'https://poolsuite.net/');
define('GUESTBOOK_FILE', __DIR__ . '/guestbook.json');

session_start();

// ---- LOGOUT ----
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ---- CODE CHECK ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    if (trim($_POST['code']) === ADMIN_CODE) {
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        header('Location: ' . WRONG_CODE_URL);
        exit;
    }
}

// ---- DELETE ENTRY ----
if ($_SESSION['admin'] && isset($_POST['delete_id'])) {
    $entries = load_entries();
    $entries = array_values(array_filter($entries, function($e) {
        return $e['id'] != $_POST['delete_id'];
    }));
    save_entries($entries);
    header('Location: admin.php?done=deleted');
    exit;
}

// ---- EDIT ENTRY ----
if ($_SESSION['admin'] && isset($_POST['edit_id'], $_POST['edit_name'], $_POST['edit_message'])) {
    $entries = load_entries();
    foreach ($entries as &$e) {
        if ($e['id'] == $_POST['edit_id']) {
            $e['name']    = trim(strip_tags($_POST['edit_name']));
            $e['message'] = trim(strip_tags($_POST['edit_message']));
            break;
        }
    }
    save_entries($entries);
    header('Location: admin.php?done=saved');
    exit;
}

// ---- CLEAR ALL ----
if ($_SESSION['admin'] && isset($_POST['clear_all']) && $_POST['clear_all'] === 'CONFIRM') {
    save_entries([]);
    header('Location: admin.php?done=cleared');
    exit;
}

// ---- HELPERS ----
function load_entries() {
    if (!file_exists(GUESTBOOK_FILE)) return [];
    return json_decode(file_get_contents(GUESTBOOK_FILE), true) ?? [];
}

function save_entries($entries) {
    file_put_contents(GUESTBOOK_FILE, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$entries      = load_entries();
$entry_count  = count($entries);
$edit_target  = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$done         = $_GET['done'] ?? null;
$authed       = !empty($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $authed ? 'ADMIN // GUESTBOOK' : 'ACCESS' ?> — Jesse.Ventures</title>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'JetBrains Mono', monospace;
      background: #0a0a0a;
      color: #FFB000;
      min-height: 100vh;
      padding: 2.5rem 1.5rem;
      -webkit-font-smoothing: antialiased;
    }

    body::after {
      content: '';
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: repeating-linear-gradient(
        0deg, transparent, transparent 2px,
        rgba(0,0,0,0.08) 2px, rgba(0,0,0,0.08) 4px
      );
      pointer-events: none;
      z-index: 100;
    }

    .wrap { max-width: 580px; margin: 0 auto; }

    .border-line { color: #00FF41; font-size: 0.78rem; opacity: 0.7; user-select: none; }
    h1 { font-size: 1.0rem; font-weight: 700; color: #FFB000; letter-spacing: 0.1em; margin: 0.5rem 0; text-shadow: 0 0 10px rgba(255,176,0,0.4); }
    .divider { border: none; border-top: 1px dashed #2a1f06; margin: 1.25rem 0; }

    .label {
      font-size: 0.52rem;
      color: #00FF41;
      opacity: 0.5;
      letter-spacing: 0.18em;
      margin-bottom: 0.5rem;
    }

    /* ---- CODE GATE ---- */
    .gate {
      margin-top: 2rem;
      border: 1px solid #2a1f06;
      padding: 1.25rem;
      background: #0d0c09;
    }

    .gate-prompt {
      font-size: 0.68rem;
      color: #00FF41;
      opacity: 0.5;
      letter-spacing: 0.1em;
      margin-bottom: 1rem;
    }

    .code-row {
      display: flex;
      gap: 0.75rem;
      align-items: center;
    }

    .code-input {
      background: transparent;
      border: none;
      border-bottom: 1px solid #2a1f06;
      color: #FFB000;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.9rem;
      letter-spacing: 0.18em;
      padding: 0.25rem 0.5rem;
      outline: none;
      flex: 1;
      transition: border-color 0.2s;
    }
    .code-input:focus { border-bottom-color: #FFB000; }
    .code-input::placeholder { color: #2a1f06; letter-spacing: 0.08em; }

    .btn {
      background: transparent;
      border: 1px solid #2a1f06;
      color: #FFB000;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.68rem;
      letter-spacing: 0.1em;
      padding: 0.4rem 0.9rem;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
      white-space: nowrap;
    }
    .btn:hover { border-color: #FFB000; background: #1a1200; }
    .btn-danger { border-color: #3a0a0a; color: #cc3333; }
    .btn-danger:hover { border-color: #cc3333; background: #1a0505; }
    .btn-green { border-color: #0a2a0a; color: #00FF41; }
    .btn-green:hover { border-color: #00FF41; background: #051205; }

    /* ---- STATUS BAR ---- */
    .status-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }

    .status-info { font-size: 0.62rem; color: #5a3e0a; letter-spacing: 0.08em; }
    .status-info span { color: #FFB000; }

    /* ---- FLASH ---- */
    .flash {
      font-size: 0.65rem;
      padding: 0.5rem 0.75rem;
      margin-bottom: 1rem;
      letter-spacing: 0.08em;
      border: 1px solid #0a2a0a;
      color: #00FF41;
      background: #050e05;
    }
    .flash::before { content: '> '; }

    /* ---- ENTRY CARDS ---- */
    .entry-card {
      border: 1px solid #1a1400;
      background: #0d0c09;
      padding: 0.75rem;
      margin-bottom: 0.6rem;
    }
    .entry-card:hover { border-color: #2a1f06; }

    .entry-meta {
      display: flex;
      align-items: baseline;
      gap: 0.75rem;
      margin-bottom: 0.4rem;
      flex-wrap: wrap;
    }
    .entry-num  { font-size: 0.55rem; color: #2a1f06; }
    .entry-name { font-size: 0.82rem; font-weight: 700; color: #FFB000; }
    .entry-time { font-size: 0.52rem; color: #3a2a08; margin-left: auto; }

    .entry-message {
      font-size: 0.72rem;
      color: #c89a14;
      line-height: 1.6;
      white-space: pre-wrap;
      word-break: break-word;
      margin-bottom: 0.6rem;
    }

    .entry-actions { display: flex; gap: 0.5rem; }

    /* ---- EDIT FORM ---- */
    .edit-form {
      margin-top: 0.75rem;
      border-top: 1px dashed #2a1f06;
      padding-top: 0.75rem;
    }

    .edit-input {
      background: #0a0a0a;
      border: 1px solid #2a1f06;
      color: #FFB000;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.72rem;
      padding: 0.35rem 0.5rem;
      outline: none;
      width: 100%;
      margin-bottom: 0.5rem;
      transition: border-color 0.2s;
    }
    .edit-input:focus { border-color: #FFB000; }

    textarea.edit-input {
      min-height: 70px;
      resize: vertical;
      line-height: 1.6;
    }

    .edit-actions { display: flex; gap: 0.5rem; }

    /* ---- CLEAR ALL ---- */
    .danger-zone {
      margin-top: 2rem;
      border: 1px solid #3a0a0a;
      padding: 0.75rem;
      background: #0d0505;
    }

    .danger-label {
      font-size: 0.52rem;
      color: #cc3333;
      opacity: 0.6;
      letter-spacing: 0.18em;
      margin-bottom: 0.6rem;
    }

    .danger-row { display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }

    .danger-input {
      background: transparent;
      border: none;
      border-bottom: 1px solid #3a0a0a;
      color: #cc3333;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.72rem;
      letter-spacing: 0.12em;
      padding: 0.2rem 0.4rem;
      outline: none;
      width: 140px;
      transition: border-color 0.2s;
    }
    .danger-input::placeholder { color: #3a0a0a; }
    .danger-input:focus { border-bottom-color: #cc3333; }

    .danger-hint { font-size: 0.55rem; color: #3a0a0a; letter-spacing: 0.08em; }

    /* ---- NAV ---- */
    .nav-row {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
      flex-wrap: wrap;
    }

    a.nav-link {
      font-size: 0.68rem;
      color: #00FF41;
      text-decoration: none;
      opacity: 0.5;
      transition: opacity 0.2s;
    }
    a.nav-link:hover { opacity: 1; }

    .empty-state {
      font-size: 0.68rem;
      color: #2a1f06;
      letter-spacing: 0.08em;
      padding: 1.25rem 0;
    }
    .empty-state::before { content: '> '; }

    .blink { animation: blink 1s step-end infinite; }
    @keyframes blink { from, to { opacity: 1; } 50% { opacity: 0; } }
  </style>
</head>
<body>
<div class="wrap">

<?php if (!$authed): ?>

  <!-- ========== CODE GATE ========== -->
  <div class="border-line">══════════════════════════════════</div>
  <h1>RESTRICTED ACCESS</h1>
  <div class="border-line">══════════════════════════════════</div>

  <div class="gate">
    <div class="gate-prompt">// ENTER ACCESS CODE TO CONTINUE</div>
    <form method="POST">
      <div class="code-row">
        <span style="font-size:0.68rem; color:#00FF41; opacity:0.5;">CODE &gt;</span>
        <input class="code-input" type="password" name="code" placeholder="________" autocomplete="off" autofocus>
        <button class="btn" type="submit">[ ENTER ]</button>
      </div>
    </form>
  </div>

<?php else: ?>

  <!-- ========== ADMIN PANEL ========== -->
  <div class="border-line">══════════════════════════════════</div>
  <h1>GUESTBOOK ADMIN</h1>
  <div class="border-line">══════════════════════════════════</div>

  <hr class="divider">

  <?php if ($done === 'deleted'): ?>
    <div class="flash">ENTRY DELETED.</div>
  <?php elseif ($done === 'saved'): ?>
    <div class="flash">ENTRY UPDATED.</div>
  <?php elseif ($done === 'cleared'): ?>
    <div class="flash">ALL ENTRIES CLEARED.</div>
  <?php endif; ?>

  <div class="status-bar">
    <div class="status-info">
      <span><?= $entry_count ?></span> <?= $entry_count === 1 ? 'ENTRY' : 'ENTRIES' ?> IN LOG
    </div>
  </div>

  <?php if (empty($entries)): ?>
    <div class="empty-state">guestbook is empty. <span class="blink">_</span></div>
  <?php else: ?>
    <?php foreach (array_reverse($entries) as $entry): ?>
    <div class="entry-card">
      <div class="entry-meta">
        <span class="entry-num">#<?= str_pad($entry['id'], 3, '0', STR_PAD_LEFT) ?></span>
        <span class="entry-name"><?= htmlspecialchars($entry['name']) ?></span>
        <span class="entry-time"><?= htmlspecialchars($entry['timestamp']) ?></span>
      </div>
      <div class="entry-message"><?= htmlspecialchars($entry['message']) ?></div>

      <div class="entry-actions">
        <a class="btn" href="admin.php?edit=<?= $entry['id'] ?>">[EDIT]</a>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this entry?')">
          <input type="hidden" name="delete_id" value="<?= $entry['id'] ?>">
          <button class="btn btn-danger" type="submit">[DELETE]</button>
        </form>
      </div>

      <?php if ($edit_target === (int)$entry['id']): ?>
      <div class="edit-form">
        <form method="POST">
          <input type="hidden" name="edit_id" value="<?= $entry['id'] ?>">
          <input class="edit-input" type="text" name="edit_name" value="<?= htmlspecialchars($entry['name']) ?>" maxlength="100">
          <textarea class="edit-input" name="edit_message" maxlength="1000"><?= htmlspecialchars($entry['message']) ?></textarea>
          <div class="edit-actions">
            <button class="btn btn-green" type="submit">[SAVE]</button>
            <a class="btn" href="admin.php">[CANCEL]</a>
          </div>
        </form>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- DANGER ZONE -->
  <div class="danger-zone">
    <div class="danger-label">// DANGER ZONE</div>
    <form method="POST" onsubmit="return confirm('This will delete ALL entries. Are you sure?')">
      <div class="danger-row">
        <span class="danger-hint">TYPE CONFIRM TO CLEAR ALL &gt;</span>
        <input class="danger-input" type="text" name="clear_all" placeholder="CONFIRM" autocomplete="off">
        <button class="btn btn-danger" type="submit">[CLEAR ALL]</button>
      </div>
    </form>
  </div>

<?php endif; ?>

  <div class="nav-row">
    <a class="nav-link" href="/contact/">[&larr; CONTACT]</a>
    <?php if ($authed): ?>
    <a class="nav-link" href="admin.php?logout=1">[LOGOUT]</a>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
