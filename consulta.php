<?php
require __DIR__.'/vendor/autoload.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

use Firebase\JWT\JWT;
use Dotenv\Dotenv;

// Carrega variáveis de ambiente
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Autenticação básica (JWT)
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    die(json_encode(["error" => "Token não fornecido"]));
}

$token = $matches[1];
try {
    JWT::decode($token, $_ENV['JWT_SECRET'], ['HS256']);
} catch (Exception $e) {
    http_response_code(401);
    die(json_encode(["error" => "Token inválido"]));
}

// Conexão com o banco
$db = new PDO(
    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

// Rota principal
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cpf = preg_replace('/\D/', '', $_GET['cpf'] ?? '');
    
    if (empty($cpf) || strlen($cpf) != 11) {
        http_response_code(400);
        echo json_encode(["error" => "CPF inválido"]);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM clientes WHERE cpf = ?");
    $stmt->execute([$cpf]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        http_response_code(404);
        echo json_encode(["error" => "Cliente não encontrado"]);
        exit;
    }

    // Resposta formatada
    echo json_encode([
        "status" => 200,
        "data" => [
            "cliente" => $cliente,
            "enderecos" => $db->query("SELECT * FROM enderecos WHERE cpf_cliente = '$cpf'")->fetchAll()
        ]
    ]);
}
