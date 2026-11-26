<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require __DIR__ . '/includes/connect.php';

try {
    $pdo->query('SELECT 1');
} catch (PDOException $exception) {
    http_response_code(500);
    exit('erro');
}

http_response_code(200);
echo 'ok';
