<?php

declare(strict_types=1);

namespace App;

use App\Services\ConfigService;

require '../../vendor/autoload.php';

$env_file = dirname(dirname(__DIR__)) . '/.env';
if (file_exists($env_file)) {
    ConfigService::loadEnvFile($env_file);
}

App::build([
    'GITHUB_CLIENT_ID' => ConfigService::get('GITHUB_CLIENT_ID'),
    'GITHUB_CLIENT_SECRET' => ConfigService::get('GITHUB_CLIENT_SECRET')
])->run();
