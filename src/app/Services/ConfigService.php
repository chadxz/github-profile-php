<?php

namespace App\Services;

use Symfony\Component\Dotenv\Dotenv;

class ConfigService {
    /**
     * @param string $env_file_dir
     */
    public static function load(string $env_file_dir): void {
        $file_path = realpath("{$env_file_dir}/.env");

        if ($file_path !== false && file_exists($file_path)) {
            $dotenv = new Dotenv();
            $dotenv->load($file_path);
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
