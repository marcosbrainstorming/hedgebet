<?php
// Proteção de acesso
require_once '../src/Helpers/trava.php';

require_once '../autoload.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$mensagemStatus = "";
$tipoAlerta = "success";

// Processa a Resolução da Aposta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'resolver_aposta') {
    try {
        $apostaId = (int)$_POST['aposta_id'];
        $resultadoReal = htmlspecialchars(strip_tags($_POST['resultado_real']));

        $queryBusca = "SELECT odd_favorito, odd_empate, aposta_favorito, aposta_empate FROM apostas WHERE id = :id";
        $stmtBusca = $db->prepare($queryBusca);
        $stmtBusca->bindParam(':id', $apostaId);
        $stmtBusca->execute();
        $dadosAposta = $stmtBusca->fetch(PDO::FETCH_ASSOC);

        if ($dadosAposta) {
            $valorRetornado = 0.00;
            $statusResultado = 'Ganhou';

            if ($resultadoReal === 'favorito') {
                $valorRetornado = $dadosAposta['aposta_favorito'] * $dadosAposta['odd_favorito'];
                $statusResultado = 'Ganhou';
            } elseif ($resultadoReal === 'empate') {
                $valorRetornado = $dadosAposta['aposta_empate'] * $dadosAposta['odd_empate'];
                $statusResultado = 'Ganhou';
            } else {
                $valorRetornado = 0.00;
                $statusResultado = 'Perdeu';
            }

            $queryUpdate = "UPDATE apostas SET 
                                status_resultado = :status_res, 
                                valor_retornado = :valor_ret, 
                                status = 'Finalizada' 
                            WHERE id = :id";
            
            $stmtUpdate = $db->prepare($queryUpdate);
            $stmtUpdate->bindParam(':status_res', $statusResultado);
            $stmtUpdate->bindParam(':valor_ret', $valorRetornado);
            $stmtUpdate->bindParam(':id', $apostaId);

            if ($stmtUpdate->execute()) {
                $mensagemStatus = ($statusResultado === 'Perdeu') 
                    ? "Operação #{$apostaId} finalizada como Prejuízo." 
                    : "Operação #{$apostaId} finalizada! Retorno: R$ " . number_format($valorRetornado, 2, ',', '.');
                $tipoAlerta = ($statusResultado === 'Perdeu') ? "danger" : "success";
            }
        }
    } catch (PDOException $e) {
        $mensagemStatus = "Erro ao processar: " . $e->getMessage();
        $tipoAlerta = "danger";
    }
}

// Busca de apostas com proteção para evitar erro de foreach
$apostas = [];
try {
    $query = "SELECT * FROM apostas ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $apostas = ($resultado !== false) ? $resultado : [];
} catch (PDOException $e) {
    $apostas = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Diário de Apostas - HedgeBet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .navbar { background-color: #1e293b !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-chart-line text-success me-2"></i>HedgeBet</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="index.php">Simulador</a>
            <a class="nav-link active" href="operacoes.php">Diário de Apostas</a>
            <a class="nav-link" href="dashboard.php">Dashboard</a>
            <a class="nav-link text-danger fw-bold" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </div>
    </div>
</nav>

<div class="container">
    <h3 class="mb-4">Diário de Operações</h3>

    <?php if (!empty($mensagemStatus)): ?>
        <div class="alert alert-<?= $tipoAlerta ?>"><?= $mensagemStatus ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Partida</th>
                        <th>Entrada Fav</th>
                        <th>Entrada Emp</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($apostas)): ?>
                        <tr><td colspan="5" class="text-center py-4">Nenhuma aposta encontrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($apostas as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['time_casa'] ?? '') ?> x <?= htmlspecialchars($a['time_visitante'] ?? '') ?></td>
                                <td>R$ <?= number_format($a['aposta_favorito'] ?? 0, 2) ?></td>
                                <td>R$ <?= number_format($a['aposta_empate'] ?? 0, 2) ?></td>
                                <td><span class="badge bg-<?= ($a['status'] === 'Aberta') ? 'warning' : 'success' ?>"><?= $a['status'] ?></span></td>
                                <td class="text-end">
                                    <?php if ($a['status'] === 'Aberta'): ?>
                                        <form action="operacoes.php" method="POST" class="d-inline-flex gap-2">
                                            <input type="hidden" name="acao" value="resolver_aposta">
                                            <input type="hidden" name="aposta_id" value="<?= $a['id'] ?>">
                                            <select name="resultado_real" class="form-select form-select-sm">
                                                <option value="favorito">Venceu Fav</option>
                                                <option value="empate">Empate</option>
                                                <option value="perdeu">Red</option>
                                            </select>
                                            <button class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>