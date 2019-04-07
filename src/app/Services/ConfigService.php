<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Dotenv\Dotenv;

class ConfigService {
    /**
     * Helper for loading a .env file
     *
     * @param string $env_file
     */
    public static function loadEnvFile(string $env_file): void {
        $dotenv = new Dotenv();
        $dotenv->load($env_file);
    }

    /**
     * @param string $option
     * @return string
     * @throws \Exception
     */
    public static function get(string $option): string {
        $result = getenv($option);

        if ($result === false) {
            throw new \Exception(
                "Configuration value for '{$option}' does not exist.'"
            );
        }

        return $result;
    }
}
