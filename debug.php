<?php

echo "<h2>🔧 Debug MyDrive</h2>";

// 1. PHP INFO (básico)
echo "<h3>PHP Version</h3>";
echo phpversion();

// 2. Variáveis de ambiente
echo "<h3>Variáveis de ambiente MySQL</h3>";

$vars = [
    "DB_HOST",
    "DB_USER",
    "DB_PASS",
    "DB_NAME",
    "DB_PORT"
];

foreach ($vars as $v) {
    $value = getenv($v);

    // esconder senha
    if ($v === "DB_PASS" && $value) {
        $value = str_repeat("*", strlen($value));
    }

    echo "<b>$v:</b> " . ($value !== false && $value !== null ? $value : "❌ NÃO DEFINIDO") . "<br>";
}

// 3. Teste simples MySQL
echo "<h3>Teste MySQL</h3>";

$host = getenv("DB_HOST");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");
$db   = getenv("DB_NAME");
$port = getenv("DB_PORT") ?: 3306;



if (!$host || !$user || !$db) {
    echo "❌ Variáveis incompletas para conexão";
    exit;
}

echo getenv("SKEY");

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    echo "❌ Falha na conexão: " . $conn->connect_error;
} else {
    echo "✅ Conectado com sucesso!";
}

?>
