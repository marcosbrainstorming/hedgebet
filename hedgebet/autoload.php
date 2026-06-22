<?php
/**
 * HedgeBet - Autoload de Classes Simples (PSR-4 Manual)
 * Arquivo: autoload.php
 */

spl_autoload_register(function ($class) {
    // Diretório base onde as classes do sistema estão localizadas
    $base_dir = __DIR__ . '/src/';

    /*
     * Se no futuro decidires usar namespaces (ex: src/Math/Calculator.php),
     * este código adapta o caminho trocando as barras invertidas do namespace
     * pelas barras de diretório do sistema operacional.
     */
    $class_path = str_replace('\\', '/', $class);

    // Monta o caminho completo do arquivo
    $file = $base_dir . $class_path . '.php';

    // Se o arquivo da classe existir dentro de src/, carrega-o
    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    /*
     * Caso a classe seja um Repositório ou esteja numa subpasta direta 
     * e ainda não estejas a usar Namespaces declarados no topo do arquivo,
     * este bloco garante o carregamento procurando recursivamente pelas pastas mais importantes.
     */
    $sub_folders = ['Math/', 'Repository/', 'Helpers/'];
    foreach ($sub_folders as $folder) {
        $file_alternative = $base_dir . $folder . $class . '.php';
        if (file_exists($file_alternative)) {
            require_once $file_alternative;
            return true;
        }
    }

    return false;
});