<?php
/**
 * HedgeBet - Tela de Login (Acesso com Senha em Texto Puro)
 * Arquivo: public/login.php
 */

session_start();

// Se já estiver logado, redireciona
if (isset($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit();
}

require_once '../config/database.php';

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $usuarioInput = trim($_POST['usuario']);
    $senhaInput = trim($_POST['senha']);

    if (!empty($usuarioInput) && !empty($senhaInput)) {
        try {
            // Busca o usuário pelo campo 'usuario'
            $query = "SELECT id, usuario, senha FROM usuarios WHERE usuario = :usuario LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':usuario', $usuarioInput);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            // A ALTERAÇÃO ESTÁ AQUI: Comparação direta (===) em vez de password_verify
            if ($usuario && ($senhaInput === $usuario['senha'])) {
                $_SESSION['usuario_logado'] = $usuario['usuario'];
                $_SESSION['usuario_id'] = $usuario['id'];
                
                header("Location: index.php");
                exit();
            } else {
                $erro = "Usuário ou senha inválidos.";
            }
        } catch (PDOException $e) {
            $erro = "Erro no servidor: " . $e->getMessage();
        }
    } else {
        $erro = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HedgeBet - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #0f172a; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { background-color: #1e293b; border-radius: 12px; width: 100%; max-width: 400px; padding: 2rem; color: #fff; }
        .form-control { background-color: #334155; border: 1px solid #475569; color: #fff; }
        .btn-success { background-color: #10b981; border: none; }
    </style>
</head>
<body>

<div class="card login-card shadow-lg">
    <h2 class="text-center fw-bold text-success mb-4"><i class="fa-solid fa-chart-line me-2"></i>HedgeBet</h2>
    
    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger p-2 text-center small"><?= $erro ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="mb-3">
            <label class="form-label small">Usuário</label>
            <input type="text" class="form-control" name="usuario" required>
        </div>
        <div class="mb-4">
            <label class="form-label small">Senha</label>
            <input type="password" class="form-control" name="senha" required>
        </div>
        <button type="submit" class="btn btn-success w-100 fw-bold">Entrar</button>
    </form>
</div>

</body>
</html>