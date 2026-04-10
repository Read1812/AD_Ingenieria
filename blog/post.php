<?php
// ─── Obtener el slug de la URL ────────────────────────────────
// Hostinger entrega el slug vía .htaccess rewrite como ?slug=xxx
// o directamente como parte del path. Lo leemos de ambas formas.
$slug = trim($_GET['slug'] ?? '', '/');

if (!$slug || !preg_match('/^[a-z0-9-]+$/', $slug)) {
    header('Location: /blog/');
    exit;
}

// ─── Conexión DB ──────────────────────────────────────────────
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=u977895088_adingen_Db;charset=utf8mb4',
        'u977895088_Richard_adm',
        '[REDACTED_DB_PASS]',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $st = $pdo->prepare('SELECT * FROM blog_posts WHERE slug = ? AND publicado = 1 LIMIT 1');
    $st->execute([$slug]);
    $post = $st->fetch();

    if (!$post) {
        header('HTTP/1.0 404 Not Found');
        header('Location: /blog/');
        exit;
    }

    // Posts relacionados (misma categoría, excluyendo el actual)
    $stRel = $pdo->prepare('
        SELECT id, titulo, slug, categoria, creado_en
        FROM blog_posts
        WHERE publicado = 1 AND id != ? AND categoria = ?
        ORDER BY creado_en DESC LIMIT 3
    ');
    $stRel->execute([$post['id'], $post['categoria']]);
    $relacionados = $stRel->fetchAll();

    // Si no hay de la misma categoría, traer los más recientes
    if (empty($relacionados)) {
        $stRec = $pdo->prepare('
            SELECT id, titulo, slug, categoria, creado_en
            FROM blog_posts
            WHERE publicado = 1 AND id != ?
            ORDER BY creado_en DESC LIMIT 3
        ');
        $stRec->execute([$post['id']]);
        $relacionados = $stRec->fetchAll();
    }

} catch (Exception $e) {
    header('Location: /blog/');
    exit;
}

// ─── Helpers ──────────────────────────────────────────────────
function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function formatFechaLarga(string $fecha): string {
    $meses = ['enero','febrero','marzo','abril','mayo','junio',
              'julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $ts = strtotime($fecha);
    return date('j', $ts) . ' de ' . $meses[date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

function tiempoLectura(string $contenido): string {
    $palabras = str_word_count(strip_tags($contenido));
    $minutos  = max(1, (int)round($palabras / 200));
    return $minutos . ' min de lectura';
}

$tiempo = tiempoLectura($post['contenido']);
$fecha  = formatFechaLarga($post['creado_en']);
$resumen = $post['resumen'] ?: strip_tags(substr($post['contenido'], 0, 160)) . '...';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($post['titulo']) ?> | Blog AD Ingeniería</title>
  <meta name="description" content="<?= esc($resumen) ?>" />
  <link rel="canonical" href="https://adingenieria.cl/blog/<?= esc($post['slug']) ?>/" />
  <meta property="og:title" content="<?= esc($post['titulo']) ?>" />
  <meta property="og:description" content="<?= esc($resumen) ?>" />
  <meta property="og:type" content="article" />
  <meta property="og:url" content="https://adingenieria.cl/blog/<?= esc($post['slug']) ?>/" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/assets/shared.css" />
  <style>
    .post-wrap {
      max-width: 1100px; margin: 0 auto; padding: 72px 48px;
      display: grid; grid-template-columns: 1fr 300px; gap: 64px; align-items: start;
    }

    /* Artículo */
    .post-header { margin-bottom: 40px; }
    .post-cat-tag {
      display: inline-block; font-size: 11px; font-weight: 700;
      letter-spacing: 0.12em; text-transform: uppercase; color: var(--blue);
      background: rgba(26,59,204,0.07); border: 1px solid rgba(26,59,204,0.15);
      padding: 4px 12px; border-radius: 100px; margin-bottom: 18px;
    }
    .post-header h1 {
      font-size: clamp(24px, 3vw, 36px); font-weight: 800;
      color: var(--text); line-height: 1.2; margin-bottom: 18px;
    }
    .post-byline {
      display: flex; align-items: center; gap: 18px;
      font-size: 13px; color: var(--muted); flex-wrap: wrap;
    }
    .post-byline span { display: flex; align-items: center; gap: 5px; }
    .post-byline svg { width: 14px; height: 14px; stroke: var(--muted); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .post-divider { border: none; border-top: 1px solid var(--border); margin: 32px 0; }

    /* Contenido del post */
    .post-content { font-size: 15.5px; line-height: 1.8; color: var(--text); }
    .post-content h2 { font-size: 22px; font-weight: 800; margin: 36px 0 14px; line-height: 1.3; }
    .post-content h3 { font-size: 18px; font-weight: 700; margin: 28px 0 12px; line-height: 1.3; }
    .post-content p { margin-bottom: 18px; }
    .post-content ul, .post-content ol { margin: 16px 0 18px 24px; }
    .post-content li { margin-bottom: 8px; }
    .post-content strong { font-weight: 700; }
    .post-content em { font-style: italic; color: var(--muted); }
    .post-content a { color: var(--blue); text-decoration: underline; text-underline-offset: 3px; }
    .post-content a:hover { color: var(--blue-dark); }
    .post-content blockquote {
      border-left: 3px solid var(--blue); padding: 12px 20px;
      color: var(--muted); font-style: italic; margin: 24px 0;
      background: var(--bg-soft); border-radius: 0 8px 8px 0;
    }
    .post-content code {
      background: var(--bg-soft); padding: 2px 6px; border-radius: 4px;
      font-family: monospace; font-size: 13.5px; color: var(--blue-dark);
    }
    .post-content pre {
      background: var(--navy); padding: 20px 24px; border-radius: 10px;
      overflow-x: auto; margin: 20px 0;
    }
    .post-content pre code { background: none; color: rgba(255,255,255,0.85); padding: 0; }

    /* Footer del post */
    .post-footer { margin-top: 48px; padding-top: 28px; border-top: 1px solid var(--border); }
    .post-footer-label { font-size: 12px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); margin-bottom: 12px; }
    .post-back {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 13.5px; font-weight: 600; color: var(--blue);
      text-decoration: none; transition: gap .2s;
    }
    .post-back:hover { gap: 10px; }
    .post-back svg { width: 14px; height: 14px; stroke: var(--blue); fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

    /* Sidebar */
    .post-sidebar { display: flex; flex-direction: column; gap: 24px; }
    .sidebar-card { background: var(--bg-soft); border: 1px solid var(--border); border-radius: 12px; padding: 22px; }
    .sidebar-card h3 { font-size: 13px; font-weight: 700; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid var(--border); }
    .related-item { display: block; text-decoration: none; color: inherit; padding: 10px 0; border-bottom: 1px solid var(--border); transition: color .15s; }
    .related-item:last-child { border-bottom: none; padding-bottom: 0; }
    .related-item:hover h4 { color: var(--blue); }
    .related-item span { font-size: 10.5px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--blue); display: block; margin-bottom: 4px; }
    .related-item h4 { font-size: 13.5px; font-weight: 600; line-height: 1.4; color: var(--text); }
    .sidebar-cta { background: var(--navy); border-radius: 12px; padding: 22px; }
    .sidebar-cta h3 { font-size: 14px; font-weight: 800; color: white; margin-bottom: 8px; }
    .sidebar-cta p { font-size: 12.5px; color: rgba(255,255,255,0.6); margin-bottom: 16px; line-height: 1.6; }
    .sidebar-cta a { display: block; text-align: center; background: var(--blue); color: white; padding: 9px 16px; border-radius: 7px; font-size: 13px; font-weight: 600; text-decoration: none; transition: background .2s; }
    .sidebar-cta a:hover { background: var(--blue-dark); }

    @media (max-width: 768px) {
      .post-wrap { grid-template-columns: 1fr; gap: 40px; padding: 48px 20px; }
    }
  </style>
</head>
<body>

<nav>
  <a href="/" class="nav-logo"><img src="/assets/logo-dark.svg" alt="AD Ingeniería" /></a>
  <ul class="nav-links">
    <li><a href="/">Inicio</a></li>
    <li><a href="/nosotros/">Nosotros</a></li>
    <li><a href="/#servicios">Servicios</a></li>
    <li><a href="/proyectos/">Proyectos</a></li>
    <li><a href="/blog/" class="active">Blog</a></li>
    <li><a href="/contacto/">Contacto</a></li>
  </ul>
  <a href="/contacto/" class="btn-primary">Solicitar Evaluación</a>
</nav>

<!-- Hero compacto -->
<section class="page-hero" style="min-height:200px;">
  <div class="page-hero-bg" style="background-image:url('https://images.unsplash.com/photo-1432888498266-38ffec3eaf0a?w=1400&q=80');"></div>
  <div class="page-hero-overlay"></div>
  <div class="page-hero-content">
    <div class="page-hero-breadcrumb">
      <a href="/">Inicio</a><span>/</span>
      <a href="/blog/">Blog</a><span>/</span>
      <?= esc(mb_strimwidth($post['titulo'], 0, 50, '...')) ?>
    </div>
  </div>
</section>

<section style="background:var(--white);">
  <div class="post-wrap">

    <!-- ARTÍCULO -->
    <article>
      <div class="post-header">
        <?php if ($post['categoria']): ?>
          <span class="post-cat-tag"><?= esc($post['categoria']) ?></span>
        <?php endif; ?>
        <h1><?= esc($post['titulo']) ?></h1>
        <div class="post-byline">
          <span>
            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <?= $fecha ?>
          </span>
          <span>
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <?= $tiempo ?>
          </span>
          <span>
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            AD Ingeniería
          </span>
        </div>
      </div>

      <hr class="post-divider" />
      <?php if (!empty($post['imagen'])): ?>
          <img src="<?= esc($post['imagen']) ?>" 
               style="width:100%; border-radius:14px; margin:24px 0;">
        <?php endif; ?>

      <div class="post-content">
        <?= $post['contenido'] ?>
      </div>

      <div class="post-footer">
        <a href="/blog/" class="post-back">
          <svg viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Volver al Blog
        </a>
      </div>
    </article>

    <!-- SIDEBAR -->
    <aside class="post-sidebar">

      <?php if (!empty($relacionados)): ?>
      <div class="sidebar-card">
        <h3>Artículos relacionados</h3>
        <?php foreach ($relacionados as $rel): ?>
          <a href="/blog/<?= esc($rel['slug']) ?>/" class="related-item">
            <?php if ($rel['categoria']): ?>
              <span><?= esc($rel['categoria']) ?></span>
            <?php endif; ?>
            <h4><?= esc($rel['titulo']) ?></h4>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="sidebar-cta">
        <h3>¿Te interesa este tema?</h3>
        <p>Conversemos sobre cómo aplicarlo en tu empresa. Sin compromiso.</p>
        <a href="/contacto/">Solicitar Asesoría</a>
      </div>

      <div class="sidebar-card">
        <h3>Servicios</h3>
        <?php
        $servicios = [
          'Seguridad Electrónica'      => '/servicios/seguridad-electronica/',
          'Automatización y Power Apps' => '/servicios/automatizacion-power-apps/',
          'Redes e Infraestructura'    => '/servicios/redes-infraestructura/',
          'Seguridad Física'           => '/servicios/seguridad-fisica/',
        ];
        foreach ($servicios as $nombre => $url): ?>
          <a href="<?= $url ?>" class="related-item" style="border-bottom:1px solid var(--border);padding:10px 0;">
            <h4 style="font-size:13.5px;color:var(--text);"><?= $nombre ?></h4>
          </a>
        <?php endforeach; ?>
      </div>

    </aside>
  </div>
</section>

<footer>
  <div class="footer-inner">
    <div class="footer-brand">
      <a href="/" class="nav-logo"><img src="/assets/logo-dark.svg" alt="AD Ingeniería" style="height:22px;filter:brightness(0) invert(1);" /></a>
      <p>Soluciones de Ingeniería y Tecnología. Desarrollamos e implementamos soluciones orientadas a la integración de sistemas, automatización y optimización de procesos.</p>
      <div class="socials"><a href="#" class="social-icon" aria-label="LinkedIn"><svg viewBox="0 0 24 24"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg></a></div>
    </div>
    <div class="footer-col"><h5>Navegación</h5><ul><li><a href="/">Inicio</a></li><li><a href="/nosotros/">Nosotros</a></li><li><a href="/#servicios">Servicios</a></li><li><a href="/proyectos/">Proyectos</a></li><li><a href="/blog/">Blog</a></li><li><a href="/contacto/">Contacto</a></li></ul></div>
    <div class="footer-col"><h5>Servicios</h5><ul><li><a href="/servicios/seguridad-electronica/">Seguridad Electrónica</a></li><li><a href="/servicios/automatizacion-power-apps/">Automatización Digital</a></li><li><a href="/servicios/redes-infraestructura/">Redes e Infraestructura</a></li><li><a href="/servicios/seguridad-fisica/">Seguridad Física</a></li></ul></div>
    <div class="footer-col"><h5>Contacto</h5><ul><li><a href="mailto:contacto@adingenieria.cl">contacto@adingenieria.cl</a></li><li><a href="#">Santiago, Chile</a></li></ul></div>
  </div>
  <div class="footer-bottom">© 2025 AD Ingeniería. Todos los derechos reservados.</div>
</footer>

</body>
</html>
