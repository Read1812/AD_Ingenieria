<?php
/**
 * AD Ingeniería — Ejemplo de configuración
 *
 * Copia este archivo como "config.php" en el servidor y completa
 * los valores reales. NUNCA subas config.php al repositorio git.
 */

// ─── BASE DE DATOS ─────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'nombre_base_de_datos');
define('DB_USER',    'usuario_db');
define('DB_PASS',    'contraseña_db');
define('DB_CHARSET', 'utf8mb4');

// ─── SMTP ──────────────────────────────────────────────────────
define('SMTP_HOST', 'smtp.tuproveedor.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'noreply@tudominio.com');
define('SMTP_PASS', 'contraseña_smtp');

// ─── CORREO ────────────────────────────────────────────────────
define('MAIL_TO',   'contacto@tudominio.com');
define('MAIL_FROM', 'noreply@tudominio.com');
define('MAIL_NAME', 'Tu Empresa');

// ─── CLOUDFLARE TURNSTILE ──────────────────────────────────────
// Obtener en: https://dash.cloudflare.com/ → Turnstile
define('CF_TURNSTILE_SECRET', 'tu_secreto_turnstile_aqui');

// ─── ADMIN (contraseña por defecto si no existe admin/config.php) ──
// Cambia esta contraseña inmediatamente después del primer login.
define('ADMIN_DEFAULT_PASS', 'CambiarEstaContraseña2025!');
