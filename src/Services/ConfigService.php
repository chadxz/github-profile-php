<?php

namespace App\Services;

use Dotenv\Dotenv;

class ConfigService {
    /**
     * @param string $env_file_dir
     */
    public static function load(string $env_file_dir): void {
        $file_path = realpath("{$env_file_dir}/.env");

        if ($file_path !== false && file_exists($file_path)) {
            $dotenv = Dotenv::create($env_file_dir);
            $dotenv->load();
        }
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
