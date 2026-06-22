<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "Sucesso! O HedgeBet está conectado ao banco de dados.";
}