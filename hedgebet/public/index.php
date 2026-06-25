<?php
require_once '../src/Helpers/trava.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../autoload.php';
require_once '../config/database.php';
require_once '../src/Helpers/functions.php';

$database = new Database();
$db = $database->getConnection();

$mensagemStatus = "";
$tipoAlerta = "success";

// Processa o salvamento quando clicar em "Enviar para Registro"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'registrar_operacao') {
    try {
        $timeCasa = htmlspecialchars(strip_tags($_POST['time_casa']));
        $timeVisitante = htmlspecialchars(strip_tags($_POST['time_visitante']));
        $dataJogo = htmlspecialchars(strip_tags($_POST['data_jogo']));
        
        $oddFavorito = (float)$_POST['odd_favorito_form'];
        $oddEmpate = (float)$_POST['odd_empate_form'];
        $stakeTotal = (float)$_POST['stake_total_form'];
        $prioridade = isset($_POST['prioridade_form']) ? $_POST['prioridade_form'] : 'equilibrado';

        // ==========================================================================
        // BACKEND: ALGORITMO DE DISTRIBUIÇÃO ASSIMÉTRICA CONFIGURÁVEL
        // ==========================================================================
        $probFav = 1 / $oddFavorito;
        $probEmp = 1 / $oddEmpate;
        $somaProb = $probFav + $probEmp;

        $apostaFav = 0;
        $apostaEmp = 0;

        if ($somaProb < 1) {
            // Há margem de lucro real (Surebet)
            if ($prioridade === 'favorito') {
                // Prioridade Favorito: Cobre o empate (retorno = stake) e joga o lucro no favorito
                $apostaEmp = round($stakeTotal / $oddEmpate, 2);
                $apostaFav = round($stakeTotal - $apostaEmp, 2);
            } elseif ($prioridade === 'empate') {
                // Prioridade Empate: Cobre o favorito (retorno = stake) e joga o lucro no empate
                $apostaFav = round($stakeTotal / $oddFavorito, 2);
                $apostaEmp = round($stakeTotal - $apostaFav, 2);
            } else {
                // Equilibrado: Arbitragem pura tradicional
                $apostaFav = round(($probFav / $somaProb) * $stakeTotal, 2);
                $apostaEmp = round(($probEmp / $somaProb) * $stakeTotal, 2);
                
                $diferenca = $stakeTotal - ($apostaFav + $apostaEmp);
                if ($diferenca != 0) {
                    $apostaFav = round($apostaFav + $diferenca, 2);
                }
            }
        } else {
            // PLANO B (Mercado sem margem): Força proteção máxima no empate para mitigar danos
            $apostaEmp = round($stakeTotal / $oddEmpate, 2);
            $apostaFav = round($stakeTotal - $apostaEmp, 2);
        }

        // Bloco de inserção com suporte a múltiplas estruturas de colunas de banco
        $gravou = false;
        $colunasTentativa = [
            "INSERT INTO apostas (casa, visitante, data_jogo, odd_favorito, odd_empate, aposta_favorito, aposta_empate, status) VALUES (:casa, :visitante, :data_jogo, :odd_fav, :odd_emp, :aposta_fav, :aposta_emp, 'Aberta')",
            "INSERT INTO apostas (time_casa, time_visitante, data_jogo, odd_favorito, odd_empate, aposta_favorito, aposta_empate, status) VALUES (:casa, :visitante, :data_jogo, :odd_fav, :odd_emp, :aposta_fav, :aposta_emp, 'Aberta')"
        ];

        foreach ($colunasTentativa as $query) {
            try {
                $stmt = $db->prepare($query);
                $stmt->bindParam(':casa', $timeCasa);
                $stmt->bindParam(':visitante', $timeVisitante);
                $stmt->bindParam(':data_jogo', $dataJogo);
                $stmt->bindParam(':odd_fav', $oddFavorito);
                $stmt->bindParam(':odd_emp', $oddEmpate);
                $stmt->bindParam(':aposta_fav', $apostaFav);
                $stmt->bindParam(':aposta_emp', $apostaEmp);
                if ($stmt->execute()) {
                    $gravou = true;
                    break;
                }
            } catch (PDOException $e) {
                continue;
            }
        }

        if ($gravou) {
            $mensagemStatus = "Ótimo! O confronto <strong>{$timeCasa} x {$timeVisitante}</strong> foi gravado no diário de operações.";
            $tipoAlerta = "success";
        } else {
            $mensagemStatus = "Erro ao gravar a operação. Verifique as colunas da sua tabela 'apostas'.";
            $tipoAlerta = "danger";
        }
    } catch (Exception $e) {
        $mensagemStatus = "Erro no processamento: " . $e->getMessage();
        $tipoAlerta = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HedgeBet - Simulador Técnico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background-color: #1e293b !important; }
        .card-header { background-color: #1e293b; color: #fff; font-weight: 600; }
        .btn-adjust { width: 45px; height: 45px; font-size: 1.2rem; }
        .result-card { border-left: 5px solid #1e293b; transition: all 0.3s; }
        .result-green { border-left-color: #10b981; background-color: #f0fdf4; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-chart-line text-success me-2"></i>HedgeBet</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link active" href="index.php">Simulador</a>
            <a class="nav-link" href="operacoes.php">Diário de Apostas</a>
            <a class="nav-link" href="dashboard.php">Dashboard</a>
            <a class="nav-link text-danger fw-bold" href="logout.php"><i class="fa-solid fa-right-from-bracket me-1"></i> Sair</a>
        </div>
    </div>
</nav>

<div class="container">
    <?php if (!empty($mensagemStatus)): ?>
        <div class="alert alert-<?= $tipoAlerta ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= $mensagemStatus ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-sliders me-2"></i>Configurar Cenário</h5>
                </div>
                <div class="card-body">
                    <form action="index.php" method="POST" id="formSimulador">
                        <input type="hidden" name="acao" value="registrar_operacao">
                        <input type="hidden" name="prioridade_form" id="prioridadeForm" value="equilibrado">
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Time Casa</label>
                                <input type="text" class="form-control" name="time_casa" placeholder="Ex: Portugal" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Time Visitante</label>
                                <input type="text" class="form-control" name="time_visitante" placeholder="Ex: UZB" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Data do Confronto</label>
                            <input type="date" class="form-control" name="data_jogo" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label fw-bold">ODD do Favorito (Back Vitória)</label>
                            <input type="number" step="0.01" min="1.01" class="form-control form-control-lg" name="odd_favorito_form" id="oddFavorito" value="2.22" required>
                        </div>
                        <div class="mb-3 border p-2 rounded bg-light">
    <label class="form-label fw-bold text-primary">
        ODD Chance Dupla (Casa)
    </label>

    <input
        type="number"
        step="0.01"
        min="1.01"
        class="form-control"
        name="odd_casa_form"
        id="oddCasa"
        value="1.26"
        required
    >

    <small class="text-muted">
        Benchmark para superar o retorno da casa.
    </small>
</div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">ODD da Cobertura (Empate)</label>
                            <input type="number" step="0.01" min="1.01" class="form-control form-control-lg" name="odd_empate_form" id="oddEmpate" value="3.60" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label d-block text-center fw-bold fs-5">Valor do Aporte (Stake Total)</label>
                            <div class="d-flex justify-content-center align-items-center gap-3">
                                <button type="button" class="btn btn-outline-danger btn-adjust rounded-circle" id="btnMenos"><i class="fa-solid fa-minus"></i></button>
                                <input type="number" step="1" min="1" class="form-control form-control-lg text-center fs-3 fw-bold" name="stake_total_form" id="stakeTotal" value="25" style="max-width: 140px;">
                                <button type="button" class="btn btn-outline-success btn-adjust rounded-circle" id="btnMais"><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 py-3 fw-bold"><i class="fa-solid fa-floppy-disk me-2"></i>Enviar para Registro</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 d-flex flex-column gap-2">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-chart-pie me-2"></i>Análise de Distribuição Assimétrica</h5>
                    
                    <div class="btn-group w-100 mt-1" role="group" aria-label="Alvo do Lucro Principal">
                        <input type="radio" class="btn-check" name="btnPrioridade" id="prioridadeEquilibrado" value="equilibrado" checked autocomplete="off">
                        <label class="btn btn-outline-light btn-sm fw-semibold" for="prioridadeEquilibrado">⚖️ Equilibrado</label>

                        <input type="radio" class="btn-check" name="btnPrioridade" id="prioridadeFavorito" value="favorito" autocomplete="off">
                        <label class="btn btn-outline-light btn-sm fw-semibold" for="prioridadeFavorito">🏆 Focar no Favorito</label>

                        <input type="radio" class="btn-check" name="btnPrioridade" id="prioridadeEmpate" value="empate" autocomplete="off">
                        <label class="btn btn-outline-light btn-sm fw-semibold" for="prioridadeEmpate">🤝 Focar no Empate</label>
                    </div>
                </div>
                
                <div class="card-body d-flex flex-column justify-content-between">
                    
                    <div class="card result-card result-green p-3 mb-3 shadow-sm" id="cardFav">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark fs-5"><i class="fa-solid fa-trophy text-warning me-2"></i>Cenário: Vitória do Favorito</span>
                            <span class="badge bg-success" id="porcentagemFav">+0.00%</span>
                        </div>
                        <div class="row text-center bg-white py-2 rounded border mx-0">
                            <div class="col-4">
                                <small class="text-muted d-block">Aposta Sugerida</small>
                                <strong class="fs-5 text-dark" id="txtApostaFav">R$ 0,00</strong>
                            </div>
                            <div class="col-4 border-start border-end">
                                <small class="text-muted d-block">Retorno Bruto</small>
                                <span class="fs-5 fw-semibold" id="txtRetornoFav">R$ 0,00</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Lucro Líquido</small>
                                <strong class="fs-5 text-success" id="txtLucroFav">R$ 0,00</strong>
                            </div>
                        </div>
                    </div>

                    <div class="card result-card result-green p-3 mb-3 shadow-sm" id="cardEmp">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark fs-5"><i class="fa-solid fa-handshake text-primary me-2"></i>Cenário: Cobertura no Empate</span>
                            <span class="badge bg-success" id="porcentagemEmp">+0.00%</span>
                        </div>
                        <div class="row text-center bg-white py-2 rounded border mx-0">
                            <div class="col-4">
                                <small class="text-muted d-block">Aposta Sugerida</small>
                                <strong class="fs-5 text-dark" id="txtApostaEmp">R$ 0,00</strong>
                            </div>
                            <div class="col-4 border-start border-end">
                                <small class="text-muted d-block">Retorno Bruto</small>
                                <span class="fs-5 fw-semibold" id="txtRetornoEmp">R$ 0,00</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Lucro Líquido</small>
                                <strong class="fs-5 text-success" id="txtLucroEmp">R$ 0,00</strong>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0 d-flex align-items-center">
                        <i class="fa-solid fa-shield-halved fs-4 me-3"></i>
                        <div>
                            <strong>Blindagem de Capital Dinâmica:</strong> Os pesos e cálculos mudam dinamicamente baseados na sua convicção de mercado selecionada no seletor acima.
                        </div>
                    </div>
                    <div class="card p-3 mt-3 shadow-sm" id="cardValidacao">
    <h6 class="fw-bold">
        <i class="fa-solid fa-shield-halved me-2"></i>
        Status de Eficiência
    </h6>
    
        <div id="statusMensagem" class="alert alert-info mb-0">Aguardando cálculo...</div>
    </div>
</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const oddFavInput = document.getElementById("oddFavorito");
    const oddEmpInput = document.getElementById("oddEmpate");
    const stakeInput = document.getElementById("stakeTotal");
    const btnMais = document.getElementById("btnMais");
    const btnMenos = document.getElementById("btnMenos");
    const prioridadeHidden = document.getElementById("prioridadeForm");

    function getPrioridadeAtiva() {
        return document.querySelector('input[name="btnPrioridade"]:checked').value;
    }

    function recalcularSimulacao() {
        const oddFav = parseFloat(oddFavInput.value) || 0;
        const oddEmp = parseFloat(oddEmpInput.value) || 0;
        const stakeTotal = parseFloat(stakeInput.value) || 0;
        const prioridade = getPrioridadeAtiva();

        // Atualiza o input oculto para mandar o valor certo pro PHP salvar
        prioridadeHidden.value = prioridade;

        if (oddFav <= 1 || oddEmp <= 1 || stakeTotal <= 0) return;

        const probFav = 1 / oddFav;
        const probEmp = 1 / oddEmp;
        const somaProb = probFav + probEmp;

        let apostaFav = 0;
        let apostaEmp = 0;
        let mercadoSemMargem = false;

        if (somaProb < 1) {
            // ARBITRAGEM EXISTENTE: Aplica a regra baseada na convicção escolhida
            if (prioridade === 'favorito') {
                // Focar no Favorito: Cobre o empate e manda todo o resto do lucro para o favorito
                apostaEmp = Math.round((stakeTotal / oddEmp) * 100) / 100;
                apostaFav = Math.round((stakeTotal - apostaEmp) * 100) / 100;
            } else if (prioridade === 'empate') {
                // Focar no Empate: Cobre o favorito e manda todo o resto do lucro para o empate
                apostaFav = Math.round((stakeTotal / oddFav) * 100) / 100;
                apostaEmp = Math.round((stakeTotal - apostaFav) * 100) / 100;
            } else {
                // Equilibrado: Distribuição proporcional inversa tradicional (Lucro Igualitário)
                apostaFav = Math.round(((probFav / somaProb) * stakeTotal) * 100) / 100;
                apostaEmp = Math.round(((probEmp / somaProb) * stakeTotal) * 100) / 100;
                
                const dif = stakeTotal - (apostaFav + apostaEmp);
                if (dif !== 0) {
                    apostaFav = Math.round((apostaFav + dif) * 100) / 100;
                }
            }
        } else {
            // MERCADO SEM MARGEM: Ativa o Plano B de proteção de danos independente da escolha
            mercadoSemMargem = true;
            apostaEmp = Math.ceil((stakeTotal / oddEmp) * 100) / 100; 
            apostaFav = Math.round((stakeTotal - apostaEmp) * 100) / 100;
        }

        // Cálculos de Retorno e Lucro Líquido
        const retornoFav = Math.round((apostaFav * oddFav) * 100) / 100;
        const lucroFav = Math.round((retornoFav - stakeTotal) * 100) / 100;
        const porcFav = Math.round((lucroFav / stakeTotal) * 10000) / 100;

        const retornoEmp = Math.round((apostaEmp * oddEmp) * 100) / 100;
        const lucroEmp = Math.round((retornoEmp - stakeTotal) * 100) / 100;
        const porcEmp = Math.round((lucroEmp / stakeTotal) * 10000) / 100;

        // Injeta os dados na tela formatados
        document.getElementById("txtApostaFav").innerText = `R$ ${apostaFav.toFixed(2).replace('.', ',')}`;
        document.getElementById("txtRetornoFav").innerText = `R$ ${retornoFav.toFixed(2).replace('.', ',')}`;
        document.getElementById("txtLucroFav").innerText = `R$ ${lucroFav.toFixed(2).replace('.', ',')}`;
        document.getElementById("porcentagemFav").innerText = `${lucroFav >= 0 ? '+' : ''}${porcFav.toFixed(2)}%`;

        document.getElementById("txtApostaEmp").innerText = `R$ ${apostaEmp.toFixed(2).replace('.', ',')}`;
        document.getElementById("txtRetornoEmp").innerText = `R$ ${retornoEmp.toFixed(2).replace('.', ',')}`;
        document.getElementById("txtLucroEmp").innerText = `R$ ${lucroEmp.toFixed(2).replace('.', ',')}`;
        document.getElementById("porcentagemEmp").innerText = `${lucroEmp >= 0 ? '+' : ''}${porcEmp.toFixed(2)}%`;
        
        // Estilização dinâmica inteligente de segurança
        if (mercadoSemMargem) {
            document.getElementById("porcentagemFav").className = "badge bg-secondary";
            document.getElementById("porcentagemEmp").className = "badge bg-secondary";
            document.querySelectorAll('.result-card').forEach(el => el.style.borderLeftColor = '#64748b');
        } else {
            // Se houver margem, define cores normais baseadas em lucro positivo ou nulo/negativo
            document.getElementById("porcentagemFav").className = lucroFav >= 0 ? "badge bg-success" : "badge bg-danger";
            document.getElementById("porcentagemEmp").className = lucroEmp >= 0 ? "badge bg-success" : "badge bg-danger";
            document.querySelectorAll('.result-card').forEach(el => el.style.borderLeftColor = '#10b981');
        }
    }

    // Escuta cliques nos botões de alternância de prioridade
    document.querySelectorAll('input[name="btnPrioridade"]').forEach(radio => {
        radio.addEventListener("change", recalcularSimulacao);
    });

    btnMais.addEventListener("click", function() { stakeInput.value = parseInt(stakeInput.value || 0) + 5; recalcularSimulacao(); });
    btnMenos.addEventListener("click", function() { let atual = parseInt(stakeInput.value || 0); if (atual > 5) { stakeInput.value = atual - 5; recalcularSimulacao(); } });
    
    oddFavInput.addEventListener("input", recalcularSimulacao);
    oddEmpInput.addEventListener("input", recalcularSimulacao);
    stakeInput.addEventListener("input", recalcularSimulacao);

    recalcularSimulacao();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>