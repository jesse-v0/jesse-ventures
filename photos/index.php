<?php
// ============================================================
// PHOTO JOURNALS HUB — AUTO-DISCOVERS ALBUMS
// Each album = subfolder with meta.json + web/cover.jpg
// ============================================================

$base_dir = __DIR__;
$albums   = [];

// Scan for subdirectories containing meta.json
$dirs = scandir($base_dir);
foreach ($dirs as $d) {
    if ($d === '.' || $d === '..') continue;
    $path = $base_dir . '/' . $d;
    if (!is_dir($path)) continue;
    $meta_path = $path . '/meta.json';
    if (!file_exists($meta_path)) continue;

    $meta = json_decode(file_get_contents($meta_path), true) ?? [];

    // Count photos in web/
    $photo_count = 0;
    $web_path = $path . '/web';
    if (is_dir($web_path)) {
        $files = scandir($web_path);
        foreach ($files as $f) {
            if (preg_match('/^\d{3}\.jpg$/i', $f)) $photo_count++;
        }
    }

    // Check for cover
    $cover_src = null;
    if (file_exists($web_path . '/cover.jpg')) {
        $cover_src = $d . '/web/cover.jpg';
    }

    $albums[] = [
        'slug'    => $d,
        'title'   => $meta['title']    ?? $d,
        'subtitle'=> $meta['subtitle'] ?? '',
        'date'    => $meta['date']     ?? '',
        'location'=> $meta['location'] ?? '',
        'desc'    => $meta['description'] ?? '',
        'cover'   => $cover_src,
        'count'   => $photo_count,
    ];
}

// Sort newest first (by folder name, descending)
usort($albums, function($a, $b) {
    return strcmp($b['slug'], $a['slug']);
});

$total_albums = count($albums);
$total_photos = array_sum(array_column($albums, 'count'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Photo Journals — Jesse.Ventures</title>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: #080808;
      font-family: 'JetBrains Mono', monospace;
      color: #c8a96e;
      min-height: 100vh;
      padding: 2.5rem 1.5rem;
      -webkit-font-smoothing: antialiased;
    }

    body::after {
      content: '';
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: repeating-linear-gradient(
        0deg, transparent, transparent 3px,
        rgba(0,0,0,0.04) 3px, rgba(0,0,0,0.04) 4px
      );
      pointer-events: none;
      z-index: 100;
    }

    .wrap { max-width: 960px; margin: 0 auto; }

    /* ---- MASTHEAD ---- */
    .masthead {
      margin-bottom: 2.5rem;
      padding-bottom: 1.25rem;
      border-bottom: 1px solid #1a1400;
    }

    .site-eyebrow {
      font-size: 0.52rem;
      color: #39ff14;
      opacity: 0.4;
      letter-spacing: 0.22em;
      margin-bottom: 0.4rem;
    }

    .page-title {
      font-size: 2rem;
      font-weight: 700;
      color: #e8c87a;
      letter-spacing: 0.06em;
      line-height: 1;
      text-shadow: 0 0 40px rgba(212,160,23,0.15);
    }

    .page-stats {
      font-size: 0.58rem;
      color: #5a3e0a;
      letter-spacing: 0.12em;
      margin-top: 0.5rem;
    }

    .page-stats span { color: #d4a017; }

    /* ---- ALBUM GRID ---- */
    .album-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.25rem;
    }

    .album-card {
      text-decoration: none;
      display: block;
      position: relative;
    }

    .album-cover {
      width: 100%;
      aspect-ratio: 4/3;
      overflow: hidden;
      background: #111;
      position: relative;
      border: 1px solid #1a1400;
    }

    .album-cover img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      opacity: 0.85;
      transition: transform 0.4s ease, opacity 0.3s ease;
    }

    .album-card:hover .album-cover img {
      transform: scale(1.04);
      opacity: 1;
    }

    .album-cover-empty {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.55rem;
      color: #2a1f06;
      letter-spacing: 0.1em;
    }

    /* Photo count badge */
    .album-badge {
      position: absolute;
      bottom: 0.5rem;
      right: 0.5rem;
      background: rgba(8,8,8,0.85);
      border: 1px solid #2a1f06;
      font-size: 0.5rem;
      color: #d4a017;
      letter-spacing: 0.1em;
      padding: 0.2rem 0.45rem;
    }

    .album-info {
      padding: 0.65rem 0 0;
    }

    .album-name {
      font-size: 0.82rem;
      font-weight: 700;
      color: #e8c87a;
      letter-spacing: 0.04em;
      line-height: 1.2;
      transition: color 0.2s;
    }
    .album-card:hover .album-name { color: #FFD060; }

    .album-date {
      font-size: 0.55rem;
      color: #5a3e0a;
      letter-spacing: 0.1em;
      margin-top: 0.2rem;
    }

    .album-loc {
      font-size: 0.55rem;
      color: #3a2a08;
      letter-spacing: 0.08em;
      margin-top: 0.1rem;
    }

    /* ---- EMPTY STATE ---- */
    .empty {
      font-size: 0.72rem;
      color: #2a1f06;
      letter-spacing: 0.08em;
      padding: 2rem 0;
    }
    .empty::before { content: '> '; }

    /* ---- FOOTER ---- */
    .page-footer {
      margin-top: 3rem;
      padding-top: 1rem;
      border-top: 1px solid #1a1400;
    }

    a.back-link {
      font-size: 0.68rem;
      color: #39ff14;
      text-decoration: none;
      opacity: 0.5;
      letter-spacing: 0.08em;
      transition: opacity 0.2s;
    }
    a.back-link:hover { opacity: 1; }

    /* ---- RESPONSIVE ---- */
    @media (max-width: 700px) {
      .album-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
      .page-title { font-size: 1.5rem; }
    }
    @media (max-width: 420px) {
      .album-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<div class="wrap">

  <div class="masthead">
    <div class="site-eyebrow">// JESSE.VENTURES</div>
    <div class="page-title">PHOTO JOURNALS</div>
    <div class="page-stats">
      <span><?= $total_albums ?></span> <?= $total_albums === 1 ? 'ALBUM' : 'ALBUMS' ?> &nbsp;&mdash;&nbsp;
      <span><?= $total_photos ?></span> PHOTOS
    </div>
  </div>

  <?php if (empty($albums)): ?>
    <div class="empty">no albums yet. <span style="color:#2a1f06;">_</span></div>
  <?php else: ?>
    <div class="album-grid">
      <?php foreach ($albums as $album): ?>
      <a class="album-card" href="/photos/<?= htmlspecialchars($album['slug']) ?>/">
        <div class="album-cover">
          <?php if ($album['cover']): ?>
            <img src="<?= htmlspecialchars($album['cover']) ?>" alt="<?= htmlspecialchars($album['title']) ?>">
          <?php else: ?>
            <div class="album-cover-empty">NO COVER</div>
          <?php endif; ?>
          <?php if ($album['count']): ?>
            <div class="album-badge"><?= $album['count'] ?> PHOTOS</div>
          <?php endif; ?>
        </div>
        <div class="album-info">
          <div class="album-name"><?= htmlspecialchars(strtoupper($album['title'])) ?></div>
          <?php if ($album['date']): ?>
            <div class="album-date"><?= htmlspecialchars(strtoupper($album['date'])) ?></div>
          <?php endif; ?>
          <?php if ($album['location']): ?>
            <div class="album-loc"><?= htmlspecialchars(strtoupper($album['location'])) ?></div>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="page-footer">
    <a class="back-link" href="/">[&larr; jesse.ventures]</a>
  </div>

</div>
</body>
</html>
