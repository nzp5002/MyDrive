<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv("DB_HOST");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");
$db   = getenv("DB_NAME");
$port = getenv("DB_PORT") ?: 3306;

// tenta conectar
$conn = @new mysqli($host, $user, $pass, $db, $port);

// se conectou, volta pro site
if (!$conn->connect_error) {

    header("Location: /");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistema Indisponível</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    height: 100vh;
    overflow: hidden;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #000;
    position: relative;
    color: white;
}

/* Fundo */
body::before {
    content: "";
    position: absolute;
    inset: 0;
    background: url('https://pbs.twimg.com/media/F74jYdUbgAAsIU4.jpg') center center/cover no-repeat;
    filter: blur(8px) brightness(0.35);
    transform: scale(1.1);
}

/* Camada escura */
body::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(
        180deg,
        rgba(0,0,0,0.55),
        rgba(0,0,0,0.85)
    );
}

.container {
    position: relative;
    z-index: 10;
    width: 92%;
    max-width: 520px;
    padding: 35px;
    border-radius: 28px;
    backdrop-filter: blur(16px);
    background: rgba(20, 20, 20, 0.55);
    border: 1px solid rgba(255,255,255,0.08);
    box-shadow:
        0 10px 40px rgba(0,0,0,0.55),
        inset 0 1px 0 rgba(255,255,255,0.04);
    text-align: center;
    animation: fadeIn 0.8s ease;
}

.logo {
    width: 120px;
    height: 120px;
    margin: 0 auto 20px;
    border-radius: 22px;
    overflow: hidden;
    border: 2px solid rgba(255,255,255,0.1);
    box-shadow: 0 8px 30px rgba(0,0,0,0.45);
}

.logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

h1 {
    font-size: 2rem;
    margin-bottom: 12px;
    font-weight: 700;
}

p {
    color: rgba(255,255,255,0.75);
    line-height: 1.7;
    font-size: 1rem;
    margin-bottom: 28px;
}

.status {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 18px;
    border-radius: 999px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    font-size: 0.95rem;
    color: rgba(255,255,255,0.85);
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #ff3b3b;
    box-shadow: 0 0 12px #ff3b3b;
    animation: pulse 1.5s infinite;
}

.reload {
    margin-top: 30px;
}

.home-btn {
    display: inline-block;
    text-decoration: none;
    border: none;
    outline: none;
    padding: 14px 22px;
    border-radius: 14px;
    background: white;
    color: black;
    font-weight: 600;
    cursor: pointer;
    transition: 0.25s ease;
    font-size: 0.95rem;
}

.home-btn:hover {
    transform: translateY(-2px) scale(1.03);
    box-shadow: 0 10px 25px rgba(255,255,255,0.15);
}

.reload button {
    border: none;
    outline: none;
    padding: 14px 22px;
    border-radius: 14px;
    background: white;
    color: black;
    font-weight: 600;
    cursor: pointer;
    transition: 0.25s ease;
    font-size: 0.95rem;
}

.reload button:hover {
    transform: translateY(-2px) scale(1.03);
    box-shadow: 0 10px 25px rgba(255,255,255,0.15);
}

@keyframes pulse {
    0% {
        opacity: 1;
        transform: scale(1);
    }

    50% {
        opacity: 0.5;
        transform: scale(1.4);
    }

    100% {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 600px) {

    .container {
        padding: 28px 22px;
    }

    h1 {
        font-size: 1.6rem;
    }

    p {
        font-size: 0.95rem;
    }
}
</style>
</head>
<body>

<div class="container">

    <div class="logo">
        <img src="https://pbs.twimg.com/media/F74jYdUbgAAsIU4.jpg" alt="Erro Sistema">
    </div>

    <h1>Sistema temporariamente indisponível</h1>

    <p>
        Estamos enfrentando uma instabilidade temporária no banco de dados.
        Nossa equipe já foi notificada automaticamente.
    </p>

    <div class="status">
        <div class="dot"></div>
        Banco de dados offline
    </div>

    <div class="reload">
        <a href="/" class="home-btn">
            Voltar para página principal
        </a>
    </div>

</div>

</body>
</html>
