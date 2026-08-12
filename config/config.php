<?php
declare(strict_types=1);

/*
 * Application configuration.
 *
 * Deployments may override the defaults with environment variables. Database
 * credentials are deliberately not stored in source control.
 */
$env = static function (string $name, $default = null) {
    $value = getenv($name);

    return $value === false ? $default : $value;
};

$environment = strtolower((string) $env('APP_ENV', 'production'));
if (!in_array($environment, ['development', 'testing', 'production'], true)) {
    $environment = 'production';
}

$debug = filter_var($env('APP_DEBUG', $environment === 'development' ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);

$config = [
    'app' => [
        'name' => (string) $env('APP_NAME', 'Peters Receptbank'),
        'version' => '0.7.0',
        'environment' => $environment,
        'debug' => $debug,
        'timezone' => (string) $env('APP_TIMEZONE', 'Europe/Stockholm'),
    ],
    'database' => [
        'driver' => (string) $env('DB_CONNECTION', 'mysql'),
        'host' => (string) $env('DB_HOST', '127.0.0.1'),
        'port' => (int) $env('DB_PORT', '3306'),
        'database' => (string) $env('DB_DATABASE', 'peters_receptbank'),
        'username' => (string) $env('DB_USERNAME', 'root'),
        'password' => (string) $env('DB_PASSWORD', ''),
        'charset' => (string) $env('DB_CHARSET', 'utf8mb4'),
    ],
];

defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'app');
defined('APP_NAME') || define('APP_NAME', $config['app']['name']);
defined('SITE_NAME') || define('SITE_NAME', APP_NAME);
defined('APP_VERSION') || define('APP_VERSION', $config['app']['version']);
defined('APP_ENV') || define('APP_ENV', $config['app']['environment']);
defined('APP_DEBUG') || define('APP_DEBUG', $config['app']['debug']);
defined('DB_HOST') || define('DB_HOST', $config['database']['host']);
defined('DB_PORT') || define('DB_PORT', $config['database']['port']);
defined('DB_NAME') || define('DB_NAME', $config['database']['database']);
defined('DB_USER') || define('DB_USER', $config['database']['username']);
defined('DB_PASS') || define('DB_PASS', $config['database']['password']);
defined('DB_CHARSET') || define('DB_CHARSET', $config['database']['charset']);

date_default_timezone_set($config['app']['timezone']);

return $config;
