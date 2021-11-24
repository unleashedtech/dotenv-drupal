<?php

/**
 * @file
 * Configures Drupal by utilizing the project's root .env file.
 */

use Symfony\Component\Dotenv\Dotenv;

/**
 * Gets the absolute path for the given path.
 *
 * @param string $path
 *   The path to resolve.
 * @param string $site_name
 *   The name of the site.
 *
 * @return string
 *   The absolute version of the path.
 */
function build_path(string $path, string $site_name): string {
  $path = str_replace([
    '{{site_name}}',
  ], [
    $site_name,
  ], $path);
  if (str_starts_with($path, '/')) {
    return $path;
  }
  return realpath(DRUPAL_ROOT . '/../' . $path);
}

// Define the site name if necessary.
if (!isset($site_name)) {
  $site_name = 'default';
}

// Load the environment variables.
$dotenv = new Dotenv();
$dotenv->loadEnv(DRUPAL_ROOT . '/../.env');

// Apply the Drupal settings.
$settings['hash_salt'] = $_ENV['HASH_SALT'];
$settings['file_public_path'] = build_path($_ENV['FILE_PUBLIC_PATH'], $site_name);
$settings['file_private_path'] = build_path($_ENV['FILE_PRIVATE_PATH'], $site_name);
$settings['config_sync_directory'] = build_path($_ENV['CONFIG_SYNC_PATH'], $site_name);
$db_url = parse_url($_ENV['DATABASE_URL']);
if (!isset($database)) {
  if (isset($db_url['path'])) {
    $database = substr($db_url['path'], 1);
  }
  else {
    $database = $site_name;
  }
}
$databases = [
  'default' =>
    [
      'default' =>
        [
          'database' => $database,
          'host' => $db_url['host'],
          'username' => $db_url['user'],
          'password' => $db_url['pass'],
          'prefix' => '',
          'port' => $db_url['port'],
          'namespace' => 'Drupal\\Core\\Database\\Driver\\' . $db_url['scheme'],
          'driver' => $db_url['scheme'],
        ],
    ],
];

// Include optional environment-based settings file.
$file = __DIR__ . '/../settings.' . strtolower($_ENV['APP_ENV']) . '.php';
if (file_exists($file)) {
  include $file;
}
