<?php

declare(strict_types=1);

// Fuso horário da aplicação e do banco: America/Sao_Paulo (UTC-3, sem DST).
date_default_timezone_set('America/Sao_Paulo');
define('DB_TIMEZONE', '-03:00');

// Configuração do banco. Usamos getenv() para que o mesmo código funcione
// no Docker (onde o host do MySQL é o serviço "db") e em execução local.
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'controle_de_frota');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'abc123');
