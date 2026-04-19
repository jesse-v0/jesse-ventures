<?php
// ============================================================
// GUESTBOOK HANDLER
// ============================================================
$guestbook_file = __DIR__ . '/guestbook.json';

function load_entries($file) {
    if (!file_exists($file)) return [];
    $data = file_get_contents($file);
    return json_decode($data, true) ?? [];
}

function save_entries($file, $entries) {
    file_put_contents($file, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim(strip_tags($_POST['name']    ?? ''));
    $message = trim(strip_tags($_POST['message'] ?? ''));

    if (empty($name) || empty($message)) {
        $error = 'NAME and MESSAGE are both required.';
    } elseif (strlen($name) > 100) {
        $error = 'Name exceeds 100 character limit.';
    } elseif (strlen($message) > 1000) {
        $error = 'Message exceeds 1000 character limit.';
    } else {
        $entries   = load_entries($guestbook_file);
        $entries[] = [
            'id'        => count($entries) + 1,
            'name'      => $name,
            'message'   => $message,
            'timestamp' => gmdate('Y-m-d H:i') . ' UTC',
        ];
        save_entries($guestbook_file, $entries);
        // PRG: redirect to prevent duplicate submit on refresh
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?sent=1');
        exit;
    }
}

$entries         = load_entries($guestbook_file);
$entries_display = array_reverse($entries); // newest first
$sent            = isset($_GET['sent']);
$entry_count     = count($entries);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact — Jesse.Ventures</title>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'JetBrains Mono', 'Consolas', monospace;
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

    .wrap { max-width: 560px; margin: 0 auto; }

    /* ---- HEADER ---- */
    .border-line {
      color: #00FF41;
      font-size: 0.78rem;
      letter-spacing: 0.04em;
      opacity: 0.8;
      user-select: none;
    }

    h1 {
      font-size: 1.1rem;
      font-weight: 700;
      color: #FFB000;
      letter-spacing: 0.1em;
      margin: 0.5rem 0;
      text-shadow: 0 0 10px rgba(255,176,0,0.4);
    }

    .divider { border: none; border-top: 1px dashed #2a1f06; margin: 1.5rem 0; }

    /* ---- PHOTO ---- */
    .photo-block { margin: 1.5rem 0 0.5rem; }

    .photo-frame {
      display: inline-block;
      background: #f5f0e8;
      padding: 10px 10px 32px 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.6), inset 0 0 0 1px rgba(0,0,0,0.08);
      transform: rotate(-1.2deg);
      position: relative;
      max-width: 260px;
    }

    .photo-frame img {
      display: block;
      width: 100%;
      filter: sepia(0.15) contrast(0.95) brightness(0.97);
    }

    .photo-caption {
      position: absolute;
      bottom: 0; left: 0; right: 0;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.46rem;
      color: #3a2a10;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      padding: 0 8px;
      text-align: center;
    }

    /* ---- IDENT ---- */
    .ident-block { margin-top: 1.25rem; }

    .label {
      font-size: 0.52rem;
      color: #00FF41;
      opacity: 0.5;
      letter-spacing: 0.18em;
      margin-bottom: 0.2rem;
    }

    .ident-name {
      font-size: 1.5rem;
      font-weight: 700;
      color: #FFB000;
      text-shadow: 0 0 12px rgba(255,176,0,0.35);
      letter-spacing: 0.06em;
      line-height: 1.1;
    }

    .ident-email { margin-top: 0.75rem; }

    .ident-email a {
      font-size: 0.82rem;
      color: #FFB000;
      text-decoration: none;
      transition: text-shadow 0.2s;
    }
    .ident-email a:hover { text-shadow: 0 0 10px rgba(255,176,0,0.7); }

    /* ---- GUESTBOOK FORM ---- */
    .section-label {
      font-size: 0.58rem;
      color: #00FF41;
      opacity: 0.55;
      letter-spacing: 0.18em;
      margin-bottom: 0.75rem;
    }

    .terminal-form {
      border: 1px solid #2a1f06;
      padding: 1rem;
      background: #0d0c09;
    }

    .field-row {
      display: grid;
      grid-template-columns: 90px 1fr;
      align-items: center;
      margin-bottom: 0.6rem;
      gap: 0.5rem;
    }

    .field-prompt {
      font-size: 0.68rem;
      color: #00FF41;
      opacity: 0.5;
      letter-spacing: 0.08em;
      white-space: nowrap;
    }

    .field-input {
      background: transparent;
      border: none;
      border-bottom: 1px solid #2a1f06;
      color: #FFB000;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.78rem;
      padding: 0.15rem 0.25rem;
      outline: none;
      width: 100%;
      transition: border-color 0.2s;
    }
    .field-input:focus { border-bottom-color: #FFB000; }
    .field-input::placeholder { color: #2a1f06; opacity: 1; }

    .message-area { margin-top: 0.75rem; }
    .message-area .field-prompt { margin-bottom: 0.35rem; display: block; }

    textarea.field-input {
      border: 1px solid #2a1f06;
      display: block;
      width: 100%;
      min-height: 80px;
      resize: vertical;
      padding: 0.4rem 0.5rem;
      line-height: 1.6;
    }
    textarea.field-input:focus { border-color: #FFB000; }

    .form-footer {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-top: 1rem;
      flex-wrap: wrap;
    }

    .send-btn {
      background: transparent;
      border: 1px solid #2a1f06;
      color: #FFB000;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.72rem;
      letter-spacing: 0.1em;
      padding: 0.45rem 1.2rem;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
    }
    .send-btn:hover { border-color: #FFB000; background: #1a1200; }

    .error-msg {
      font-size: 0.65rem;
      color: #ff4444;
      opacity: 0.85;
      letter-spacing: 0.05em;
    }
    .error-msg::before { content: '! '; }

    /* ---- SUCCESS FLASH ---- */
    .success-flash {
      border: 1px solid #00FF41;
      background: #0a120a;
      padding: 0.6rem 0.9rem;
      font-size: 0.68rem;
      color: #00FF41;
      letter-spacing: 0.08em;
      margin-bottom: 1rem;
      opacity: 0.85;
    }
    .success-flash::before { content: '> '; }

    /* ---- GUESTBOOK LOG ---- */
    .log-header {
      display: flex;
      align-items: baseline;
      gap: 0.75rem;
      margin-bottom: 0.75rem;
    }

    .log-count {
      font-size: 0.52rem;
      color: #00FF41;
      opacity: 0.35;
      letter-spacing: 0.12em;
    }

    .gb-empty {
      font-size: 0.68rem;
      color: #2a1f06;
      letter-spacing: 0.08em;
      padding: 1rem 0;
    }
    .gb-empty::before { content: '> '; }

    .gb-entry {
      border-bottom: 1px solid #1a1200;
      padding: 0.75rem 0;
    }
    .gb-entry:last-child { border-bottom: none; }

    .gb-meta {
      display: flex;
      gap: 1.25rem;
      align-items: baseline;
      margin-bottom: 0.3rem;
      flex-wrap: wrap;
    }

    .gb-num {
      font-size: 0.55rem;
      color: #2a1f06;
      letter-spacing: 0.08em;
      min-width: 32px;
    }

    .gb-name {
      font-size: 0.78rem;
      color: #FFB000;
      font-weight: 700;
      letter-spacing: 0.04em;
    }

    .gb-time {
      font-size: 0.55rem;
      color: #3a2a08;
      letter-spacing: 0.08em;
      margin-left: auto;
    }

    .gb-message {
      font-size: 0.72rem;
      color: #c89a14;
      line-height: 1.65;
      padding-left: 0;
      white-space: pre-wrap;
      word-break: break-word;
    }

    /* ---- BACK ---- */
    .owner-link {
      display: inline-block;
      margin-top: 2.5rem;
      font-size: 0.58rem;
      color: #2a1f06;
      text-decoration: none;
      letter-spacing: 0.08em;
      transition: color 0.2s;
    }
    .owner-link:hover { color: #5a3e0a; }

    .back-link {
      display: inline-block;
      margin-top: 2.5rem;
      font-size: 0.72rem;
      color: #00FF41;
      text-decoration: none;
      opacity: 0.6;
      transition: opacity 0.2s;
    }
    .back-link:hover { opacity: 1; }

    /* ---- RESPONSIVE ---- */
    @media (max-width: 500px) {
      .photo-frame { max-width: 200px; }
      .ident-name { font-size: 1.2rem; }
      .gb-time { margin-left: 0; }
    }
  </style>
</head>
<body>
<div class="wrap">

  <div class="border-line">══════════════════════════════════</div>
  <h1>CONTACT</h1>
  <div class="border-line">══════════════════════════════════</div>

  <hr class="divider">

  <!-- PHOTO + IDENT -->
  <div style="display:flex; gap:1.75rem; align-items:flex-start; flex-wrap:wrap;">

    <div class="photo-block">
      <div class="photo-frame">
        <img src="how-to-get-there.jpg" alt="Jesse, December 1994">
        <div class="photo-caption">Beijing &mdash; December 1994 &mdash; figuring out how to get there</div>
      </div>
    </div>

    <div class="ident-block">
      <div class="label">// OPERATOR</div>
      <div class="ident-name">JESSE</div>

      <div class="ident-email" style="margin-top:1rem;">
        <div class="label">// EMAIL</div>
        jesse.lunsford@gmail.com
      </div>
    </div>

  </div>

  <hr class="divider">

  <!-- GUESTBOOK FORM -->
  <div class="section-label">// GUESTBOOK &mdash; LEAVE A MESSAGE</div>

  <?php if ($sent): ?>
  <div class="success-flash">MESSAGE LOGGED. THANKS FOR STOPPING BY.</div>
  <?php endif; ?>

  <form class="terminal-form" method="POST" action="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?')) ?>">
    <div class="field-row">
      <span class="field-prompt">NAME &gt;</span>
      <input class="field-input" type="text" name="name" maxlength="100"
             placeholder="required" autocomplete="off" spellcheck="false"
             value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    </div>
    <div class="message-area">
      <span class="field-prompt">MESSAGE &gt;</span>
      <textarea class="field-input" name="message" maxlength="1000"
                placeholder="required" spellcheck="false"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
    </div>
    <div class="form-footer">
      <button class="send-btn" type="submit">[ SIGN GUESTBOOK ]</button>
      <?php if ($error): ?>
      <span class="error-msg"><?= htmlspecialchars($error) ?></span>
      <?php endif; ?>
    </div>
  </form>

  <hr class="divider">

  <!-- GUESTBOOK ENTRIES -->
  <div class="log-header">
    <div class="section-label" style="margin-bottom:0;">// LOG</div>
    <div class="log-count"><?= $entry_count ?> <?= $entry_count === 1 ? 'ENTRY' : 'ENTRIES' ?></div>
  </div>

  <?php if (empty($entries_display)): ?>
    <div class="gb-empty">no entries yet. be the first.</div>
  <?php else: ?>
    <?php foreach ($entries_display as $entry): ?>
    <div class="gb-entry">
      <div class="gb-meta">
        <span class="gb-num">#<?= str_pad($entry['id'], 3, '0', STR_PAD_LEFT) ?></span>
        <span class="gb-name"><?= htmlspecialchars($entry['name']) ?></span>
        <span class="gb-time"><?= htmlspecialchars($entry['timestamp']) ?></span>
      </div>
      <div class="gb-message"><?= htmlspecialchars($entry['message']) ?></div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <a class="back-link" href="/">[&larr; jesse.ventures]</a>
  <a class="owner-link" href="admin.php">[site owner]</a>

</div>
</body>
</html>
