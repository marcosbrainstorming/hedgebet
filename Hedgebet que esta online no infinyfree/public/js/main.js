document.addEventListener("DOMContentLoaded", function() {
    // Referências aos elementos
    const oddFavInput = document.getElementById("oddFavorito");
    const oddEmpInput = document.getElementById("oddEmpate");
    const oddCasaInput = document.getElementById("oddCasa"); // Novo campo
    const stakeInput = document.getElementById("stakeTotal");
    const statusMsg = document.getElementById("statusMensagem"); // A div que não está mudando
    const prioridadeHidden = document.getElementById("prioridadeForm");

    function getPrioridadeAtiva() {
        return document.querySelector('input[name="btnPrioridade"]:checked').value;
    }

    function recalcularSimulacao() {
        // Leitura dos valores
        const oddFav = parseFloat(oddFavInput.value) || 0;
        const oddEmp = parseFloat(oddEmpInput.value) || 0;
        const oddCasa = parseFloat(oddCasaInput.value) || 0;
        const stakeTotal = parseFloat(stakeInput.value) || 0;
        const prioridade = getPrioridadeAtiva();

        prioridadeHidden.value = prioridade;
        if (oddFav <= 1 || oddEmp <= 1 || stakeTotal <= 0) return;

        // Cálculos
        const probFav = 1 / oddFav;
        const probEmp = 1 / oddEmp;
        const somaProb = probFav + probEmp;
        let apostaFav = 0, apostaEmp = 0;

        if (somaProb < 1) {
            if (prioridade === 'favorito') {
                apostaEmp = Math.round((stakeTotal / oddEmp) * 100) / 100;
                apostaFav = Math.round((stakeTotal - apostaEmp) * 100) / 100;
            } else if (prioridade === 'empate') {
                apostaFav = Math.round((stakeTotal / oddFav) * 100) / 100;
                apostaEmp = Math.round((stakeTotal - apostaFav) * 100) / 100;
            } else {
                apostaFav = Math.round(((probFav / somaProb) * stakeTotal) * 100) / 100;
                apostaEmp = Math.round(((probEmp / somaProb) * stakeTotal) * 100) / 100;
            }
        } else {
            apostaEmp = Math.ceil((stakeTotal / oddEmp) * 100) / 100;
            apostaFav = Math.round((stakeTotal - apostaEmp) * 100) / 100;
        }

        const lucroFav = Math.round(((apostaFav * oddFav) - stakeTotal) * 100) / 100;
        const lucroEmp = Math.round(((apostaEmp * oddEmp) - stakeTotal) * 100) / 100;

        // Atualiza interface
        document.getElementById("txtApostaFav").innerText = `R$ ${apostaFav.toFixed(2).replace('.', ',')}`;
        document.getElementById("txtLucroFav").innerText = `R$ ${lucroFav.toFixed(2).replace('.', ',')}`;
        document.getElementById("txtApostaEmp").innerText = `R$ ${apostaEmp.toFixed(2).replace('.', ',')}`;
        document.getElementById("txtLucroEmp").innerText = `R$ ${lucroEmp.toFixed(2).replace('.', ',')}`;

        // Lógica de Status de Eficiência
        const valorX = stakeTotal * oddCasa;
        const lucroCobertura = Math.max(lucroFav, lucroEmp);

        if (statusMsg) {
            if (lucroCobertura >= (valorX - stakeTotal)) {
                statusMsg.className = "alert alert-success mb-0";
                statusMsg.innerHTML = "<strong>Operação Vantajosa!</strong> Supera a casa ✅";
            } else {
                statusMsg.className = "alert alert-warning mb-0";
                statusMsg.innerHTML = "<strong>Ineficiente:</strong> Abaixo da casa ❌";
            }
        }
    }

    // listeners para garantir que qualquer mudança dispare o cálculo
    [oddFavInput, oddEmpInput, oddCasaInput, stakeInput].forEach(el => {
        el.addEventListener("input", recalcularSimulacao);
    });
    document.querySelectorAll('input[name="btnPrioridade"]').forEach(r => {
        r.addEventListener("change", recalcularSimulacao);
    });

    // Cálculo inicial
    recalcularSimulacao();
});
// ... (restante do código) ...

    function recalcularSimulacao() {
        const oddFav = parseFloat(oddFavInput.value) || 0;
        const oddEmp = parseFloat(oddEmpInput.value) || 0;
        const oddCasa = parseFloat(oddCasaInput.value) || 0;
        const stakeTotal = parseFloat(stakeInput.value) || 0;

        // DEBUG: Isso vai aparecer no seu F12 -> Console
        console.log("Calculando... OddCasa:", oddCasa, "Stake:", stakeTotal);

        const valorX = stakeTotal * oddCasa;
        // ... (resto do cálculo) ...

        if (statusMsg) {
            console.log("Elemento statusMsg encontrado!"); // Se isso não aparecer no F12, o ID está errado
            if (lucroCobertura >= (valorX - stakeTotal)) {
                statusMsg.className = "alert alert-success mb-0";
                statusMsg.innerHTML = "<strong>Operação Vantajosa!</strong> Supera a casa ✅";
            } else {
                statusMsg.className = "alert alert-warning mb-0";
                statusMsg.innerHTML = "<strong>Ineficiente:</strong> Abaixo da casa ❌";
            }
        } else {
            console.error("ERRO: Elemento 'statusMensagem' não encontrado no HTML!");
        }
    }