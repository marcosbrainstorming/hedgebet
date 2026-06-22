/**
 * HedgeBet - Motores de Interação e Cálculos em Tempo Real
 * Arquivo: public/js/main.js
 */

document.addEventListener("DOMContentLoaded", function() {
    // Captura os elementos da tela do Simulador (index.php)
    const oddFavInput = document.getElementById("oddFavorito");
    const oddEmpInput = document.getElementById("oddEmpate");
    const stakeInput = document.getElementById("stakeTotal");
    const btnMais = document.getElementById("btnMais");
    const btnMenos = document.getElementById("btnMenos");

    // Verifica se os elementos existem na página atual antes de rodar o script
    if (!oddFavInput || !oddEmpInput || !stakeInput) return;

    /**
     * Formata números float no padrão de moeda brasileiro (R$ X.XXX,XX)
     */
    function formatarMoedaBR(valor) {
        return "R$ " + valor.toFixed(2).replace(".", ",").replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
    }

    /**
     * Executa a distribuição assimétrica baseada na sua tese de investimento
     */
    function recalcularSimulacao() {
        const oddFav = parseFloat(oddFavInput.value) || 0;
        const oddEmp = parseFloat(oddEmpInput.value) || 0;
        const stakeTotal = parseFloat(stakeInput.value) || 0;

        // Validação mínima de segurança
        if (oddFav <= 1 || oddEmp <= 1 || stakeTotal <= 0) return;

        // Viés estratégico do HedgeBet: Peso de 60% focado em extrair lucro do empate
        const pesoEmpate = 0.60;
        const probFav = 1 / oddFav;
        const probEmp = 1 / oddEmp;
        const somaProb = probFav + probEmp;

        // Distribuição de pesos baseada no viés
        const fatorFav = (probFav / somaProb) * (1 - (pesoEmpate - 0.5));
        const fatorEmp = (probEmp / somaProb) * (1 + (pesoEmpate - 0.5));
        const totalFatores = fatorFav + fatorEmp;

        // Define as frações exatas das stakes
        let apostaFav = Math.round(((fatorFav / totalFatores) * stakeTotal) * 100) / 100;
        let apostaEmp = Math.round((stakeTotal - apostaFav) * 100) / 100;

        // Projeções para o cenário: Vitória do Favorito
        const retornoFav = Math.round((apostaFav * oddFav) * 100) / 100;
        const lucroFav = Math.round((retornoFav - stakeTotal) * 100) / 100;
        const porcFav = ((lucroFav / stakeTotal) * 100).toFixed(2);

        // Projeções para o cenário: Empate
        const retornoEmp = Math.round((apostaEmp * oddEmp) * 100) / 100;
        const lucroEmp = Math.round((retornoEmp - stakeTotal) * 100) / 100;
        const porcEmp = ((lucroEmp / stakeTotal) * 100).toFixed(2);

        // Atualização dinâmica dos elementos HTML na interface
        document.getElementById("txtApostaFav").innerText = formatarMoedaBR(apostaFav);
        document.getElementById("txtRetornoFav").innerText = formatarMoedaBR(retornoFav);
        document.getElementById("txtLucroFav").innerText = formatarMoedaBR(lucroFav);
        document.getElementById("porcentagemFav").innerText = `${lucroFav >= 0 ? '+' : ''}${porcFav}%`;

        document.getElementById("txtApostaEmp").innerText = formatarMoedaBR(apostaEmp);
        document.getElementById("txtRetornoEmp").innerText = formatarMoedaBR(retornoEmp);
        document.getElementById("txtLucroEmp").innerText = formatarMoedaBR(lucroEmp);
        document.getElementById("porcentagemEmp").innerText = `${lucroEmp >= 0 ? '+' : ''}${porcEmp}%`;
    }

    // Ações dos botões + e - (Sobe/desce o aporte de 5 em 5 reais)
    btnMais.addEventListener("click", function() {
        stakeInput.value = parseInt(stakeInput.value || 0) + 5;
        recalcularSimulacao();
    });

    btnMenos.addEventListener("click", function() {
        let atual = parseInt(stakeInput.value || 0);
        if (atual > 5) {
            stakeInput.value = atual - 5;
            recalcularSimulacao();
        }
    });

    // Escuta alterações de digitação direta nos campos
    oddFavInput.addEventListener("input", recalcularSimulacao);
    oddEmpInput.addEventListener("input", recalcularSimulacao);
    stakeInput.addEventListener("input", recalcularSimulacao);

    // Roda o cálculo inicial assim que a tela abre
    recalcularSimulacao();
});