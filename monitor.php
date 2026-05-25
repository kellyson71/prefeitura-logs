<?php
/**
 * monitor.php — Verificação de uptime dos domínios da Prefeitura.
 * Configurar no cron Hostinger: * /5 * * * * php ~/domains/logs.protocolosead.com/public_html/monitor.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ─── Configuração ─────────────────────────────────────────────────────────────
define('ALERT_EMAILS', ['kellyson.medeiros.pdf@gmail.com', 'pmpfestagio@gmail.com']);
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'naoresponder@protocolosead.com');

$_env_file = __DIR__ . '/.env';
if (file_exists($_env_file)) {
    foreach (file($_env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if (str_starts_with(trim($_line), '#') || !str_contains($_line, '=')) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        $_ENV[trim($_k)] = trim($_v);
    }
}
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('LOG_DIR', __DIR__ . '/logs');
define('COOLDOWN_SECONDS', 1800); // só envia email a cada 30 min por domínio

$domains = [
    'estagio'     => ['name' => 'Portal Estagiários',   'url' => 'https://estagiopaudosferros.com'],
    'sema'        => ['name' => 'SEMA Licenças',         'url' => 'https://sema.protocolosead.com'],
    'curtapdf'    => ['name' => 'Festival CurtaPDF',     'url' => 'https://curtapdf.com.br'],
    'demutran'    => ['name' => 'DEMUTRAN',               'url' => 'https://demutranpaudosferros.com.br'],
    'protocolo'   => ['name' => 'Protocolo SEAD',        'url' => 'https://protocolosead.com'],
];

// ─── Funções ──────────────────────────────────────────────────────────────────
function checkDomain(string $url): array {
    $start = microtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY         => false, // precisa do body para checar erros PHP
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'PrefeituraMonitor/1.0',
    ]);
    $body      = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $latency   = round((microtime(true) - $start) * 1000);
    $error     = curl_error($ch);
    curl_close($ch);

    // Detecta erros PHP no conteúdo mesmo com HTTP 200
    $php_error  = false;
    $php_detail = '';
    if ($body && $http_code >= 200 && $http_code < 400) {
        $error_patterns = [
            'Fatal error'    => 'Fatal error',
            'Parse error'    => 'Parse error',
            'Uncaught Error' => 'Uncaught Error',
            'Uncaught Exception' => 'Uncaught Exception',
            'Call to undefined' => 'Call to undefined',
        ];
        foreach ($error_patterns as $pattern => $label) {
            if (stripos($body, $pattern) !== false) {
                $php_error  = true;
                $php_detail = $label;
                break;
            }
        }
    }

    return [
        'status'     => $http_code,
        'latency'    => $latency,
        'ok'         => ($http_code >= 200 && $http_code < 400) && !$php_error,
        'error'      => $error,
        'php_error'  => $php_error,
        'php_detail' => $php_detail,
    ];
}

function logUptime(string $id, string $name, array $result): void {
    $logFile = LOG_DIR . '/uptime_' . date('Y-m') . '.log';
    $ts      = date('Y-m-d H:i:s');
    $status  = $result['ok'] ? 'OK' : ($result['php_error'] ? 'PHP_ERROR' : 'DOWN');
    $line    = "$ts | $id | $name | HTTP={$result['status']} | {$result['latency']}ms | $status";
    if (!empty($result['php_detail'])) {
        $line .= " | php={$result['php_detail']}";
    } elseif (!empty($result['error'])) {
        $line .= " | err={$result['error']}";
    }
    file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function shouldSendAlert(string $id): bool {
    $flagFile = LOG_DIR . "/.alert_cooldown_$id";
    if (!file_exists($flagFile)) return true;
    return (time() - filemtime($flagFile)) > COOLDOWN_SECONDS;
}

function markAlertSent(string $id): void {
    $flagFile = LOG_DIR . "/.alert_cooldown_$id";
    touch($flagFile);
}

function sendAlert(string $id, string $name, string $url, array $result): bool {
    if (!shouldSendAlert($id)) return false;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_USER, 'Monitor Prefeitura');
        foreach (ALERT_EMAILS as $email) {
            $mail->addAddress($email);
        }
        $ts   = date('d/m/Y H:i:s');
        $http = $result['status'];
        $lat  = $result['latency'];

        $is_php_error = $result['php_error'] ?? false;

        if ($is_php_error) {
            $titulo   = "[ERRO PHP] $name com erro na pagina";
            $subtitulo = 'Erro PHP detectado no conteudo';
            $cor       = '#d97706';
            $icone     = '🔶';
            $detalhe   = "PHP: {$result['php_detail']}";
        } else {
            $titulo   = "[ALERTA] $name fora do ar";
            $subtitulo = 'Site fora do ar';
            $cor       = '#dc2626';
            $icone     = '🔴';
            $detalhe   = $result['error'] ? "Erro: {$result['error']}" : '';
        }

        $mail->Subject = '=?UTF-8?B?' . base64_encode($titulo) . '?=';

        $mail->isHTML(true);
        $mail->Body = "
        <div style='font-family:sans-serif;max-width:480px'>
            <h2 style='color:$cor'>$icone $subtitulo</h2>
            <table style='border-collapse:collapse;width:100%'>
                <tr><td style='padding:4px 8px;font-weight:bold'>Sistema</td><td>$name</td></tr>
                <tr><td style='padding:4px 8px;font-weight:bold'>URL</td><td>$url</td></tr>
                <tr><td style='padding:4px 8px;font-weight:bold'>Código HTTP</td><td>$http</td></tr>
                <tr><td style='padding:4px 8px;font-weight:bold'>Latência</td><td>{$lat}ms</td></tr>
                <tr><td style='padding:4px 8px;font-weight:bold'>Detectado às</td><td>$ts</td></tr>
                " . ($detalhe ? "<tr><td style='padding:4px 8px;font-weight:bold'>Detalhe</td><td>$detalhe</td></tr>" : '') . "
            </table>
            <p style='color:#6b7280;font-size:12px;margin-top:16px'>
                Proximo alerta somente apos 30 min.
            </p>
        </div>";

        $mail->AltBody = "$titulo | $url | HTTP: $http | $ts";
        $mail->send();
        markAlertSent($id);

        // Registrar alerta no log
        $alertLog = LOG_DIR . '/alerts_' . date('Y-m') . '.log';
        file_put_contents($alertLog,
            date('Y-m-d H:i:s') . " | ALERTA_ENVIADO | $id | HTTP=$http\n",
            FILE_APPEND | LOCK_EX);

        return true;
    } catch (Exception $e) {
        error_log("[monitor] Falha ao enviar email: " . $mail->ErrorInfo);
        return false;
    }
}

function writeStatusJson(array $results): void {
    $file = LOG_DIR . '/status.json';
    file_put_contents($file, json_encode([
        'updated' => date('Y-m-d H:i:s'),
        'domains' => $results,
    ], JSON_PRETTY_PRINT), LOCK_EX);
}

// ─── Modo teste de email ───────────────────────────────────────────────────────
if (PHP_SAPI === 'cli' && in_array('--test-email', $argv ?? [])) {
    echo "Enviando email de teste para: " . implode(', ', ALERT_EMAILS) . "...\n";
    $ok = sendAlert('teste', 'TESTE - Monitor Prefeitura', 'https://example.com', [
        'status'  => 503,
        'latency' => 0,
        'ok'      => false,
        'error'   => 'Teste manual',
    ]);
    echo $ok ? "Email enviado com sucesso!\n" : "Falha ao enviar (verifique SMTP_PASS).\n";
    exit;
}

// ─── Loop principal ───────────────────────────────────────────────────────────
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

$allResults = [];

foreach ($domains as $id => $info) {
    $result = checkDomain($info['url']);
    logUptime($id, $info['name'], $result);

    $issue = '';
    if (!$result['ok']) {
        $issue = $result['php_error'] ? 'php_error' : 'down';
    }

    $allResults[$id] = [
        'name'    => $info['name'],
        'url'     => $info['url'],
        'ok'      => $result['ok'],
        'status'  => $result['status'],
        'latency' => $result['latency'],
        'issue'   => $issue,
        'checked' => date('Y-m-d H:i:s'),
    ];

    if (!$result['ok']) {
        sendAlert($id, $info['name'], $info['url'], $result);
    } else {
        // Limpa cooldown quando volta ao normal
        $flagFile = LOG_DIR . "/.alert_cooldown_$id";
        if (file_exists($flagFile)) {
            unlink($flagFile);
        }
    }
}

writeStatusJson($allResults);

if (PHP_SAPI === 'cli') {
    foreach ($allResults as $id => $r) {
        if ($r['ok']) {
            $icon = '✓';
            $extra = '';
        } elseif ($r['issue'] === 'php_error') {
            $icon = '🔶';
            $extra = ' [ERRO PHP]';
        } else {
            $icon = '✗';
            $extra = ' [FORA DO AR]';
        }
        echo "$icon {$r['name']} — HTTP {$r['status']} ({$r['latency']}ms)$extra\n";
    }
}
