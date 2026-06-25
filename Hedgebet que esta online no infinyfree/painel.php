
<?php
session_start();
require_once 'src/Helpers/trava.php';
require_once 'autoload.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();
$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'registrar_operacao') {
    try {
        $stmt = $db->prepare("INSERT INTO apostas (time_casa, time_visitante, data_jogo, odd_favorito, odd_empate, aposta_favorito, aposta_empate, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Aberta')");
        $stmt->execute([
            $_POST['time_casa'], $_POST['time_visitante'], $_POST['data_jogo'],
            (float)$_POST['odd_favorito_form'], (float)$_POST['odd_empate_form'],
            (float)$_POST['aposta_fav_hidden'], (float)$_POST['aposta_emp_hidden']
        ]);
        $mensagem = '<div class="alert alert-success">Operação registrada com sucesso!</div>';
    } catch (Exception $e) {
        $mensagem = '<div class="alert alert-danger">Erro: ' . $e->getMessage() . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HedgeBet - Simulador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .navbar { background-color: #1e293b !important; }
        .card-result { border-left: 5px solid #10b981; background: #f0fdf4; }
        /* Ajustes de responsividade */
        @media (max-width: 767px) {
            .btn-group { flex-direction: column; }
            .btn-group .btn { border-radius: 0.375rem !important; margin-bottom: 5px; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="painel.php"><i class="fa-solid fa-chart-line text-success me-2"></i>HedgeBet</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link active" href="painel.php">Simulador</a>
            <a class="nav-link" href="operacoes.php">Diário de Apostas</a>
            <a class="nav-link" href="dashboard.php">Dashboard</a>
            <a class="nav-link text-danger fw-bold" href="logout.php"><i class="fa-solid fa-right-from-bracket me-1"></i> Sair</a>
        </div>
    </div>
</nav>

<div class="container">
    <?= $mensagem ?>
    <form action="painel.php" method="POST" id="formSimulador">
        <input type="hidden" name="acao" value="registrar_operacao">
        <input type="hidden" name="aposta_fav_hidden" id="apostaFavHidden">
        <input type="hidden" name="aposta_emp_hidden" id="apostaEmpHidden">

        <div class="row g-4">
            <div class="col-12 col-md-4">
                <div class="card p-3 shadow-sm h-100">
                    <h5 class="mb-3">Configurar Cenário</h5>
                    <input type="text" name="time_casa" class="form-control mb-2" placeholder="Time Casa" required>
                    <input type="text" name="time_visitante" class="form-control mb-2" placeholder="Time Visitante" required>
                    <input type="date" name="data_jogo" class="form-control mb-2" value="<?= date('Y-m-d') ?>">
                    
                    <label class="small fw-bold">ODD Favorito</label>
                    <input type="number" step="0.01" id="oddFav" name="odd_favorito_form" class="form-control mb-2" value="2.22">
                    
                    <label class="small fw-bold">ODD Empate</label>
                    <input type="number" step="0.01" id="oddEmp" name="odd_empate_form" class="form-control mb-2" value="3.60">
                    
                    <label class="small fw-bold text-primary">ODD Chance Dupla (Casa)</label>
                    <input type="number" step="0.01" id="oddCasa" class="form-control mb-2" value="1.26">
                    
                    <label class="small fw-bold">Stake Total</label>
                    <input type="number" id="stake" class="form-control mb-3" value="25">
                    
                    <button type="submit" class="btn btn-success w-100">Enviar para Registro</button>
                </div>
            </div>
            
            <div class="col-12 col-md-8">
                <div class="card p-3 shadow-sm mb-3 border-0 bg-light">
                    <h6><i class="fa-solid fa-shield-halved me-2"></i>Status de Eficiência</h6>
                    <div id="statusMensagem" class="alert alert-info mb-0 text-center fw-bold">Aguardando cálculo...</div>
                </div>

                <div class="card p-3 shadow-sm mb-3">
                    <h5 class="mb-2">Distribuição Assimétrica</h5>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="prioridade" id="p1" value="equilibrado" checked>
                        <label class="btn btn-outline-secondary" for="p1">⚖️ Equilibrado</label>
                        <input type="radio" class="btn-check" name="prioridade" id="p2" value="favorito">
                        <label class="btn btn-outline-secondary" for="p2">🏆 Focar Favorito</label>
                        <input type="radio" class="btn-check" name="prioridade" id="p3" value="empate">
                        <label class="btn btn-outline-secondary" for="p3">🤝 Focar Empate</label>
                    </div>
                </div>

                <div class="card card-result p-3 shadow-sm mb-3">
                    <strong><i class="fa-solid fa-trophy text-warning"></i> Cenário: Vitória do Favorito <span class="badge bg-success float-end" id="percFav">+0.00%</span></strong>
                    <div class="row text-center mt-2 g-2">
                        <div class="col-4 small">Aposta<br><strong id="resApostaFav">R$ 0,00</strong></div>
                        <div class="col-4 small">Retorno<br><strong id="resRetornoFav">R$ 0,00</strong></div>
                        <div class="col-4 small">Lucro<br><strong id="resLucroFav" class="text-success">R$ 0,00</strong></div>
                    </div>
                </div>

                <div class="card card-result p-3 shadow-sm">
                    <strong><i class="fa-solid fa-handshake text-primary"></i> Cenário: Cobertura no Empate <span class="badge bg-success float-end" id="percEmp">+0.00%</span></strong>
                    <div class="row text-center mt-2 g-2">
                        <div class="col-4 small">Aposta<br><strong id="resApostaEmp">R$ 0,00</strong></div>
                        <div class="col-4 small">Retorno<br><strong id="resRetornoEmp">R$ 0,00</strong></div>
                        <div class="col-4 small">Lucro<br><strong id="resLucroEmp" class="text-success">R$ 0,00</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function calc() {
    const oddFav = parseFloat(document.getElementById("oddFav").value) || 0;
    const oddEmp = parseFloat(document.getElementById("oddEmp").value) || 0;
    const oddCasa = parseFloat(document.getElementById("oddCasa").value) || 0;
    const stake = parseFloat(document.getElementById("stake").value) || 0;
    const prio = document.querySelector('input[name="prioridade"]:checked').value;
    const statusMsg = document.getElementById("statusMensagem");
    
    let aFav, aEmp;

    if (prio === 'favorito') {
        aEmp = (stake / oddEmp);
        aFav = stake - aEmp;
    } else if (prio === 'empate') {
        aFav = (stake / oddFav);
        aEmp = stake - aFav;
    } else {
        const soma = (1/oddFav) + (1/oddEmp);
        aFav = ((1/oddFav)/soma) * stake;
        aEmp = ((1/oddEmp)/soma) * stake;
    }
    
    const lucroFav = (aFav * oddFav) - stake;
    const lucroEmp = (aEmp * oddEmp) - stake;
    
    document.getElementById("resApostaFav").innerText = "R$ " + aFav.toFixed(2);
    document.getElementById("resRetornoFav").innerText = "R$ " + (aFav * oddFav).toFixed(2);
    document.getElementById("resLucroFav").innerText = "R$ " + lucroFav.toFixed(2);
    document.getElementById("percFav").innerText = "+" + ((lucroFav / stake) * 100).toFixed(2) + "%";
    
    document.getElementById("resApostaEmp").innerText = "R$ " + aEmp.toFixed(2);
    document.getElementById("resRetornoEmp").innerText = "R$ " + (aEmp * oddEmp).toFixed(2);
    document.getElementById("resLucroEmp").innerText = "R$ " + lucroEmp.toFixed(2);
    document.getElementById("percEmp").innerText = "+" + ((lucroEmp / stake) * 100).toFixed(2) + "%";

    document.getElementById("apostaFavHidden").value = aFav.toFixed(2);
    document.getElementById("apostaEmpHidden").value = aEmp.toFixed(2);

    const valorX = stake * oddCasa;
    if (Math.max(lucroFav, lucroEmp) >= (valorX - stake)) {
        statusMsg.className = "alert alert-success mb-0 text-center fw-bold";
        statusMsg.innerHTML = "Operação Vantajosa! ✅";
    } else {
        statusMsg.className = "alert alert-warning mb-0 text-center fw-bold";
        statusMsg.innerHTML = "Ineficiente: Abaixo da Casa ❌";
    }
}
document.querySelectorAll('input').forEach(i => i.addEventListener('input', calc));
document.querySelectorAll('input[name="prioridade"]').forEach(i => i.addEventListener('change', calc));
calc();
</script>
</body>
</html>