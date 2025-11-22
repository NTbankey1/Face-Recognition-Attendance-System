<?php
// PHP built-in server router
// - If the requested path is a real file, let the server serve it
// - Otherwise, route to index.php and set request_site from the URL path

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = __DIR__ . $uri;

if ($uri !== '/' && file_exists($path) && !is_dir($path)) {
    // Serve static file (assets, models, images, etc.)
    return false;
}

// Derive request_site from the path (e.g., /login, /logout, /home)
$slug = trim($uri, '/');
if ($slug !== '') {
    $_GET['request_site'] = $slug;
}

require __DIR__ . '/index.php';
