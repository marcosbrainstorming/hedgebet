<?php
/**
 * HedgeBet - Dashboard Financeiro & Gestão de Saques Pessoais
 * Arquivo: public/dashboard.php
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

// Tenta criar a tabela de saques caso ela não exista no seu banco 'hedgebet'
try {
    $db->exec("CREATE TABLE IF NOT EXISTS saques_caixa (
        id INT AUTO_INCREMENT PRIMARY KEY,
        valor DECIMAL(10,2) NOT NULL,
        descricao VARCHAR(255) NOT NULL,
        data_saque TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    // Tabela já existe ou erro silencioso
}

// PROCESSA O CADASTRO DE UM NOVO SAQUE PESSOAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'registrar_saque') {
    try {
        $valorSaque = (float)$_POST['valor_saque'];
        $descricaoSaque = htmlspecialchars(strip_tags($_POST['descricao_saque']));

        if ($valorSaque > 0) {
            $querySaque = "INSERT INTO saques_caixa (valor, descricao) VALUES (:valor, :descricao)";
            $stmtSaque = $db->prepare($querySaque);
            $stmtSaque->bindParam(':valor', $valorSaque);
            $stmtSaque->bindParam(':descricao', $descricaoSaque);

            if ($stmtSaque->execute()) {
                $mensagemStatus = "Saque pessoal de <strong>R$ " . number_format($valorSaque, 2, ',', '.') . "</strong> registrado com sucesso e deduzido do saldo total!";
                $tipoAlerta = "success";
            }
        } else {
            $mensagemStatus = "Por favor, insira um valor válido para o saque.";
            $tipoAlerta = "warning";
        }
    } catch (PDOException $e) {
        $mensagemStatus = "Erro ao registrar saque: " . $e->getMessage();
        $tipoAlerta = "danger";
    }
}

// 1. CÁLCULO DAS APOSTAS (Entradas e Retornos Técnicos)
$totalInvestido = 0.00;
$totalRetornado = 0.00;
$lucroBrutoApostas = 0.00;

try {
    // Soma tudo o que foi colocado nas duas colunas de entrada
    $queryApostas = "SELECT SUM(aposta_favorito + aposta_empate) as total_investido, SUM(valor_retornado) as total_retornado FROM apostas WHERE status = 'Finalizada'";
    $stmtA = $db->query($queryApostas);
    $resA = $stmtA->fetch(PDO::FETCH_ASSOC);
    
    if ($resA) {
        $totalInvestido = (float)$resA['total_investido'];
        $totalRetornado = (float)$resA['total_retornado'];
        $lucroBrutoApostas = $totalRetornado - $totalInvestido;
    }
} catch (PDOException $e) {
    // Erro ao buscar apostas
}

// 2. CÁLCULO DOS SAQUES TOTAIS (Retiradas para conta pessoal)
$totalSaquesPessoais = 0.00;
try {
    $queryTotalSaques = "SELECT SUM(valor) as total_saques FROM saques_caixa";
    $stmtS = $db->query($queryTotalSaques);
    $resS = $stmtS->fetch(PDO::FETCH_ASSOC);
    if ($resS) {
        $totalSaquesPessoais = (float)$resS['total_saques'];
    }
} catch (PDOException $e) {
    // Erro ao buscar saques
}

// 3. SALDO ATUAL DO SISTEMA (Banca disponível considerando as retiradas feitas)
$bancaDisponivelAtual = $lucroBrutoApostas - $totalSaquesPessoais;

// BUSCA HISTÓRICO DE SAQUES PARA EXIBIR NA TELA
$historicoSaques = [];
try {
    $queryHist = "SELECT id, valor, descricao, data_saque FROM saques_caixa ORDER BY id DESC LIMIT 5";
    $historicoSaques = $db->query($queryHist)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Erro ao buscar histórico
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HedgeBet - Dashboard Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background-color: #1e293b !important; }
        .card-metric { border-left: 4px solid; }
        .metric-value { font-size: 1.8rem; font-weight: 700; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-chart-line text-success me-2"></i>HedgeBet</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="index.php">Simulador</a>
            <a class="nav-link" href="operacoes.php">Diário de Apostas</a>
            <a class="nav-link active" href="dashboard.php">Dashboard</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0 text-dark"><i class="fa-solid fa-gauge-high text-success me-2"></i>Dashboard Financeiro</h3>
        <button type="button" class="btn btn-danger btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalSaque">
            <i class="fa-solid fa-hand-holding-dollar me-1"></i> Registrar Retirada / Saque
        </button>
    </div>

    <?php if (!empty($mensagemStatus)): ?>
        <div class="alert alert-<?= $tipoAlerta ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= $mensagemStatus ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-metric border-start border-success shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small">Lucro Bruto (Apostas)</h6>
                    <div class="metric-value text-success">R$ <?= number_format($lucroBrutoApostas, 2, ',', '.') ?></div>
                    <small class="text-muted">Total gerado pelas operações ganhas</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-metric border-start border-danger shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small">Retiradas Executadas</h6>
                    <div class="metric-value text-danger">R$ <?= number_format($totalSaquesPessoais, 2, ',', '.') ?></div>
                    <small class="text-muted">Dinheiro sacado para sua conta pessoal</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-metric border-start border-primary shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small">Saldo Atual na Banca</h6>
                    <div class="metric-value text-primary">R$ <?= number_format($bancaDisponivelAtual, 2, ',', '.') ?></div>
                    <small class="text-muted">Disponível para novas operações no sistema</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white py-3">
            <h6 class="m-0"><i class="fa-solid fa-clock-rotate-left me-2 text-warning"></i>Últimos Saques Pessoais Realizados</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Data da Retirada</th>
                            <th>Descrição / Destino</th>
                            <th class="text-end pe-3">Valor Sacado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historicoSaques)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Nenhum saque pessoal realizado até o momento.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historicoSaques as $s): ?>
                                <tr>
                                    <td class="ps-3"><i class="fa-regular fa-calendar-check me-2 text-secondary"></i><?= date('d/m/Y H:i', strtotime($s['data_saque'])) ?></td>
                                    <td><span class="badge bg-light text-dark text-wrap"><?= htmlspecialchars($s['descricao']) ?></span></td>
                                    <td class="text-end pe-3 text-danger fw-bold">- R$ <?= number_format($s['valor'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSaque" tabindex="-1" aria-labelledby="modalSaqueLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title id="modalSaqueLabel" fw-bold"><i class="fa-solid fa-money-bill-transfer me-2"></i>Registrar Saque de Lucro</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="dashboard.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="registrar_saque">
                    
                    <div class="mb-3">
                        <label for="valor_saque" class="form-label fw-semibold">Valor do Saque (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">R$</span>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="valor_saque" name="valor_saque" placeholder="0,00" required>
                        </div>
                        <div class="form-text">Este valor será deduzido do Saldo Geral do sistema.</div>
                    </div>

                    <div class="mb-3">
                        <label for="descricao_saque" class="form-label fw-semibold">Descrição / Motivo</label>
                        <input type="text" class="form-control" id="descricao_saque" name="descricao_saque" placeholder="Ex: Transferência para minha conta corrente" required>
                    </div>
                </div>
                <div class="modal-footer table-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3 fw-bold"><i class="fa-solid fa-check me-1"></i>Confirmar Saque</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>