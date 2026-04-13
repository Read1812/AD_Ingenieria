<?php
// ─── Conexión DB ──────────────────────────────────────────────
require_once __DIR__ . '/../config.php';

$posts = [];
$categorias = [];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    // Solo entradas publicadas, más recientes primero
    $posts = $pdo->query("
        SELECT id, titulo, slug, categoria, resumen, creado_en
        FROM blog_posts
        WHERE publicado = 1
        ORDER BY creado_en DESC
    ")->fetchAll();

    // Categorías únicas para el sidebar
    $cats = $pdo->query("
        SELECT DISTINCT categoria
        FROM blog_posts
        WHERE publicado = 1 AND categoria != ''
        ORDER BY categoria ASC
    ")->fetchAll(PDO::FETCH_COLUMN);
    $categorias = $cats;

} catch (Exception $e) {
    // Si falla la DB, la página igual se muestra vacía (no rompe)
    $posts = [];
    $categorias = [];
}

// ─── Helpers ──────────────────────────────────────────────────
function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function formatFecha(string $fecha): string {
    $meses = ['enero','febrero','marzo','abril','mayo','junio',
              'julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $ts  = strtotime($fecha);
    return $meses[date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

function tiempoLectura(string $contenido): string {
    $palabras = str_word_count(strip_tags($contenido));
    $minutos  = max(1, (int)round($palabras / 200));
    return $minutos . ' min lectura';
}

// Ícono SVG según categoría
function iconoPorCategoria(string $cat): string {
    $map = [
        'Automatización'         => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/><path d="M7 8l3 3-3 3M13 14h4"/>',
        'Seguridad Electrónica'  => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'Redes e Infraestructura'=> '<path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1" fill="rgba(255,255,255,0.5)"/>',
        'Transformación Digital' => '<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>',
        'Seguridad Física'       => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    ];
    $default = '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>';
    return $map[$cat] ?? $default;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
            <!-- SVG moderno -->
    <link rel="icon" type="image/svg+xml" href="/assets/logo-simple.svg">
    
    <!-- PNG fallback -->
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png">
    
    <!-- Apple (iPhone) -->
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">
    
    <!-- Para tema claro/oscuro -->
    <link rel="icon" href="/assets/logo-simple.svg" media="(prefers-color-scheme: light)">
    <link rel="icon" href="/assets/logo-simple.svg" media="(prefers-color-scheme: dark)">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Blog Técnico | AD Ingeniería — Ingeniería y Tecnología en Chile</title>
  <meta name="description" content="Artículos técnicos sobre seguridad electrónica, automatización, Power Apps, infraestructura de redes y transformación digital en Chile. Blog de AD Ingeniería." />
  <link rel="canonical" href="https://adingenieria.cl/blog/" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/assets/shared.css" />
  <style>
    .blog-wrap { max-width: 1100px; margin: 0 auto; padding: 80px 48px; }
    .blog-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 48px; align-items: start; }

    .posts-list { display: flex; flex-direction: column; gap: 32px; }
    .post-card {
      background: var(--bg-soft); border: 1px solid var(--border);
      border-radius: 14px; overflow: hidden;
      display: grid; grid-template-columns: 200px 1fr;
      transition: box-shadow .2s, transform .2s; text-decoration: none; color: inherit;
    }
    .post-card:hover { box-shadow: 0 8px 32px rgba(26,59,204,0.09); transform: translateY(-3px); }
    .post-img {
      background: linear-gradient(135deg, var(--navy), var(--blue));
      display: flex; align-items: center; justify-content: center;
    }
    .post-img svg { width: 40px; height: 40px; stroke: rgba(255,255,255,0.4); fill: none; stroke-width: 1.3; stroke-linecap: round; stroke-linejoin: round; }
    .post-body { padding: 24px 28px; }
    .post-cat { font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--blue); display: block; margin-bottom: 8px; }
    .post-body h2 { font-size: 17px; font-weight: 800; margin-bottom: 10px; line-height: 1.35; }
    .post-body p { font-size: 13.5px; color: var(--muted); line-height: 1.7; margin-bottom: 16px; }
    .post-meta { display: flex; align-items: center; gap: 14px; font-size: 12px; color: var(--muted); margin-bottom: 14px; }
    .post-meta span { display: flex; align-items: center; gap: 4px; }
    .post-meta svg { width: 13px; height: 13px; stroke: var(--muted); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .read-more { font-size: 13px; font-weight: 600; color: var(--blue); display: inline-flex; align-items: center; gap: 5px; transition: gap .2s; }
    .post-card:hover .read-more { gap: 9px; }
    .read-more svg { width: 13px; height: 13px; stroke: var(--blue); fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

    /* Estado vacío */
    .empty-blog { padding: 64px 0; text-align: center; color: var(--muted); }
    .empty-blog svg { width: 48px; height: 48px; stroke: var(--border); fill: none; stroke-width: 1.3; stroke-linecap: round; margin-bottom: 16px; }
    .empty-blog h3 { font-size: 18px; font-weight: 700; margin-bottom: 8px; color: var(--text); }
    .empty-blog p { font-size: 14px; }

    /* Sidebar */
    .sidebar { display: flex; flex-direction: column; gap: 28px; }
    .sidebar-card { background: var(--bg-soft); border: 1px solid var(--border); border-radius: 12px; padding: 24px; }
    .sidebar-card h3 { font-size: 14px; font-weight: 700; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
    .sidebar-topics { list-style: none; }
    .sidebar-topics li { margin-bottom: 8px; }
    .sidebar-topics a { font-size: 13.5px; color: var(--muted); text-decoration: none; display: flex; align-items: center; gap: 8px; transition: color .2s; }
    .sidebar-topics a:hover { color: var(--blue); }
    .sidebar-topics a::before { content: ''; display: block; width: 6px; height: 6px; border-radius: 50%; background: var(--border); flex-shrink: 0; transition: background .2s; }
    .sidebar-topics a:hover::before { background: var(--blue); }
    .sidebar-cta { background: var(--navy); border-radius: 12px; padding: 24px; }
    .sidebar-cta h3 { font-size: 15px; font-weight: 800; color: var(--white); margin-bottom: 10px; }
    .sidebar-cta p { font-size: 13px; color: rgba(255,255,255,0.6); margin-bottom: 18px; line-height: 1.65; }
    .sidebar-cta a { display: block; text-align: center; background: var(--blue); color: white; padding: 10px 20px; border-radius: 7px; font-size: 13.5px; font-weight: 600; text-decoration: none; transition: background .2s; }
    .sidebar-cta a:hover { background: var(--blue-dark); }

    @media (max-width: 768px) {
      .blog-wrap { padding: 56px 20px; }
      .blog-grid { grid-template-columns: 1fr; }
      .post-card { grid-template-columns: 1fr; }
      .post-img { height: 140px; }
    }
  </style>
</head>
<body>

<nav>
  <a href="/" class="nav-logo"><img src="/assets/logo-dark.svg" alt="AD Ingeniería" /></a>
  <ul class="nav-links">
    <li><a href="/">Inicio</a></li>
    <li><a href="/nosotros/">Nosotros</a></li>
    <li><a href="/servicios/">Servicios</a></li>
    <li><a href="/proyectos/">Proyectos</a></li>
    <li><a href="/blog/" class="active">Blog</a></li>
    <li><a href="/contacto/">Contacto</a></li>
  </ul>
  <a href="/contacto/" class="btn-primary">Solicitar Evaluación</a>
</nav>

<section class="page-hero">
  <div class="page-hero-bg" style="background-image:url('https://images.unsplash.com/photo-1432888498266-38ffec3eaf0a?w=1400&q=80');"></div>
  <div class="page-hero-overlay"></div>
  <div class="page-hero-content">
    <div class="page-hero-breadcrumb"><a href="/">Inicio</a><span>/</span> Blog Técnico</div>
    <h1>Blog Técnico</h1>
    <p>Artículos sobre ingeniería, seguridad electrónica, automatización y transformación digital para empresas en Chile.</p>
  </div>
</section>

<section style="background:var(--white);">
  <div class="blog-wrap">
    <div class="blog-grid">

      <!-- LISTA DE POSTS -->
      <div class="posts-list">
        <?php if (empty($posts)): ?>
          <div class="empty-blog">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <h3>Próximamente</h3>
            <p>Estamos preparando contenido técnico. Vuelve pronto.</p>
          </div>
        <?php else: ?>
          <?php foreach ($posts as $post): ?>
            <a href="/blog/<?= esc($post['slug']) ?>/" class="post-card">
              <div class="post-img">
                  <?php if (!empty($post['imagen'])): ?>
                    <img src="<?= esc($post['imagen']) ?>" style="width:100%;height:100%;object-fit:cover;">
                  <?php else: ?>
                    <svg viewBox="0 0 24 24"><?= iconoPorCategoria($post['categoria']) ?></svg>
                  <?php endif; ?>
                </div>
              <div class="post-body">
                <?php if ($post['categoria']): ?>
                  <span class="post-cat"><?= esc($post['categoria']) ?></span>
                <?php endif; ?>
                <h2><?= esc($post['titulo']) ?></h2>
                <?php if ($post['resumen']): ?>
                  <p><?= esc($post['resumen']) ?></p>
                <?php endif; ?>
                <div class="post-meta">
                  <span>
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?= formatFecha($post['creado_en']) ?>
                  </span>
                </div>
                <span class="read-more">
                  Leer artículo
                  <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </span>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- SIDEBAR -->
      <aside class="sidebar">
        <?php if (!empty($categorias)): ?>
        <div class="sidebar-card">
          <h3>Temas</h3>
          <ul class="sidebar-topics">
            <?php foreach ($categorias as $cat): ?>
              <li><a href="/blog/categoria/<?= esc(strtolower(str_replace([' ','/'], ['-','-'], $cat))) ?>/"><?= esc($cat) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <div class="sidebar-cta">
          <h3>¿Tienes un proyecto?</h3>
          <p>Conversamos sobre tu requerimiento sin compromiso. El equipo técnico responde en 24 horas.</p>
          <a href="/contacto/">Solicitar Asesoría</a>
        </div>

        <div class="sidebar-card">
          <h3>Servicios</h3>
          <ul class="sidebar-topics">
            <li><a href="/servicios/seguridad-electronica/">Seguridad Electrónica</a></li>
            <li><a href="/servicios/transformacion-digital/">Automatización y Power Apps</a></li>
            <li><a href="/servicios/redes-infraestructura/">Redes e Infraestructura</a></li>
            <li><a href="/servicios/electronica-iot/">Seguridad Física</a></li>
            <li><a href="/servicios/seguridad-fisica/">Seguridad Física</a></li>
          </ul>
        </div>
      </aside>

    </div>
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
    <div class="footer-col"><h5>Servicios</h5><ul><li><a href="/servicios/seguridad-electronica/">Seguridad Electrónica</a></li><li><a href="/servicios/transformacion-digital/">Transformación Digital</a></li><li><a href="/servicios/redes-infraestructura/">Redes e Infraestructura</a></li><li><a href="/servicios/electronica-iot/">Electrónica e IoT</a></li><li><a href="/servicios/seguridad-fisica/">Seguridad Física</a></li></ul></div>
    <div class="footer-col"><h5>Contacto</h5><ul><li><a href="mailto:contacto@adingenieria.cl">contacto@adingenieria.cl</a></li><li><a href="#">Santiago, Chile</a></li></ul></div>
  </div>
  <div class="footer-bottom">© 2025 AD Ingeniería. Todos los derechos reservados.</div>
</footer>

</body>
</html>
