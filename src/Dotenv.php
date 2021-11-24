<?php

namespace UnleashedTech\Drupal\Dotenv;

use Symfony\Component\Dotenv\Dotenv as SymfonyDotenv;

/**
 * A class to help configure Drupal based on ENV file variables.
 */
class Dotenv
{

    /**
     * @var string Optional. The name of the database to use.
     */
    private string $databaseName;

    /**
     * @var string The name of the Drupal site being configured.
     */
    private string $siteName = 'default';

    /**
     * The class constructor.
     */
    public function __construct()
    {
        // Load data from ENV file(s) if APP_ENV is not defined.
        if (!isset($_SERVER['APP_ENV'])) {
            $root = $this->getProjectPath();
            $dotenv = new SymfonyDotenv();
            if (file_exists($root . '/.env') || file_exists($root . '/.env.dist')) {
                $dotenv->loadEnv(DRUPAL_ROOT . '/../.env');
            } elseif (file_exists($root . '/.env.dev')) {
                $_ENV['APP_ENV'] = 'dev';
                $dotenv->load(DRUPAL_ROOT . '/../.env.dev');
            }
        }
    }

    /**
     * Gets the name of the site.
     *
     * @return string
     *   The name of the site.
     */
    public function getSiteName(): string
    {
        return $this->siteName;
    }

    /**
     * Sets the name the site.
     * @param string $siteName
     *   The name the site.
     */
    public function setSiteName(string $siteName)
    {
        return $this->siteName = $siteName;
    }

    /**
     * Gets the environment name.
     *
     * @return string
     *   The environment name.
     */
    public function getEnvironmentName(): string
    {
        return strtolower($_ENV['APP_ENV']);
    }

    /**
     * Decorates the given data with data of the same type defined in PHP files.
     *
     * @param $data
     * @param $type
     */
    private function decorate(&$data, $type): void
    {
        $$type = &$data;
        $file = DRUPAL_ROOT . '/sites/' . $this->getSiteName() . '/' .
            $type . '.' . $this->getEnvironmentName() . '.php';
        if (file_exists($file)) {
            include $file;
        }
    }

    /**
     *
     * @return array
     */
    public function getConfig(): array
    {
        $config = [];
        if (isset($_ENV['SOLR_URL'])) {
            $parts = parse_url($_ENV['SOLR_URL']);
            $config['search_api.server.afa_solr']['backend_config']['connector_config'] = [
                'host' => $parts['host'],
                'port' => $parts['port'],
            ];
        }
        switch ($this->getEnvironmentName()) {
            case 'dev':
                $config['config_split.config_split.local']['status'] = TRUE;
                $config['environment_indicator.indicator'] = [
                    'name' => 'Development',
                    'fg_color' => '#110011',
                    'bg_color' => '#33aa33',
                ];
                $config['system.logging']['error_level'] = 'verbose';
                $config['system.performance'] = [
                    'css' => [
                        'preprocess' => false,
                    ],
                    'js' => [
                        'preprocess' => false,
                    ],
                ];
                break;

            case 'staging':
                $config['environment_indicator.indicator'] = [
                    'name' => 'Staging',
                    'fg_color' => '#ffe0b6',
                    'bg_color' => '#a15c00',
                ];
                break;

            case 'prod':
                $config['environment_indicator.indicator'] = [
                    'name' => 'Production',
                    'fg_color' => '#ffb6b6',
                    'bg_color' => '#870000',
                ];
                break;
        }
        $this->decorate($config, 'config');
        return $config;
    }

    public function getDatabases(): array
    {
        $db_url = parse_url($_ENV['DATABASE_URL']);
        return [
            'default' =>
                [
                    'default' =>
                        [
                            'database' => $this->getDatabaseName(),
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
    }

    public function getDatabaseName(): ?string
    {
        if (isset($this->databaseName)) {
            return $this->databaseName;
        }
        $result = parse_url($_ENV['DATABASE_URL'], PHP_URL_PATH);
        return (FALSE === $result) ? $this->getSiteName() : substr($result, 1);
    }

    function setDatabaseName(string $database)
    {
        $this->databaseName = $database;
    }

    public function getSettings(): array
    {
        $envName = $this->getEnvironmentName();
        $settings['config_sync_directory'] = $this->getConfigSyncPath();
        $settings['file_public_path'] = $this->getPublicFilePath();
        $settings['file_private_path'] = $this->getPrivateFilePath();
        $settings['container_yamls'] = [
            $this->getAppPath() . '/sites/' . $envName . '.services.yml',
        ];
        switch ($envName) {
            case 'dev':
                $settings['cache']['bins'] = [
                    'render' => 'cache.backend.null',
                    'page' => 'cache.backend.null',
                    'dynamic_page_cache' => 'cache.backend.null',
                ];
                $settings['hash_salt'] = 'foo';
                $settings['rebuild_access'] = FALSE;
                $settings['trusted_host_patterns'] = [
                    $_SERVER['VIRTUAL_HOST'],
                ];
                $settings['skip_permissions_hardening'] = TRUE;
                $settings['update_free_access'] = FALSE;
        }
        $this->decorate($settings, 'settings');
        return $settings;
    }

    /**
     * Gets the absolute path for the given path.
     *
     * @param string $path
     *   The path to resolve.
     *
     * @return string
     *   The absolute version of the path.
     */
    function buildPath(string $path): string
    {
        $path = $this->replacePlaceholders($path);
        if (str_starts_with($path, '/')) {
            return $path;
        }
        return realpath(DRUPAL_ROOT . '/../' . $path);
    }

    public function getAppPath(): string
    {
        return DRUPAL_ROOT;
    }

    public function getProjectPath(): string
    {
        return dirname(DRUPAL_ROOT, 1);
    }

    public function getPrivateFilePath(): string
    {
        return dirname(DRUPAL_ROOT, 1);
    }

    public function getPublicFilePath(): string
    {
        return $this->getAppPath() . '/sites/' . $this->getSiteName() . '/files';
    }

    public function getConfigSyncPath(): string
    {
        return $this->getProjectPath() . '/drupal/config/sync';
    }

    private function replacePlaceholders(string $string): string
    {
        return str_replace([
            '{{app_path}}',
            '{{project_path}}',
            '{{site_name}}',
            '{{virtual_host}}',
        ], [
            $this->getAppPath(),
            $this->getProjectPath(),
            $this->getSiteName(),
            $_SERVER['VIRTUAL_HOST'],
        ], $string);
    }
}
