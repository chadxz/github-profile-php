<?php

namespace App\Services;

use Dotenv\Dotenv;

class Config {

    public static function load($env_file_dir) {
        if (file_exists(realpath("{$env_file_dir}/.env"))) {
            $dotenv = Dotenv::create($env_file_dir);
            $dotenv->load();
        }
    }

    public static function get($option, $default = null) {
        return getenv($option) ?: $default;
    }
}
