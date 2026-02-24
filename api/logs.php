<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$projectFilter = $_GET['project'] ?? 'all';

// Vamos montar dinamicamente o path a partir do DOCUMENT_ROOT da Hostinger
// O script roda em /home/u492577848/domains/logs.protocolosead.com/public_html/api/logs.php
// E os logs estão provavelmente em /home/u492577848/.logs ou similar
$base = dirname($_SERVER['DOCUMENT_ROOT'], 2); // volta 2 niveis de /domains/.../public_html = /home/u492577848

// Tentativas de encontrar a pasta .logs correta
$possiblePaths = [
    $base . '/.logs',
    dirname($_SERVER['DOCUMENT_ROOT'], 3) . '/.logs', // volta 3 niveis
    realpath(__DIR__ . '/../../../../../.logs'), // Subindo a partir do arquivo api/logs.php
    realpath(__DIR__ . '/../../../../.logs'),
    realpath(__DIR__ . '/../../../.logs'),
    realpath(__DIR__ . '/../../.logs')
];

$logsDir = false;
foreach ($possiblePaths as $path) {
    if ($path && is_dir($path)) {
        $logsDir = $path;
        break;
    }
}

// Fallback para ambiente local se nada foi encontrado
if (!$logsDir) {
    $logsDir = realpath(__DIR__ . '/../.logs');
    if (!$logsDir || !is_dir($logsDir)) {
         echo json_encode([
            "generated" => gmdate('Y-m-d\TH:i:s\Z'),
            "project" => $projectFilter,
            "count" => 0,
            "data" => [],
            "error" => "Log directory not found. Please verify paths.",
            "debug_tested_paths" => $possiblePaths,
            "debug___DIR__" => __DIR__,
            "debug_DOCUMENT_ROOT" => $_SERVER['DOCUMENT_ROOT'] ?? 'not set'
        ]);
        exit;
    }
}

$logs = [];

// Busca todos os arquivos no diretório que correspondem ao filtro
// Ignoramos arquivos .gz por enquanto, focando nos arquivos ativos (.log, _log_...)
$files = glob($logsDir . '/*');

if ($files === false) {
    echo json_encode(['error' => 'Failed to read log directory']);
    exit;
}

// Função para ler as últimas N linhas do arquivo (eficiente para logs grandes)
function tailFile($filepath, $lines = 50) {
    $f = @fopen($filepath, "rb");
    if ($f === false) return "Unreadable file.";

    $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
    fseek($f, -1, SEEK_END);
    if (fread($f, 1) != "\n") $lines -= 1;
    
    $output = '';
    $chunk = '';

    while (ftell($f) > 0 && $lines >= 0) {
        $seek = min(ftell($f), $buffer);
        fseek($f, -$seek, SEEK_CUR);
        $chunk = fread($f, $seek);
        $output = $chunk . $output;
        fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
        $lines -= substr_count($chunk, "\n");
    }

    while ($lines++ < 0) {
        $output = substr($output, strpos($output, "\n") + 1);
    }
    
    fclose($f);
    return trim($output);
}

foreach ($files as $file) {
    if (!is_file($file)) continue;

    $filename = basename($file);
    
    // Ignorar arquivos ocultos e arquivos compactados .gz se preferirmos apenas live logs
    if (strpos($filename, '.') === 0) continue;
    
    // Filtro pelo projeto Selecionado na UI
    // Ex: "protocolosead_com", "estagiopaudosferros_com", "sema_paudosferros"
    $match = false;
    if ($projectFilter === 'all') {
        $match = true;
    } else {
        // Verifica se a string do projeto (ex: protocolosead) existe no nome do arquivo
        if (strpos($filename, $projectFilter) !== false) {
            $match = true;
        }
    }

    if ($match) {
        $preview = '';
        if (substr($filename, -3) === '.gz') {
            $preview = "[Arquivo Compactado - Pré-visualização indisponível. Baixe para visualizar.]";
        } else {
            $preview = tailFile($file, 150); // Pega as ultimas 150 linhas
            if (empty($preview)) {
                $preview = "[Arquivo vazio]";
            }
        }

        $logs[] = [
            "file" => $filename,
            "size_bytes" => filesize($file),
            "modified" => date('Y-m-d\TH:i:s\Z', filemtime($file)),
            "preview" => mb_convert_encoding($preview, 'UTF-8', 'UTF-8') // Evita erros JSON com chars inválidos
        ];
    }
}

// Ordenar do mais recentemente modificado para o mais antigo
usort($logs, function($a, $b) {
    return strtotime($b['modified']) - strtotime($a['modified']);
});

echo json_encode([
    "generated" => gmdate('Y-m-d\TH:i:s\Z'),
    "project" => $projectFilter,
    "count" => count($logs),
    "data" => $logs
]);
