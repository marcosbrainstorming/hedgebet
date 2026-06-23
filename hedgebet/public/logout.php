<?php
/**
 * HedgeBet - Encerrar Sessão
 * Arquivo: public/logout.php
 */
session_start();
session_destroy(); // Limpa todas as variáveis salvas
header("Location: login.php"); // Manda de volta para a tela de login
exit();