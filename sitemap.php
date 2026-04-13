<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

$dominio = "https://adingenieria.cl";

// 🔹 URLs fijas
$urls = [
    "/" => "1.0",
    "/contacto/" => "0.7",
    "/nosotros/" => "0.6",
    "/proyectos/" => "0.9"
];

foreach ($urls as $path => $priority) {
    echo "<url>";
    echo "<loc>{$dominio}{$path}</loc>";
    echo "<lastmod>" . date("Y-m-d") . "</lastmod>";
    echo "<changefreq>weekly</changefreq>";
    echo "<priority>{$priority}</priority>";
    echo "</url>";
}


try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );


    // 🔥 CONSULTA CORRECTA
    $st = $pdo->query("SELECT slug, actualizado FROM blog_posts WHERE publicado = 1");

    while ($row = $st->fetch()) {

        $slug = htmlspecialchars($row['slug'], ENT_QUOTES, 'UTF-8');

        echo "<url>";
        echo "<loc>{$dominio}/blog/{$slug}/</loc>";
        echo "<lastmod>" . date("Y-m-d", strtotime($row['actualizado'])) . "</lastmod>";
        echo "<changefreq>monthly</changefreq>";
        echo "<priority>0.8</priority>";
        echo "</url>";
    }

} catch (Exception $e) {
    // 🔥 no romper sitemap
}

// 🔥 cierre
echo '</urlset>';