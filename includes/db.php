<?php

declare(strict_types=1);


date_default_timezone_set('America/Sao_Paulo');
define('DB_TIMEZONE', '-03:00');



define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'controle_de_frota');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'abc123');
