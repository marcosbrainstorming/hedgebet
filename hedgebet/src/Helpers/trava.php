<?php
/**
 * HedgeBet - Protetor de Páginas
 * Arquivo: src/Helpers/trava.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se NÃO existir a sessão do usuário, chuta ele de volta para o login
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: login.php");
    exit();
}