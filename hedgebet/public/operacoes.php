<?php
/**
 * HedgeBet - Diário de Apostas Ativas e Resolução
 * Arquivo: public/operacoes.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../autoload.php';
require_once '../config/database.php';
require_once '../src/Helpers/functions.php';

$database = new Database();
$db = $database->getConnection();

$mensagemStatus = "";
$tipoAlerta = "success";

// PROCESSA A RESOLUÇÃO DA APOSTA (Quando clica em Salvar Resultado)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'resolver_aposta') {
    try {
        $apostaId = (int)$_POST['aposta_id'];
        $resultadoReal = htmlspecialchars(strip_tags($_POST['resultado_real'])); // 'favorito', 'empate' ou 'perdeu'

        // 1. Busca os dados originais da aposta para fazer o cálculo do retorno
        $queryBusca = "SELECT odd_favorito, odd_empate, aposta_favorito, aposta_empate FROM apostas WHERE id = :id";
        $stmtBusca = $db->prepare($queryBusca);
        $stmtBusca->bindParam(':id', $apostaId);
        $stmtBusca->execute();
        $dadosAposta = $stmtBusca->fetch(PDO::FETCH_ASSOC);

        if ($dadosAposta) {
            $valorRetornado = 0.00;
            $statusResultado = 'Ganhou';

            // 2. Calcula o retorno bruto baseado no resultado escolhido
            if ($resultadoReal === 'favorito') {
                $valorRetornado = $dadosAposta['aposta_favorito'] * $dadosAposta['odd_favorito'];
                $statusResultado = 'Ganhou';
            } elseif ($resultadoReal === 'empate') {
                $valorRetornado = $dadosAposta['aposta_empate'] * $dadosAposta['odd_empate'];
                $statusResultado = 'Ganhou';
            } else {
                // Cenário onde a operação deu totalmente errada (Zebra ganhou ou Red total)
                $valorRetornado = 0.00;
                $statusResultado = 'Perdeu';
            }

            // 3. Atualiza a aposta no banco de dados
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
                if ($statusResultado === 'Perdeu') {
                    $mensagemStatus = "Operação #{$apostaId} finalizada como <strong>Prejuízo (Red Total)</strong>. R$ 0,00 de retorno.";
                    $tipoAlerta = "danger";
                } else {
                    $mensagemStatus = "Operação #{$apostaId} finalizada com sucesso! Retorno de <strong>R$ " . number_format($valorRetornado, 2, ',', '.') . "</strong> computado.";
                    $tipoAlerta = "success";
                }
            }
        }
    } catch (PDOException $e) {
        $mensagemStatus = "Erro ao finalizar aposta: " . $e->getMessage();
        $tipoAlerta = "danger";
    }
}

// BUSCA AS APOSTAS PARA LISTAGEM
$apostas = [];
try {
    $query = "SELECT id, time_casa, time_visitante, data_jogo, odd_favorito, odd_empate, aposta_favorito, aposta_empate, status, status_resultado, valor_retornado 
              FROM apostas 
              ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagemStatus = "Erro ao carregar dados do banco: " . $e->getMessage();
    $tipoAlerta = "danger";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HedgeBet - Diário de Apostas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background-color: #1e293b !important; }
        .card-header { background-color: #1e293b; color: #fff; font-weight: 600; }
        .badge-status { font-size: 0.85rem; padding: 0.4em 0.8em; }
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
        </div>
    </div>
</nav>

<div class="container">
    <h3 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-folder-open text-primary me-2"></i>Apostas em Andamento</h3>

    <?php if (!empty($mensagemStatus)): ?>
        <div class="alert alert-<?= $tipoAlerta ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= $mensagemStatus ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header py-3">
            <h6 class="m-0"><i class="fa-solid fa-list text-success me-2"></i>Monitoramento de Coberturas Recentes</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Partida / Data</th>
                            <th class="text-center">ODD Fav / Emp</th>
                            <th class="text-center">Entrada Fav</th>
                            <th class="text-center">Entrada Emp</th>
                            <th class="text-center">Status / Resultado</th>
                            <th class="text-end pe-3" style="min-width: 220px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($apostas)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-circle-info d-block fs-2 mb-2 text-secondary"></i>
                                    Nenhuma operação encontrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($apostas as $a): ?>
                                <tr>
                                    <td class="ps-3">
                                        <strong><?= htmlspecialchars($a['time_casa']) . " x " . htmlspecialchars($a['time_visitante']) ?></strong>
                                        <small class="text-muted d-block"><i class="fa-regular fa-calendar me-1"></i><?= date('d/m/Y', strtotime($a['data_jogo'])) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">F: <?= number_format($a['odd_favorito'], 2) ?></span>
                                        <span class="badge bg-dark">E: <?= number_format($a['odd_empate'], 2) ?></span>
                                    </td>
                                    <td class="text-center fw-semibold text-dark">R$ <?= number_format($a['aposta_favorito'], 2, ',', '.') ?></td>
                                    <td class="text-center fw-semibold text-dark">R$ <?= number_format($a['aposta_empate'], 2, ',', '.') ?></td>
                                    <td class="text-center">
                                        <?php if ($a['status'] === 'Aberta'): ?>
                                            <span class="badge bg-warning text-dark badge-status"><i class="fa-regular fa-clock me-1"></i>Aberta</span>
                                        <?php else: ?>
                                            <span class="badge <?= $a['status_resultado'] === 'Ganhou' ? 'bg-success' : 'bg-danger' ?> badge-status">
                                                <i class="fa-solid <?= $a['status_resultado'] === 'Ganhou' ? 'fa-check-double' : 'fa-xmark' ?> me-1"></i><?= htmlspecialchars($a['status']) ?>
                                            </span>
                                            <small class="d-block text-muted mt-1">Retorno: R$ <?= number_format($a['valor_retornado'], 2, ',', '.') ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if ($a['status'] === 'Aberta'): ?>
                                            <form action="operacoes.php" method="POST" class="d-flex gap-2 justify-content-end align-items-center">
                                                <input type="hidden" name="acao" value="resolver_aposta">
                                                <input type="hidden" name="aposta_id" value="<?= $a['id'] ?>">
                                                
                                                <select name="resultado_real" class="form-select form-select-sm" style="max-width: 150px;" required>
                                                    <option value="" disabled selected>Quem ganhou?</option>
                                                    <option value="favorito">Vitória Favorito</option>
                                                    <option value="empate">Empate</option>
                                                    <option value="perdeu">Perdeu (Red Total)</option>
                                                </select>
                                                
                                                <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-floppy-disk"></i></button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small italic">
                                                <i class="fa-solid fa-lock me-1"></i>
                                                <?= $a['status_resultado'] === 'Ganhou' ? 'Lucro' : 'Red Total' ?>
                                            </span>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>