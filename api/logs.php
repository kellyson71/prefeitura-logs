<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$project = $_GET['project'] ?? 'general';

$logs = [];

$names = ['error.log', 'access.log', 'debug.log', 'system.log', 'worker.log', 'mail.log', 'db_sync.log', 'api_gateway.log'];
$snippets = [
    "PHP Warning: Undefined variable on line 32 in /var/www/html/app/index.php\nStack trace:\n#0 {main}",
    "[2026-02-23 10:15:00] INFO: User 1204 logged in successfully from IP 192.168.1.10",
    "Tail recursive call detected in worker. Re-allocating memory block.\nMemory usage: 45MB",
    "PDOException: SQLSTATE[HY000] [2002] Connection refused in database.php:10\nStack trace:\n#0 /var/www/html/src/db.php(25): PDO->__construct()\n#1 {main}",
    "GET /api/v1/users 200 OK 45ms",
    "SMTP connect() failed. https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting\nError: Connection timed out",
    "[ERROR] Unable to sync data with remote server. Timeout after 30s.",
    "DEBUG: Payload received: {\"user_id\": 451, \"action\": \"update_profile\", \"timestamp\": 1735689600}"
];

$count = rand(3, 12);
for ($i = 0; $i < $count; $i++) {
    $logName = $names[array_rand($names)];
    $file = strtolower(str_replace(' ', '_', $project)) . '_' . $logName;
    
    // Prevent exactly same filenames
    if ($i > 0) {
        $file = str_replace('.log', '_' . date('Ymd_His', strtotime('-' . rand(1, 100) . ' hours')) . '.log', $file);
    }

    $previewContent = $snippets[array_rand($snippets)];
    $fullContent = $previewContent . "\n\n...\n[End of snippet]\n\n" . str_repeat("Additional log context... ", 20);

    $logs[] = [
        "file" => $file,
        "size_bytes" => rand(512, 15485760), // De 512B a 15MB
        "modified" => date('Y-m-d\TH:i:s\Z', strtotime('-' . rand(0, 72) . ' hours')),
        "preview" => $fullContent
    ];
}

// Default sort by newest
usort($logs, function($a, $b) {
    return strtotime($b['modified']) - strtotime($a['modified']);
});

echo json_encode([
    "generated" => gmdate('Y-m-d\TH:i:s\Z'),
    "project" => $project,
    "count" => count($logs),
    "data" => $logs
]);
