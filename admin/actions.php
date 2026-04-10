<?php
/**
 * AD Ingeniería — Blog Admin · API de acciones
 * ✔ Fix: password_hash() como variable, no como define()
 */

ini_set('display_errors', 0);
error_reporting(0);

// Capturar cualquier error fatal y devolverlo como JSON
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e['message']]);
    }
});

session_start();

header('Content-Type: application/json; charset=utf-8');

// ─── CREDENCIALES ─────────────────────────────────────────────
// El hash se guarda en config.php (mismo directorio).
// Si no existe, se crea con la contraseña por defecto.

$ADMIN_USER    = 'admin';
$CONFIG_FILE   = __DIR__ . '/config.php';
$DEFAULT_PASS  = 'ADingenieria2025!';

if (file_exists($CONFIG_FILE)) {
    // Cargar hash guardado
    $cfg = include $CONFIG_FILE;
    $ADMIN_HASH = $cfg['hash'] ?? password_hash($DEFAULT_PASS, PASSWORD_BCRYPT);
} else {
    // Primera vez: crear config.php con hash de la contraseña por defecto
    $ADMIN_HASH = password_hash($DEFAULT_PASS, PASSWORD_BCRYPT);
    $content = "<?php\nreturn ['hash' => " . var_export($ADMIN_HASH, true) . "];\n";
    file_put_contents($CONFIG_FILE, $content);
}

// ─── DB ───────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'u977895088_adingen_Db');
define('DB_USER',    'u977895088_Richard_adm');
define('DB_PASS',    'AD_InG2026');
define('DB_CHARSET', 'utf8mb4');

// ─── HELPERS ──────────────────────────────────────────────────
function resp(bool $ok, string $msg = '', array $data = []): void {
    echo json_encode(['success' => $ok, 'message' => $msg] + $data);
    exit;
}

function requireAuth(): void {
    if (empty($_SESSION['admin_logged'])) {
        http_response_code(401);
        resp(false, 'No autenticado');
    }
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS blog_posts (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                titulo      VARCHAR(255)  NOT NULL,
                slug        VARCHAR(255)  NOT NULL UNIQUE,
                categoria   VARCHAR(100)  DEFAULT '',
                resumen     TEXT,
                contenido   LONGTEXT      NOT NULL,
                publicado   TINYINT(1)    DEFAULT 0,
                creado_en   DATETIME      DEFAULT CURRENT_TIMESTAMP,
                actualizado DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
    } catch (Exception $e) {
        resp(false, 'No se pudo conectar a la base de datos.');
    }
    return $pdo;
}

function makeSlug(string $text): string {
    $map  = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u',
             'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ñ'=>'n'];
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// ─── ROUTER ───────────────────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── LOGIN ──────────────────────────────────────────────────
    case 'login':
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';

        if (!$user || !$pass) {
            resp(false, 'Completa usuario y contraseña.');
        }

        // Comparar usuario (timing-safe) y verificar hash
        $userOk = hash_equals($ADMIN_USER, $user);
        $passOk = password_verify($pass, $ADMIN_HASH);

        if (!$userOk || !$passOk) {
            sleep(1); // freno de fuerza bruta
            resp(false, 'Usuario o contraseña incorrectos.');
        }

        session_regenerate_id(true);
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_user']   = $user;
        resp(true, 'Sesión iniciada');

    // ── LOGOUT ─────────────────────────────────────────────────
    case 'logout':
        $_SESSION = [];
        session_destroy();
        resp(true, 'Sesión cerrada');

    // ── CHECK SESSION ──────────────────────────────────────────
    case 'check':
        resp(!empty($_SESSION['admin_logged']), '');

    // ── LISTAR POSTS ──────────────────────────────────────────
    case 'list':
        requireAuth();
        $rows = getDB()->query(
            'SELECT id, titulo, slug, categoria, publicado, creado_en, actualizado
             FROM blog_posts ORDER BY creado_en DESC'
        )->fetchAll();
        resp(true, '', ['posts' => $rows]);

    // ── OBTENER UN POST ────────────────────────────────────────
    case 'get':
        requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) resp(false, 'ID inválido');
        $st = getDB()->prepare('SELECT * FROM blog_posts WHERE id = ?');
        $st->execute([$id]);
        $post = $st->fetch();
        if (!$post) resp(false, 'Entrada no encontrada');
        resp(true, '', ['post' => $post]);

    // ── GUARDAR POST ──────────────────────────────────────────
    case 'save':
        requireAuth();
        $id        = (int)($_POST['id']        ?? 0);
        $titulo    = trim($_POST['titulo']     ?? '');
        $categoria = trim($_POST['categoria']  ?? '');
        $resumen   = trim($_POST['resumen']    ?? '');
        $contenido = trim($_POST['contenido']  ?? '');
        $imagen    = trim($_POST['imagen'] ?? '');
        $publicado = !empty($_POST['publicado']) ? 1 : 0;

        if (!$titulo)    resp(false, 'El título es obligatorio.');
        if (!$contenido) resp(false, 'El contenido no puede estar vacío.');

        $db   = getDB();
        $base = makeSlug($titulo);
        $slug = $base;
        $i    = 2;

        // Slug único
        while (true) {
            $st = $db->prepare('SELECT id FROM blog_posts WHERE slug = ?');
            $st->execute([$slug]);
            $existing = (int)$st->fetchColumn();
            if (!$existing || $existing === $id) break;
            $slug = $base . '-' . $i++;
        }

        try {
            if ($id > 0) {
                $st = $db->prepare('
                    UPDATE blog_posts
                    SET titulo=?, slug=?, categoria=?, resumen=?, contenido=?, publicado=?, imagen=?
                    WHERE id=?
                ');
                $st->execute([$titulo, $slug, $categoria, $resumen, $contenido, $publicado, $imagen, $id]);
                resp(true, 'Entrada actualizada.', ['slug' => $slug, 'id' => $id]);
            } else {
                $st = $db->prepare('
            INSERT INTO blog_posts (titulo, slug, categoria, resumen, contenido, publicado, imagen)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            
            $st->execute([
                $titulo,
                $slug,
                $categoria,
                $resumen,
                $contenido,
                $publicado,
                $imagen
            ]);
                resp(true, 'Entrada creada.', ['slug' => $slug, 'id' => (int)$db->lastInsertId()]);
            }
        } catch (Exception $e) {
            resp(false, 'Error al guardar: ' . $e->getMessage());
        }

    // ── ELIMINAR POST ─────────────────────────────────────────
    case 'delete':
        requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) resp(false, 'ID inválido');
        $st = getDB()->prepare('DELETE FROM blog_posts WHERE id = ?');
        $st->execute([$id]);
        resp(true, 'Entrada eliminada.');

    // ── TOGGLE PUBLICADO ──────────────────────────────────────
    case 'toggle':
        requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) resp(false, 'ID inválido');
        $db = getDB();
        $db->prepare('UPDATE blog_posts SET publicado = 1 - publicado WHERE id = ?')->execute([$id]);
        $pub = (int)$db->prepare('SELECT publicado FROM blog_posts WHERE id = ?')
                       ->execute([$id]) ? $db->query("SELECT publicado FROM blog_posts WHERE id=$id")->fetchColumn() : 0;
        resp(true, $pub ? 'Publicado' : 'Borrador', ['publicado' => (int)$pub]);

    // ── CAMBIAR CONTRASEÑA ────────────────────────────────────
    case 'change_password':
        requireAuth();
        $actual  = $_POST['actual']   ?? '';
        $nueva   = $_POST['nueva']    ?? '';
        $repetir = $_POST['repetir']  ?? '';

        if (!$actual || !$nueva || !$repetir) {
            resp(false, 'Completa todos los campos.');
        }
        if (!password_verify($actual, $ADMIN_HASH)) {
            sleep(1);
            resp(false, 'La contraseña actual es incorrecta.');
        }
        if (strlen($nueva) < 8) {
            resp(false, 'La nueva contraseña debe tener al menos 8 caracteres.');
        }
        if ($nueva !== $repetir) {
            resp(false, 'Las contraseñas nuevas no coinciden.');
        }

        $nuevoHash = password_hash($nueva, PASSWORD_BCRYPT);
        $content   = "<?php\nreturn ['hash' => " . var_export($nuevoHash, true) . "];\n";

        if (file_put_contents($CONFIG_FILE, $content) === false) {
            resp(false, 'No se pudo guardar la contraseña. Verifica permisos del directorio.');
        }

        // Regenerar sesión por seguridad
        session_regenerate_id(true);
        resp(true, 'Contraseña actualizada correctamente.');

    default:
        http_response_code(400);
        resp(false, 'Acción no reconocida.');
}
