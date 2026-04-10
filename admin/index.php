<?php
/**
 * AD Ingeniería — Panel de administración de Blog
 * Página no indexada · Solo acceso con usuario y contraseña
 */

// Enviar headers antes de cualquier output para bloquear CDN
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');

// Nonce único por request para CSP
$nonce = base64_encode(random_bytes(16));

// CSP: solo permite scripts con el nonce correcto + Google Fonts
// Esto bloquea cualquier script inyectado por Hostinger CDN
header("Content-Security-Policy: default-src 'self'; script-src 'nonce-{$nonce}' 'strict-dynamic'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; connect-src 'self'; img-src 'self' data:; frame-ancestors 'none';");
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
  <meta name="robots" content="noindex, nofollow, noarchive" />
  <title>Panel de Blog — AD Ingeniería</title>
  <!-- Bloqueamos scripts externos del CDN via CSP con nonce -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --blue:      #1a3bcc;
      --blue-dark: #1530a8;
      --navy:      #0d1b3e;
      --navy-deep: #08122b;
      --text:      #1a1a2e;
      --muted:     #5a6073;
      --bg:        #f4f6fb;
      --bg-mid:    #eef1f9;
      --border:    #e2e6f0;
      --white:     #ffffff;
      --green:     #10b981;
      --red:       #ef4444;
      --amber:     #f59e0b;
    }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; min-height: 100vh; }

    /* ═══════════════════════════════════
       LOGIN
    ═══════════════════════════════════ */
    #screen-login {
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      background: var(--navy-deep);
      background-image: radial-gradient(ellipse 60% 70% at 60% 40%, rgba(26,59,204,0.2) 0%, transparent 70%);
    }
    .login-card {
      background: var(--white); border-radius: 16px; padding: 48px 44px;
      width: 100%; max-width: 400px;
      box-shadow: 0 24px 64px rgba(0,0,0,0.25);
    }
    .login-logo {
      text-align: center; margin-bottom: 32px;
    }
    .login-logo-text {
      font-size: 20px; font-weight: 800; color: var(--navy-deep); display: block; margin-top: 6px;
    }
    .login-logo-sub {
      font-size: 12px; color: var(--muted); display: block; margin-top: 3px; letter-spacing: 0.04em;
    }
    .login-card h2 { font-size: 22px; font-weight: 800; margin-bottom: 6px; }
    .login-card > p { font-size: 13.5px; color: var(--muted); margin-bottom: 28px; }
    .field { margin-bottom: 18px; }
    .field label { display: block; font-size: 12.5px; font-weight: 600; margin-bottom: 5px; }
    .field input {
      width: 100%; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: 8px;
      font-size: 14px; font-family: inherit; outline: none; transition: border-color .2s;
    }
    .field input:focus { border-color: var(--blue); }
    .field input.error { border-color: var(--red); }
    .btn-login {
      width: 100%; background: var(--blue); color: white; border: none;
      padding: 12px 20px; border-radius: 8px; font-size: 15px; font-weight: 700;
      cursor: pointer; font-family: inherit; transition: background .2s; margin-top: 4px;
    }
    .btn-login:hover { background: var(--blue-dark); }
    .btn-login:disabled { opacity: .65; cursor: not-allowed; }
    #login-error {
      display: none; background: #fff5f5; border: 1px solid #fecaca;
      color: #991b1b; padding: 10px 14px; border-radius: 8px;
      font-size: 13px; font-weight: 500; margin-top: 14px; text-align: center;
    }

    /* ═══════════════════════════════════
       LAYOUT ADMIN
    ═══════════════════════════════════ */
    #screen-admin { display: none; min-height: 100vh; }

    .admin-topbar {
      position: sticky; top: 0; z-index: 50;
      background: var(--navy-deep); height: 56px;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 28px; border-bottom: 1px solid rgba(255,255,255,0.07);
    }
    .topbar-brand {
      font-size: 15px; font-weight: 800; color: white;
      display: flex; align-items: center; gap: 10px;
    }
    .topbar-brand-sub { font-size: 11px; color: rgba(255,255,255,0.4); font-weight: 400; }
    .topbar-actions { display: flex; align-items: center; gap: 12px; }
    .btn-new {
      background: var(--blue); color: white; border: none;
      padding: 7px 16px; border-radius: 7px; font-size: 13px;
      font-weight: 600; cursor: pointer; font-family: inherit;
      display: flex; align-items: center; gap: 6px; transition: background .2s;
    }
    .btn-new:hover { background: var(--blue-dark); }
    .btn-new svg { width: 14px; height: 14px; stroke: white; fill: none; stroke-width: 2.5; stroke-linecap: round; }
    .btn-logout {
      background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.7);
      border: 1px solid rgba(255,255,255,0.12);
      padding: 7px 14px; border-radius: 7px; font-size: 12.5px;
      font-weight: 600; cursor: pointer; font-family: inherit; transition: all .2s;
    }
    .btn-logout:hover { background: rgba(255,255,255,0.14); color: white; }
    .btn-change-pass {
      background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
      width: 34px; height: 34px; border-radius: 7px; cursor: pointer;
      display: flex; align-items: center; justify-content: center; transition: all .2s;
    }
    .btn-change-pass:hover { background: rgba(255,255,255,0.14); }
    .btn-change-pass svg { width: 15px; height: 15px; stroke: rgba(255,255,255,0.7); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    /* ═══════════════════════════════════
       DASHBOARD (lista de posts)
    ═══════════════════════════════════ */
    #screen-list {
      max-width: 1000px; margin: 0 auto; padding: 40px 24px;
    }
    .list-header { margin-bottom: 28px; }
    .list-header h1 { font-size: 24px; font-weight: 800; }
    .list-header p { font-size: 14px; color: var(--muted); margin-top: 4px; }

    .posts-table {
      background: var(--white); border: 1px solid var(--border);
      border-radius: 12px; overflow: hidden;
    }
    .posts-table-header {
      display: grid; grid-template-columns: 1fr 140px 100px 110px;
      padding: 12px 20px; background: var(--bg);
      border-bottom: 1px solid var(--border);
      font-size: 11px; font-weight: 700; letter-spacing: 0.08em;
      text-transform: uppercase; color: var(--muted);
    }
    .post-row {
      display: grid; grid-template-columns: 1fr 140px 100px 110px;
      padding: 16px 20px; border-bottom: 1px solid var(--border);
      align-items: center; transition: background .15s;
    }
    .post-row:last-child { border-bottom: none; }
    .post-row:hover { background: var(--bg); }
    .post-title-cell h3 { font-size: 14px; font-weight: 600; margin-bottom: 3px; line-height: 1.35; }
    .post-title-cell span { font-size: 11.5px; color: var(--muted); }
    .post-cat-cell { font-size: 12.5px; color: var(--muted); }
    .post-status {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 11.5px; font-weight: 700; padding: 3px 10px;
      border-radius: 100px; cursor: pointer; transition: all .15s;
      border: none; font-family: inherit;
    }
    .post-status.pub { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .post-status.draft { background: #fefce8; color: #854d0e; border: 1px solid #fde68a; }
    .post-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .post-actions { display: flex; gap: 6px; }
    .btn-edit, .btn-delete {
      border: none; border-radius: 6px; cursor: pointer;
      width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;
      transition: all .15s;
    }
    .btn-edit { background: var(--bg-mid); }
    .btn-edit:hover { background: rgba(26,59,204,0.12); }
    .btn-edit svg { width: 14px; height: 14px; stroke: var(--blue); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .btn-delete { background: #fff5f5; }
    .btn-delete:hover { background: #fee2e2; }
    .btn-delete svg { width: 14px; height: 14px; stroke: var(--red); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .empty-state {
      padding: 64px 24px; text-align: center;
    }
    .empty-state svg { width: 48px; height: 48px; stroke: var(--border); fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; margin-bottom: 16px; }
    .empty-state h3 { font-size: 16px; font-weight: 700; margin-bottom: 6px; }
    .empty-state p { font-size: 14px; color: var(--muted); }

    /* ═══════════════════════════════════
       EDITOR
    ═══════════════════════════════════ */
    #screen-editor { display: none; max-width: 1000px; margin: 0 auto; padding: 40px 24px; }
    .editor-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; gap: 16px; }
    .editor-header h1 { font-size: 22px; font-weight: 800; }
    .editor-header-actions { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }

    .editor-grid { display: grid; grid-template-columns: 1fr 280px; gap: 24px; align-items: start; }
    .editor-main { display: flex; flex-direction: column; gap: 18px; }
    .editor-side { display: flex; flex-direction: column; gap: 18px; }

    .editor-card {
      background: var(--white); border: 1px solid var(--border);
      border-radius: 12px; padding: 24px;
    }
    .editor-card h3 {
      font-size: 12px; font-weight: 700; letter-spacing: 0.1em;
      text-transform: uppercase; color: var(--muted);
      margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border);
    }

    /* Fields */
    .ef { margin-bottom: 14px; }
    .ef:last-child { margin-bottom: 0; }
    .ef label { display: block; font-size: 12.5px; font-weight: 600; margin-bottom: 5px; }
    .ef input, .ef textarea, .ef select {
      width: 100%; padding: 9px 13px; border: 1.5px solid var(--border); border-radius: 7px;
      font-size: 13.5px; font-family: inherit; color: var(--text);
      background: var(--white); outline: none; transition: border-color .2s;
    }
    .ef input:focus, .ef textarea:focus, .ef select:focus { border-color: var(--blue); }
    .ef textarea { resize: vertical; min-height: 80px; }
    .ef .slug-preview { font-size: 11.5px; color: var(--muted); margin-top: 4px; }
    .ef .slug-preview span { color: var(--blue); }

    /* Rich text toolbar */
    .rich-toolbar {
      display: flex; flex-wrap: wrap; gap: 4px; padding: 10px 12px;
      background: var(--bg); border: 1.5px solid var(--border);
      border-bottom: none; border-radius: 7px 7px 0 0;
    }
    .rich-btn {
      background: var(--white); border: 1px solid var(--border);
      border-radius: 5px; padding: 5px 9px; cursor: pointer;
      font-size: 12px; font-weight: 600; font-family: inherit; color: var(--text);
      transition: all .15s; display: flex; align-items: center; gap: 4px;
    }
    .rich-btn:hover { background: var(--bg-mid); border-color: rgba(26,59,204,0.25); color: var(--blue); }
    .rich-btn svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .rich-sep { width: 1px; background: var(--border); margin: 0 2px; align-self: stretch; }
    #editor-content {
      width: 100%; min-height: 340px; padding: 16px 18px;
      border: 1.5px solid var(--border); border-radius: 0 0 7px 7px;
      font-size: 14.5px; font-family: inherit; color: var(--text);
      outline: none; line-height: 1.75; background: var(--white);
    }
    #editor-content:focus { border-color: var(--blue); }
    #editor-content h1, #editor-content h2, #editor-content h3 { margin: 16px 0 8px; line-height: 1.3; }
    #editor-content h2 { font-size: 20px; }
    #editor-content h3 { font-size: 17px; }
    #editor-content p { margin-bottom: 10px; }
    #editor-content ul, #editor-content ol { margin: 10px 0 10px 24px; }
    #editor-content li { margin-bottom: 4px; }
    #editor-content strong { font-weight: 700; }
    #editor-content em { font-style: italic; }
    #editor-content a { color: var(--blue); }
    #editor-content blockquote {
      border-left: 3px solid var(--blue); padding-left: 14px;
      color: var(--muted); font-style: italic; margin: 12px 0;
    }

    /* Toggle publicado */
    .toggle-wrap { display: flex; align-items: center; justify-content: space-between; }
    .toggle-wrap label { font-size: 14px; font-weight: 600; }
    .toggle-wrap span { font-size: 12.5px; color: var(--muted); display: block; margin-top: 2px; }
    .toggle {
      position: relative; width: 44px; height: 24px; flex-shrink: 0;
    }
    .toggle input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
      position: absolute; inset: 0; background: var(--border);
      border-radius: 100px; cursor: pointer; transition: background .2s;
    }
    .toggle-slider::before {
      content: ''; position: absolute; width: 18px; height: 18px;
      left: 3px; bottom: 3px; background: white; border-radius: 50%;
      transition: transform .2s; box-shadow: 0 1px 4px rgba(0,0,0,0.15);
    }
    .toggle input:checked + .toggle-slider { background: var(--green); }
    .toggle input:checked + .toggle-slider::before { transform: translateX(20px); }

    /* Buttons */
    .btn-save {
      background: var(--blue); color: white; border: none;
      padding: 9px 22px; border-radius: 8px; font-size: 14px;
      font-weight: 700; cursor: pointer; font-family: inherit;
      transition: background .2s; display: flex; align-items: center; gap: 6px;
    }
    .btn-save:hover { background: var(--blue-dark); }
    .btn-save:disabled { opacity: .65; cursor: not-allowed; }
    .btn-save svg { width: 15px; height: 15px; stroke: white; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .btn-back {
      background: transparent; color: var(--muted); border: 1.5px solid var(--border);
      padding: 9px 18px; border-radius: 8px; font-size: 13.5px;
      font-weight: 600; cursor: pointer; font-family: inherit; transition: all .2s;
    }
    .btn-back:hover { border-color: var(--blue); color: var(--blue); }

    /* Toast */
    #toast {
      position: fixed; bottom: 28px; right: 28px; z-index: 999;
      padding: 12px 20px; border-radius: 10px; font-size: 14px; font-weight: 600;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18); display: none;
      max-width: 320px; line-height: 1.4;
    }
    #toast.ok  { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    #toast.err { background: #fff5f5; color: #991b1b; border: 1px solid #fecaca; }

    /* Delete confirm modal */
    #modal-delete, #modal-pass {
      display: none; position: fixed; inset: 0; z-index: 200;
      background: rgba(8,18,43,0.6); align-items: center; justify-content: center;
    }
    #modal-delete.open, #modal-pass.open { display: flex; }
    .modal-card {
      background: var(--white); border-radius: 14px; padding: 36px;
      max-width: 400px; width: 90%; text-align: center;
      box-shadow: 0 24px 64px rgba(0,0,0,0.2);
    }
    .modal-card h3 { font-size: 18px; font-weight: 800; margin-bottom: 10px; }
    .modal-card p { font-size: 14px; color: var(--muted); margin-bottom: 24px; line-height: 1.65; }
    .modal-card .field { margin-bottom: 14px; }
    .modal-card .field label { display: block; font-size: 12.5px; font-weight: 600; margin-bottom: 5px; }
    .modal-card .field input { width: 100%; padding: 9px 13px; border: 1.5px solid var(--border); border-radius: 7px; font-size: 14px; font-family: inherit; outline: none; transition: border-color .2s; }
    .modal-card .field input:focus { border-color: var(--blue); }
    .modal-actions { display: flex; gap: 10px; justify-content: center; }
    .btn-cancel { background: var(--bg); border: 1.5px solid var(--border); color: var(--text); padding: 9px 22px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; transition: all .2s; }
    .btn-cancel:hover { border-color: var(--blue); color: var(--blue); }
    .btn-confirm-delete { background: var(--red); color: white; border: none; padding: 9px 22px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; transition: background .2s; }
    .btn-confirm-delete:hover { background: #dc2626; }

    @media (max-width: 700px) {
      .editor-grid { grid-template-columns: 1fr; }
      .posts-table-header, .post-row { grid-template-columns: 1fr auto; }
      .posts-table-header span:nth-child(2),
      .posts-table-header span:nth-child(3),
      .post-cat-cell, .post-row > div:nth-child(3) { display: none; }
    }
  </style>
</head>
<body>

<!-- ═══════════════════════════════════════
     PANTALLA: LOGIN
═══════════════════════════════════════ -->
<div id="screen-login">
  <div class="login-card">
    <div class="login-logo">
      <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect width="40" height="40" rx="10" fill="#08122b"/>
        <path d="M12 28V12h4l4 10 4-10h4v16h-4V20l-3 8h-2l-3-8v8z" fill="white" opacity="0.9"/>
      </svg>
      <span class="login-logo-text">AD Ingeniería</span>
      <span class="login-logo-sub">Panel de Administración</span>
    </div>
    <p>Ingresa tus credenciales para acceder al panel.</p>
    <div class="field">
      <label for="inp-user">Usuario</label>
      <input type="text" id="inp-user" placeholder="admin" autocomplete="username" />
    </div>
    <div class="field">
      <label for="inp-pass">Contraseña</label>
      <input type="password" id="inp-pass" placeholder="••••••••••" autocomplete="current-password" />
    </div>
    <button class="btn-login" id="btn-login">Iniciar sesión</button>
    <div id="login-error"></div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     PANTALLA: ADMIN
═══════════════════════════════════════ -->
<div id="screen-admin">

  <!-- Topbar -->
  <div class="admin-topbar">
    <div class="topbar-brand">
      <svg width="22" height="22" viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="8" fill="#1a3bcc"/><path d="M12 28V12h4l4 10 4-10h4v16h-4V20l-3 8h-2l-3-8v8z" fill="white"/></svg>
      <div>
        <div>AD Ingeniería</div>
        <div class="topbar-brand-sub">Blog Admin</div>
      </div>
    </div>
    <div class="topbar-actions">
      <button class="btn-new" id="btn-new-post">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nueva entrada
      </button>
      <button class="btn-change-pass" id="btn-change-pass" title="Cambiar contraseña">
        <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </button>
      <button class="btn-logout" id="btn-logout">Cerrar sesión</button>
    </div>
  </div>

  <!-- Dashboard: lista -->
  <div id="screen-list">
    <div class="list-header">
      <h1>Entradas del Blog</h1>
      <p id="list-count">Cargando...</p>
    </div>
    <div class="posts-table" id="posts-table">
      <div class="posts-table-header">
        <span>Título</span>
        <span>Categoría</span>
        <span>Estado</span>
        <span>Acciones</span>
      </div>
      <div id="posts-body">
        <div class="empty-state">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          <h3>Sin entradas todavía</h3>
          <p>Crea tu primera entrada con el botón "Nueva entrada".</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Editor -->
  <div id="screen-editor">
    <div class="editor-header">
      <h1 id="editor-title">Nueva entrada</h1>
      <div class="editor-header-actions">
        <button class="btn-back" id="btn-back">← Volver</button>
        <button class="btn-save" id="btn-save">
          <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          Guardar
        </button>
      </div>
    </div>

    <div class="editor-grid">
      <!-- Columna principal -->
      <div class="editor-main">
        <div class="editor-card">
          <h3>Contenido</h3>
          <div class="ef">
            <label for="f-titulo">Título <span style="color:#ef4444">*</span></label>
            <input type="text" id="f-titulo" placeholder="Ej. Ventajas de Power Apps en la logística" />
          </div>
          <div class="ef">
            <label>Slug (URL)</label>
            <div class="slug-preview">adingenieria.cl/blog/<span id="slug-preview">—</span>/</div>
          </div>
          <div class="ef">
            <label for="f-resumen">Resumen <span style="font-weight:400;color:var(--muted)">(aparece en la lista del blog)</span></label>
            <textarea id="f-resumen" placeholder="Descripción breve del artículo, 1-2 oraciones..." rows="3"></textarea>
          </div>
        </div>

        <div class="editor-card">
          <h3>Cuerpo del artículo</h3>
          <div class="rich-toolbar">
            <button class="rich-btn" data-cmd="bold" title="Negrita"><svg viewBox="0 0 24 24"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg></button>
            <button class="rich-btn" data-cmd="italic" title="Cursiva"><svg viewBox="0 0 24 24"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg></button>
            <button class="rich-btn" data-cmd="underline" title="Subrayado"><svg viewBox="0 0 24 24"><path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"/><line x1="4" y1="21" x2="20" y2="21"/></svg></button>
            <div class="rich-sep"></div>
            <button class="rich-btn" data-cmd="formatBlock" data-val="h2" title="Título H2">H2</button>
            <button class="rich-btn" data-cmd="formatBlock" data-val="h3" title="Título H3">H3</button>
            <button class="rich-btn" data-cmd="formatBlock" data-val="p" title="Párrafo">P</button>
            <div class="rich-sep"></div>
            <button class="rich-btn" data-cmd="insertUnorderedList" title="Lista">
              <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            </button>
            <button class="rich-btn" data-cmd="insertOrderedList" title="Lista numerada">
              <svg viewBox="0 0 24 24"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
            </button>
            <button class="rich-btn" data-cmd="formatBlock" data-val="blockquote" title="Cita">
              <svg viewBox="0 0 24 24"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>
            </button>
            <div class="rich-sep"></div>
            <button class="rich-btn" id="btn-link" title="Insertar enlace">
              <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            </button>
            <button class="rich-btn" id="btn-html">HTML</button>
            <button class="rich-btn" data-cmd="removeFormat" title="Limpiar formato">
              <svg viewBox="0 0 24 24"><path d="M10 5l8 8M17 5l-8 8"/><line x1="5" y1="19" x2="10" y2="19"/></svg>
            </button>
          </div>
          <div id="editor-content" contenteditable="true" spellcheck="false"></div>
        </div>
      </div>

      <!-- Sidebar del editor -->
      <div class="editor-side">
        <div class="editor-card">
          <h3>Publicación</h3>
          <div class="toggle-wrap">
            <div>
              <label for="f-publicado">Publicado</label>
              <span id="pub-status-text">El artículo es un borrador</span>
            </div>
            <label class="toggle">
              <input type="checkbox" id="f-publicado" />
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="editor-card">
          <h3>Clasificación</h3>
          <div class="ef">
            <label for="f-categoria">Categoría</label>
            <select id="f-categoria">
              <option value="">Sin categoría</option>
              <option value="Automatización">Automatización</option>
              <option value="Seguridad Electrónica">Seguridad Electrónica</option>
              <option value="Redes e Infraestructura">Redes e Infraestructura</option>
              <option value="Seguridad Física">Seguridad Física</option>
              <option value="Transformación Digital">Transformación Digital</option>
            </select>
          </div>
        </div>
        <div class="editor-card">
          <h3>Imagen destacada</h3>
        
          <div class="ef">
            <label for="f-imagen">URL de imagen</label>
            <input type="text" id="f-imagen" placeholder="/assets/blog/imagen.jpg" />
          </div>
        
          <div id="img-preview" style="margin-top:10px;"></div>
        </div>

        <div class="editor-card" id="meta-info" style="display:none;">
          <h3>Información</h3>
          <div style="font-size:12.5px;color:var(--muted);line-height:1.8;">
            <div><strong>ID:</strong> <span id="meta-id">—</span></div>
            <div><strong>Creado:</strong> <span id="meta-creado">—</span></div>
            <div><strong>Actualizado:</strong> <span id="meta-actualizado">—</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div><!-- /screen-admin -->

<!-- Modal cambiar contraseña -->
<div id="modal-pass">
  <div class="modal-card">
    <h3>Cambiar contraseña</h3>
    <p>Ingresa tu contraseña actual y luego la nueva dos veces para confirmar.</p>
    <div class="field" style="text-align:left;">
      <label for="cp-actual">Contraseña actual</label>
      <input type="password" id="cp-actual" placeholder="••••••••" autocomplete="current-password" />
    </div>
    <div class="field" style="text-align:left;">
      <label for="cp-nueva">Nueva contraseña</label>
      <input type="password" id="cp-nueva" placeholder="Mínimo 8 caracteres" autocomplete="new-password" />
    </div>
    <div class="field" style="text-align:left;">
      <label for="cp-repetir">Repetir nueva contraseña</label>
      <input type="password" id="cp-repetir" placeholder="Repite la nueva contraseña" autocomplete="new-password" />
    </div>
    <div id="cp-error" style="display:none;background:#fff5f5;border:1px solid #fecaca;color:#991b1b;padding:9px 13px;border-radius:7px;font-size:13px;font-weight:500;margin-bottom:4px;"></div>
    <div class="modal-actions" style="margin-top:8px;">
      <button class="btn-cancel" id="cp-cancel">Cancelar</button>
      <button class="btn-save" id="cp-submit" style="padding:9px 22px;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Guardar contraseña
      </button>
    </div>
  </div>
</div>

<!-- Modal confirmar eliminación -->
<div id="modal-delete">
  <div class="modal-card">
    <h3>¿Eliminar esta entrada?</h3>
    <p>Esta acción no se puede deshacer. La entrada se eliminará permanentemente de la base de datos.</p>
    <div class="modal-actions">
      <button class="btn-cancel" id="modal-cancel">Cancelar</button>
      <button class="btn-confirm-delete" id="modal-confirm">Sí, eliminar</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div id="toast"></div>

<script nonce="<?= $nonce ?>">
// ════════════════════════════════════════════════════════
//  Utilidades
// ════════════════════════════════════════════════════════
const API = 'actions.php';
let currentPostId = 0;
let deleteTargetId = 0;

async function api(params) {
  const fd = new FormData();
  Object.entries(params).forEach(([k,v]) => fd.append(k, v));
  const r = await fetch(API, { method: 'POST', body: fd });
  return r.json();
}
async function apiGet(action, id = '') {
  const url = id ? `${API}?action=${action}&id=${id}` : `${API}?action=${action}`;
  const r = await fetch(url);
  return r.json();
}

function toast(msg, ok = true) {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className = ok ? 'ok' : 'err';
  el.style.display = 'block';
  clearTimeout(el._t);
  el._t = setTimeout(() => { el.style.display = 'none'; }, 3500);
}

function show(id) {
  document.getElementById('screen-login').style.display  = 'none';
  document.getElementById('screen-admin').style.display  = 'none';
  document.getElementById('screen-list').style.display   = 'none';
  document.getElementById('screen-editor').style.display = 'none';

  if (id === 'screen-admin') {
    document.getElementById('screen-admin').style.display = 'block';
    document.getElementById('screen-list').style.display  = 'block';
  } else if (id === 'screen-login') {
    document.getElementById('screen-login').style.display = 'flex';
  } else {
    document.getElementById(id).style.display = 'block';
  }
}

function makeSlug(text) {
  const map = {á:'a',é:'e',í:'i',ó:'o',ú:'u',ñ:'n',ü:'u',Á:'a',É:'e',Í:'i',Ó:'o',Ú:'u',Ñ:'n'};
  return text.toLowerCase().replace(/[áéíóúñüÁÉÍÓÚÑ]/g, c => map[c]||c)
             .replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').replace(/^-|-$/g,'');
}

function fmtDate(str) {
  if (!str) return '—';
  const d = new Date(str.replace(' ','T'));
  return d.toLocaleDateString('es-CL', {day:'2-digit',month:'2-digit',year:'numeric'}) +
         ' ' + d.toLocaleTimeString('es-CL', {hour:'2-digit',minute:'2-digit'});
}

// ════════════════════════════════════════════════════════
//  Login
// ════════════════════════════════════════════════════════
async function checkSession() {
  try {
    const r = await apiGet('check');
    if (r.success) { show('screen-admin'); loadPosts(); }
    else show('screen-login');
  } catch { show('screen-login'); }
}

document.getElementById('btn-login').addEventListener('click', doLogin);
document.getElementById('inp-pass').addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });

async function doLogin() {
  const btn = document.getElementById('btn-login');
  const err = document.getElementById('login-error');
  const user = document.getElementById('inp-user').value.trim();
  const pass = document.getElementById('inp-pass').value;
  if (!user || !pass) { err.textContent = 'Completa usuario y contraseña.'; err.style.display = 'block'; return; }
  btn.disabled = true; btn.textContent = 'Verificando...';
  err.style.display = 'none';
  try {
    const r = await api({ action: 'login', username: user, password: pass });
    if (r.success) { show('screen-admin'); loadPosts(); }
    else { err.textContent = r.message; err.style.display = 'block'; }
  } catch { err.textContent = 'Error de conexión.'; err.style.display = 'block'; }
  btn.disabled = false; btn.textContent = 'Iniciar sesión';
}

document.getElementById('btn-logout').addEventListener('click', async () => {
  await api({ action: 'logout' });
  show('screen-login');
  document.getElementById('inp-user').value = '';
  document.getElementById('inp-pass').value = '';
});

// ════════════════════════════════════════════════════════
//  Lista de posts
// ════════════════════════════════════════════════════════
async function loadPosts() {
  const r = await apiGet('list');
  const body = document.getElementById('posts-body');
  const count = document.getElementById('list-count');
  if (!r.success || !r.posts.length) {
    count.textContent = 'Sin entradas publicadas todavía.';
    body.innerHTML = `<div class="empty-state">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      <h3>Sin entradas todavía</h3>
      <p>Crea tu primera entrada con el botón "Nueva entrada".</p></div>`;
    return;
  }
  const total = r.posts.length;
  const pub   = r.posts.filter(p => p.publicado == 1).length;
  count.textContent = `${total} entrada${total!==1?'s':''} · ${pub} publicada${pub!==1?'s':''}`;
  body.innerHTML = r.posts.map(p => `
    <div class="post-row">
      <div class="post-title-cell">
        <h3>${escHtml(p.titulo)}</h3>
        <span>${fmtDate(p.creado_en)}</span>
      </div>
      <div class="post-cat-cell">${escHtml(p.categoria || '—')}</div>
      <div>
        <button class="post-status ${p.publicado==1?'pub':'draft'}"
                onclick="togglePost(${p.id})" title="Clic para cambiar estado">
          ${p.publicado==1 ? 'Publicado' : 'Borrador'}
        </button>
      </div>
      <div class="post-actions">
        <button class="btn-edit" onclick="editPost(${p.id})" title="Editar">
          <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </button>
        <button class="btn-delete" onclick="confirmDelete(${p.id})" title="Eliminar">
          <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </button>
      </div>
    </div>`).join('');
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function togglePost(id) {
  const r = await api({ action: 'toggle', id });
  if (r.success) { toast(r.message); loadPosts(); }
  else toast(r.message, false);
}

// ════════════════════════════════════════════════════════
//  Editor
// ════════════════════════════════════════════════════════
document.getElementById('btn-new-post').addEventListener('click', openNewEditor);
document.getElementById('btn-back').addEventListener('click', () => {
  document.getElementById('screen-editor').style.display = 'none';
  document.getElementById('screen-list').style.display = 'block';
  loadPosts();
});

function openNewEditor() {
  currentPostId = 0;
  document.getElementById('editor-title').textContent = 'Nueva entrada';
  document.getElementById('f-titulo').value = '';
  document.getElementById('f-resumen').value = '';
  document.getElementById('f-categoria').value = '';
  document.getElementById('editor-content').innerHTML = '';
  document.getElementById('f-imagen').value = '';
  document.getElementById('img-preview').innerHTML = '';
  document.getElementById('f-publicado').checked = false;
  document.getElementById('pub-status-text').textContent = 'El artículo es un borrador';
  document.getElementById('slug-preview').textContent = '—';
  document.getElementById('meta-info').style.display = 'none';
  document.getElementById('screen-list').style.display = 'none';
  document.getElementById('screen-editor').style.display = 'block';
}

async function editPost(id) {
  const r = await apiGet('get', id);
  if (!r.success) { toast('No se pudo cargar la entrada', false); return; }
  const p = r.post;
  currentPostId = p.id;
  document.getElementById('editor-title').textContent = 'Editar entrada';
  document.getElementById('f-titulo').value = p.titulo;
  document.getElementById('f-resumen').value = p.resumen || '';
  document.getElementById('f-categoria').value = p.categoria || '';
  document.getElementById('editor-content').innerHTML = p.contenido;
  document.getElementById('f-imagen').value = p.imagen || '';
  document.getElementById('img-preview').innerHTML = p.imagen 
    ? `<img src="${p.imagen}" style="width:100%;border-radius:8px;margin-top:8px;">`
    : '';
  document.getElementById('f-publicado').checked = p.publicado == 1;
  document.getElementById('pub-status-text').textContent = p.publicado == 1 ? 'Visible en el blog' : 'El artículo es un borrador';
  document.getElementById('slug-preview').textContent = p.slug;
  document.getElementById('meta-id').textContent = p.id;
  document.getElementById('meta-creado').textContent = fmtDate(p.creado_en);
  document.getElementById('meta-actualizado').textContent = fmtDate(p.actualizado);
  document.getElementById('meta-info').style.display = 'block';
  document.getElementById('screen-list').style.display = 'none';
  document.getElementById('screen-editor').style.display = 'block';
}

// Slug preview en tiempo real
document.getElementById('f-titulo').addEventListener('input', function() {
  const s = makeSlug(this.value);
  document.getElementById('slug-preview').textContent = s || '—';
});

// Toggle publicado label
document.getElementById('f-publicado').addEventListener('change', function() {
  document.getElementById('pub-status-text').textContent =
    this.checked ? 'Visible en el blog' : 'El artículo es un borrador';
});

// Guardar
document.getElementById('btn-save').addEventListener('click', async function() {
  const titulo    = document.getElementById('f-titulo').value.trim();
  const resumen   = document.getElementById('f-resumen').value.trim();
  const categoria = document.getElementById('f-categoria').value;
  const contenido = document.getElementById('editor-content').innerHTML.trim();
  const imagen    = document.getElementById('f-imagen').value;
  const publicado = document.getElementById('f-publicado').checked ? 'on' : '';

  if (!titulo) { toast('El título es obligatorio', false); document.getElementById('f-titulo').focus(); return; }
  if (!contenido || contenido === '<br>') { toast('El contenido no puede estar vacío', false); return; }

  this.disabled = true;
  this.textContent = 'Guardando...';

  const r = await api({
      action:'save',
      id: currentPostId,
      titulo,
      resumen,
      categoria,
      contenido,
      publicado,
      imagen
    });
  this.disabled = false;
  this.innerHTML = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar';

  if (r.success) {
    toast(r.message);
    currentPostId = r.id;
    document.getElementById('slug-preview').textContent = r.slug;
    document.getElementById('meta-id').textContent = r.id;
    document.getElementById('meta-info').style.display = 'block';
    document.getElementById('editor-title').textContent = 'Editar entrada';
  } else {
    toast(r.message, false);
  }
});

// ════════════════════════════════════════════════════════
//  Rich text toolbar
// ════════════════════════════════════════════════════════
document.querySelectorAll('.rich-btn[data-cmd]').forEach(btn => {
  btn.addEventListener('mousedown', e => {
    e.preventDefault();
    const cmd = btn.dataset.cmd;
    const val = btn.dataset.val || null;
    document.execCommand(cmd, false, val);
    document.getElementById('editor-content').focus();
  });
});

document.getElementById('btn-link').addEventListener('mousedown', e => {
  e.preventDefault();
  const url = prompt('URL del enlace (ej: https://...):');
  if (url) document.execCommand('createLink', false, url);
  document.getElementById('editor-content').focus();
});

// ════════════════════════════════════════════════════════
//  Eliminar
// ════════════════════════════════════════════════════════
function confirmDelete(id) {
  deleteTargetId = id;
  document.getElementById('modal-delete').classList.add('open');
}

document.getElementById('modal-cancel').addEventListener('click', () => {
  document.getElementById('modal-delete').classList.remove('open');
});

document.getElementById('modal-confirm').addEventListener('click', async () => {
  document.getElementById('modal-delete').classList.remove('open');
  const r = await api({ action: 'delete', id: deleteTargetId });
  toast(r.success ? 'Entrada eliminada' : r.message, r.success);
  if (r.success) loadPosts();
});

document.getElementById('modal-delete').addEventListener('click', e => {
  if (e.target === document.getElementById('modal-delete'))
    document.getElementById('modal-delete').classList.remove('open');
});

// ════════════════════════════════════════════════════════
//  Cambiar contraseña
// ════════════════════════════════════════════════════════
document.getElementById('btn-change-pass').addEventListener('click', () => {
  document.getElementById('cp-actual').value  = '';
  document.getElementById('cp-nueva').value   = '';
  document.getElementById('cp-repetir').value = '';
  document.getElementById('cp-error').style.display = 'none';
  document.getElementById('modal-pass').classList.add('open');
  setTimeout(() => document.getElementById('cp-actual').focus(), 80);
});

document.getElementById('cp-cancel').addEventListener('click', () => {
  document.getElementById('modal-pass').classList.remove('open');
});

document.getElementById('modal-pass').addEventListener('click', e => {
  if (e.target === document.getElementById('modal-pass'))
    document.getElementById('modal-pass').classList.remove('open');
});

document.getElementById('cp-submit').addEventListener('click', async function() {
  const actual  = document.getElementById('cp-actual').value;
  const nueva   = document.getElementById('cp-nueva').value;
  const repetir = document.getElementById('cp-repetir').value;
  const errEl   = document.getElementById('cp-error');

  errEl.style.display = 'none';

  const btn = this;
  btn.disabled = true;
  const prev = btn.innerHTML;
  btn.innerHTML = 'Guardando...';

  const r = await api({ action: 'change_password', actual, nueva, repetir });

  btn.disabled = false;
  btn.innerHTML = prev;

  if (r.success) {
    document.getElementById('modal-pass').classList.remove('open');
    toast('✓ ' + r.message);
  } else {
    errEl.textContent = r.message;
    errEl.style.display = 'block';
  }
});

// Permitir Enter en los campos del modal de contraseña
['cp-actual','cp-nueva','cp-repetir'].forEach(id => {
  document.getElementById(id).addEventListener('keydown', e => {
    if (e.key === 'Enter') document.getElementById('cp-submit').click();
  });
});
document.getElementById('f-imagen').addEventListener('input', function() {
  const url = this.value;
  const preview = document.getElementById('img-preview');

  if (!url) {
    preview.innerHTML = '';
    return;
  }

  preview.innerHTML = `
    <img src="${url}" style="width:100%; border-radius:8px; margin-top:8px;">
  `;
});
document.getElementById('btn-html').addEventListener('click', () => {
  const html = prompt('Pega tu HTML aquí:');

  if (html) {
    document.getElementById('editor-content').innerHTML += html;
  }
});
// ════════════════════════════════════════════════════════
//  Init
// ════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  checkSession();
});
</script>
</body>
</html>
