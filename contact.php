<?php
/**
 * AD Ingeniería — Formulario de contacto (PRO)
 * ✔ Cloudflare Turnstile
 * ✔ MySQL (Hostinger)
 * ✔ PHPMailer SMTP
 */

// ─── DEBUG (desactivar en producción) ─────────────────────────
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ─── CONFIGURACIÓN ────────────────────────────────────────────
define('CF_TURNSTILE_SECRET', getenv('CF_TURNSTILE_SECRET') ?: '');

// DB ────────────────────────────────────────────────
define('DB_HOST',    getenv('DB_HOST') ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME') ?: '');
define('DB_USER',    getenv('DB_USER') ?: '');
define('DB_PASS',    getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// MAIL ────────────────────────────────────────────
define('MAIL_TO',   getenv('MAIL_TO') ?: 'contacto@adingenieria.cl');
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'no-reply@adingenieria.cl');
define('MAIL_NAME', getenv('MAIL_NAME') ?: 'AD Ingeniería');

// SMTP────────────────────────────────────────────
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 465));
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');

// ─── HEADERS ─────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// ─── INPUT ───────────────────────────────────────────────────
$nombre   = trim($_POST['nombre'] ?? '');
$email    = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$servicio = trim($_POST['servicio'] ?? '');
$mensaje  = trim($_POST['mensaje'] ?? '');
$token    = $_POST['cf-turnstile-response'] ?? '';
$ip       = $_SERVER['REMOTE_ADDR'] ?? '';

// ─── VALIDACIÓN ──────────────────────────────────────────────
if (!$nombre || !$email || !$mensaje || !$token) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// ─── TURNSTILE ───────────────────────────────────────────────
function verifyTurnstile($token, $ip) {
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'secret' => CF_TURNSTILE_SECRET,
            'response' => $token,
            'remoteip' => $ip
        ])
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['success'] ?? false;
}

if (!verifyTurnstile($token, $ip)) {
    echo json_encode(['success' => false, 'message' => 'Error de verificación']);
    exit;
}

// ─── DB ──────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // crear tabla si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contactos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(120),
            email VARCHAR(180),
            telefono VARCHAR(30),
            servicio VARCHAR(120),
            mensaje TEXT,
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $stmt = $pdo->prepare("
        INSERT INTO contactos (nombre, email, telefono, servicio, mensaje)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([$nombre, $email, $telefono, $servicio, $mensaje]);

    $contactoId = $pdo->lastInsertId();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error DB']);
    exit;
}
// ─── Correo inerno ───────────────────────────────────────────────
function buildHtml($nombre, $email, $telefono, $servicio, $mensaje, $id) {

    $nombre   = htmlspecialchars($nombre);
    $email    = htmlspecialchars($email);
    $telefono = htmlspecialchars($telefono);
    $servicio = htmlspecialchars($servicio);
    $mensaje  = nl2br(htmlspecialchars($mensaje));

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0;">
<tr><td align="center">

<table width="560" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">

<tr>
<td style="background:#1a3bcc;padding:24px;color:white;">
<strong style="font-size:20px;">AD Ingeniería</strong><br>
<span style="font-size:13px;opacity:.8;">Nuevo mensaje de contacto #$id</span>
</td>
</tr>

<tr>
<td style="padding:24px;">

<p><strong>Nombre:</strong><br>$nombre</p>

<p><strong>Correo:</strong><br>
<a href="mailto:$email">$email</a></p>

<p><strong>Teléfono:</strong><br>$telefono</p>

<p><strong>Servicio:</strong><br>$servicio</p>

<p><strong>Mensaje:</strong><br>$mensaje</p>

</td>
</tr>

<tr>
<td style="background:#f4f6fb;padding:16px;font-size:12px;color:#666;">
Enviado desde adingenieria.cl
</td>
</tr>

</table>

</td></tr>
</table>

</body>
</html>
HTML;
}
// ─── Correo cofirmación ───────────────────────────────────────────────
function buildHtmlCliente($nombre, $servicio, $id) {

    $nombre   = htmlspecialchars($nombre);
    $servicio = htmlspecialchars($servicio);

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
</head>

<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0;">
<tr><td align="center">

<table width="560" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">

<!-- HEADER -->
<tr>
<td style="background:#0f172a;padding:15px 28px;color:white;">

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>

            <!-- LOGO -->
            <td width="60" style="text-align:left; vertical-align:middle;">
                <img src="https://adingenieria.cl/assets/logo-simple.svg"
                     style="height:60px;"
                     alt="AD Ingeniería">
            </td>

            <!-- TEXTO CENTRADO REAL -->
            <td style="text-align:center; vertical-align:middle;">
                <div style="font-size:18px;font-weight:600;letter-spacing:0.5px;">
                    AD Ingeniería
                </div>
                <div style="font-size:14px;opacity:.8;margin-top:6px;">
                    Hemos recibido tu solicitud
                </div>
            </td>

            <!-- ESPACIADOR (IMPORTANTE) -->
            <td width="60"></td>

        </tr>
    </table>

</td>
</tr>
<!-- CONTENIDO -->
<tr>
<td style="padding:28px;">

<p>Hola <strong>$nombre</strong>,</p>

<p>Gracias por contactarte con <strong>AD Ingeniería</strong>.</p>

<p>Tu solicitud ha sido registrada correctamente con el siguiente detalle:</p>

<div style="background:#f4f6fb;padding:14px;border-radius:8px;">
<strong>Servicio:</strong> $servicio<br>
<strong>ID de solicitud:</strong> #$id
</div>

<p style="margin-top:20px;">
Nuestro equipo revisará tu requerimiento y te contactará dentro de las próximas <strong>24 horas</strong>.
</p>

<p>
Si deseas agregar más información, puedes responder directamente a este correo o escribirnos a <a href="mailto:contacto@adingenieria.cl" style="color:#1a3bcc;text-decoration:none;">
    contacto@adingenieria.cl
</a>.
</p>

<p style="margin-top:24px;">
Saludos,<br>
<strong>Equipo AD Ingeniería</strong>
</p>

</td>
</tr>

<!-- LOGO FINAL -->
<tr>
<td style="padding:24px;text-align:center;">
    <img src="https://adingenieria.cl/assets/Logo-dark.png"
         style="width:350px; opacity:0.95;"
         alt="AD Ingeniería">
</td>
</tr>

<!-- FOOTER -->
<tr>
<td style="background:#f4f6fb;padding:16px;font-size:12px;color:#666;text-align:center;">
Este es un correo generado automaticamente.
</td>
</tr>

</table>

</td></tr>
</table>

</body>
</html>
HTML;
}
// ─── PHPMailer ───────────────────────────────────────────────
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

$mailOk = false;

try {
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = SMTP_PORT;

    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ];

    $mail->setFrom(MAIL_FROM, MAIL_NAME);
    $mail->addAddress(MAIL_TO);
    $mail->addReplyTo($email, $nombre);

    $mail->isHTML(true);
    $mail->Subject = "Nuevo solicitud #$contactoId";

    $mail->Body = buildHtml(
        $nombre,
        $email,
        $telefono,
        $servicio,
        $mensaje,
        $contactoId
    );

    $mailOk = $mail->send();
    // ─── ENVÍO AL CLIENTE ─────────────────────────────
    if ($mailOk) {
    
        $mail->clearAddresses();
        $mail->clearReplyTos();
        
        $mail->addAddress($email, $nombre);
        $mail->addReplyTo('contacto@adingenieria.cl', 'AD Ingeniería');
        $mail->Subject = "Hemos recibido tu solicitud – AD Ingeniería";
        
        $mail->Body = buildHtmlCliente(
            $nombre,
            $servicio,
            $contactoId
        );
        
        $mail->send();
    }

} catch (Exception $e) {
    error_log("MAIL ERROR: " . $mail->ErrorInfo);
}

// ─── RESPUESTA ───────────────────────────────────────────────
echo json_encode([
    'success' => true,
    'message' => 'Mensaje enviado correctamente'
]);