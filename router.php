<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$publicFile = __DIR__ . $uri;

// =========================
// uploads -> check.php
// =========================
if (preg_match('#^/uploads/(.*)$#', $uri, $matches)) {

    $_GET['file'] = $matches[1];
    require __DIR__ . '/check.php';

    return true;
}

// =========================
// Arquivo físico existente
// =========================
if ($uri !== '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    return false;
}

// =========================
// Pasta existente
// =========================
if (is_dir($publicFile)) {
    return false;
}

// =========================
// rota -> arquivo.php
// login -> login.php
// perfil -> perfil.php
// =========================
$route = trim($uri, '/');

if (empty($route)) {
    $route = 'index';
}

$phpFile = __DIR__ . '/' . $route . '.php';

if (file_exists($phpFile)) {
    require $phpFile;
    return true;
}

// =========================
// 404
// =========================
http_response_code(404);

echo "<h1>404</h1>";
echo "Página não encontrada";
