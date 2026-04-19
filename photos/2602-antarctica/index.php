<?php
// ============================================================
// PHOTO GALLERY — AUTO-READS web/ FOLDER
// ============================================================
$web_dir   = __DIR__ . '/web';
$thumb_dir = __DIR__ . '/web/thumbs';
$meta_file = __DIR__ . '/meta.json';

// Load album metadata
$meta = file_exists($meta_file)
    ? (json_decode(file_get_contents($meta_file), true) ?? [])
    : [];

$title    = $meta['title']    ?? 'Photo Journal';
$subtitle = $meta['subtitle'] ?? '';
$date     = $meta['date']     ?? '';
$desc     = $meta['description'] ?? '';

// Scan web/ for numbered images (001.jpg etc), exclude cover.jpg and thumbs/
$images = [];
if (is_dir($web_dir)) {
    $files = scandir($web_dir);
    foreach ($files as $f) {
        if (preg_match('/^\d{3}\.jpg$/i', $f)) {
            $images[] = $f;
        }
    }
    sort($images);
}

$count = count($images);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?> — Photos — Jesse.Ventures</title>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: #080808;
      font-family: 'JetBrains Mono', monospace;
      color: #c8a96e;
      min-height: 100vh;
      padding: 2rem 1.5rem;
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

    .wrap { max-width: 1100px; margin: 0 auto; }

    /* ---- HEADER ---- */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      flex-wrap: wrap;
      gap: 0.75rem;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #1a1400;
    }

    .header-left {}

    .album-eyebrow {
      font-size: 0.52rem;
      color: #39ff14;
      opacity: 0.45;
      letter-spacing: 0.2em;
      margin-bottom: 0.35rem;
    }

    .album-title {
      font-size: 1.6rem;
      font-weight: 700;
      color: #e8c87a;
      letter-spacing: 0.06em;
      line-height: 1;
      text-shadow: 0 0 30px rgba(212,160,23,0.2);
    }

    .album-meta {
      font-size: 0.6rem;
      color: #5a3e0a;
      letter-spacing: 0.12em;
      margin-top: 0.4rem;
    }

    .album-desc {
      font-size: 0.62rem;
      color: #7a5a20;
      letter-spacing: 0.08em;
      margin-top: 0.25rem;
    }

    .header-right {
      text-align: right;
    }

    .photo-count {
      font-size: 2rem;
      font-weight: 700;
      color: #d4a017;
      line-height: 1;
    }

    .photo-count-label {
      font-size: 0.5rem;
      color: #39ff14;
      opacity: 0.4;
      letter-spacing: 0.15em;
    }

    /* ---- GRID ---- */
    .photo-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 4px;
    }

    .photo-thumb {
      aspect-ratio: 4/3;
      overflow: hidden;
      cursor: pointer;
      position: relative;
      background: #111;
    }

    .photo-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.3s ease, opacity 0.3s ease;
      opacity: 0.88;
    }

    .photo-thumb:hover img {
      transform: scale(1.03);
      opacity: 1;
    }

    .photo-thumb .thumb-num {
      position: absolute;
      bottom: 0.3rem;
      right: 0.4rem;
      font-size: 0.45rem;
      color: rgba(255,255,255,0.3);
      letter-spacing: 0.08em;
      z-index: 2;
    }

    /* ---- LIGHTBOX ---- */
    .lightbox {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(4,4,4,0.97);
      z-index: 500;
      align-items: center;
      justify-content: center;
    }

    .lightbox.active { display: flex; }

    .lb-img-wrap {
      position: relative;
      max-width: calc(100vw - 120px);
      max-height: calc(100vh - 80px);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .lb-img-wrap img {
      max-width: 100%;
      max-height: calc(100vh - 80px);
      object-fit: contain;
      display: block;
    }

    .lb-prev, .lb-next {
      position: fixed;
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      border: 1px solid #2a1f06;
      color: #d4a017;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.72rem;
      padding: 0.75rem 0.9rem;
      cursor: pointer;
      z-index: 502;
      transition: border-color 0.2s, background 0.2s;
      letter-spacing: 0.1em;
    }
    .lb-prev { left: 1rem; }
    .lb-next { right: 1rem; }
    .lb-prev:hover, .lb-next:hover { border-color: #d4a017; background: #1a1200; }

    .lb-close {
      position: fixed;
      top: 1rem;
      right: 1rem;
      background: transparent;
      border: 1px solid #2a1f06;
      color: #d4a017;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.65rem;
      padding: 0.4rem 0.75rem;
      cursor: pointer;
      z-index: 502;
      letter-spacing: 0.1em;
      transition: border-color 0.2s, background 0.2s;
    }
    .lb-close:hover { border-color: #d4a017; background: #1a1200; }

    .lb-counter {
      position: fixed;
      bottom: 1.25rem;
      left: 50%;
      transform: translateX(-50%);
      font-size: 0.58rem;
      color: #5a3e0a;
      letter-spacing: 0.15em;
      z-index: 502;
    }

    /* ---- FOOTER NAV ---- */
    .page-footer {
      margin-top: 2rem;
      padding-top: 1rem;
      border-top: 1px solid #1a1400;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    a.nav-link {
      font-size: 0.68rem;
      color: #39ff14;
      text-decoration: none;
      opacity: 0.5;
      letter-spacing: 0.08em;
      transition: opacity 0.2s;
    }
    a.nav-link:hover { opacity: 1; }

    /* ---- RESPONSIVE ---- */
    @media (max-width: 800px) {
      .photo-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 520px) {
      .photo-grid { grid-template-columns: repeat(2, 1fr); gap: 3px; }
      .lb-prev, .lb-next { padding: 0.5rem 0.6rem; }
      .lb-img-wrap { max-width: calc(100vw - 80px); }
      .album-title { font-size: 1.2rem; }
    }
  </style>
</head>
<body>
<div class="wrap">

  <div class="page-header">
    <div class="header-left">
      <div class="album-eyebrow">// PHOTO JOURNAL</div>
      <div class="album-title"><?= htmlspecialchars(strtoupper($title)) ?></div>
      <?php if ($date): ?>
      <div class="album-meta"><?= htmlspecialchars(strtoupper($date)) ?></div>
      <?php endif; ?>
      <?php if ($desc): ?>
      <div class="album-desc"><?= htmlspecialchars($desc) ?></div>
      <?php endif; ?>
    </div>
    <div class="header-right">
      <div class="photo-count"><?= $count ?></div>
      <div class="photo-count-label">PHOTOS</div>
    </div>
  </div>

  <!-- PHOTO GRID -->
  <div class="photo-grid" id="grid">
    <?php foreach ($images as $i => $img):
      $num = str_pad($i + 1, 3, '0', STR_PAD_LEFT);
      $thumb_exists = file_exists($thumb_dir . '/' . $img);
      $thumb_src = $thumb_exists ? 'web/thumbs/' . $img : 'web/' . $img;
    ?>
    <div class="photo-thumb" onclick="openLightbox(<?= $i ?>)">
      <img src="<?= $thumb_src ?>" alt="Photo <?= $num ?>" loading="lazy">
      <span class="thumb-num"><?= $num ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="page-footer">
    <a class="nav-link" href="/photos/">[&larr; ALL ALBUMS]</a>
    <a class="nav-link" href="/antarctica/">[&larr; EXPEDITION LOG]</a>
  </div>

</div>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox">
  <button class="lb-close" onclick="closeLightbox()">[&times; CLOSE]</button>
  <button class="lb-prev" onclick="shiftLightbox(-1)">[&lsaquo; PREV]</button>
  <div class="lb-img-wrap">
    <img id="lb-img" src="" alt="">
  </div>
  <button class="lb-next" onclick="shiftLightbox(1)">[NEXT &rsaquo;]</button>
  <div class="lb-counter" id="lb-counter"></div>
</div>

<script>
  var images = <?= json_encode(array_values($images)) ?>;
  var current = 0;

  function openLightbox(idx) {
    current = idx;
    updateLightbox();
    document.getElementById('lightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
  }

  function shiftLightbox(dir) {
    current = (current + dir + images.length) % images.length;
    updateLightbox();
  }

  function updateLightbox() {
    document.getElementById('lb-img').src = 'web/' + images[current];
    document.getElementById('lb-counter').textContent =
      (current + 1) + ' / ' + images.length;
  }

  document.addEventListener('keydown', function(e) {
    var lb = document.getElementById('lightbox');
    if (!lb.classList.contains('active')) return;
    if (e.key === 'ArrowLeft')  shiftLightbox(-1);
    if (e.key === 'ArrowRight') shiftLightbox(1);
    if (e.key === 'Escape')     closeLightbox();
  });

  document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this) closeLightbox();
  });
</script>

</body>
</html>
