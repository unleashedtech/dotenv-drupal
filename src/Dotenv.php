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
     * @var bool Whether the default site is allowed in a multi-site configuration.
     */
    private bool $isMultiSiteDefaultSiteAllowed = FALSE;

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
                $_SERVER['APP_ENV'] = 'dev';
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
        return strtolower($_SERVER['APP_ENV']);
    }

    /**
     * Alters the given data with data of the same type defined in PHP files.
     *
     * @param $data
     *   The data to alter.
     * @param $type
     *   The type of data being altered (e.g. settings, config, databases).
     */
    private function alter(&$data, $type): void
    {
        $$type = &$data;

        // Allow alteration via the `default` directory.
        $file = DRUPAL_ROOT . '/sites/default/' .
            $type . '.' . $this->getEnvironmentName() . '.php';
        if (file_exists($file)) {
            include $file;
        }

        // Allow alteration via non-`default` directories.
        $siteName = $this->getSiteName();
        if ($siteName !== 'default') {
            $file = DRUPAL_ROOT . '/sites/' . $siteName . '/' .
                $type . '.' . $this->getEnvironmentName() . '.php';
            if (file_exists($file)) {
                include $file;
            }
        }
    }

    /**
     *
     * @return array
     */
    public function getConfig(): array
    {
        $config = [];
        if (isset($_SERVER['SOLR_URL'])) {
            $parts = parse_url($_SERVER['SOLR_URL']);
            $name = $parts['fragment'] ?? 'default';
            $config['search_api.server.' . $name]['backend_config']['connector_config'] = [
                'scheme' => $parts['scheme'] ?? 'http',
                'host' => $parts['host'] ?? 'localhost',
                'port' => $parts['port'] ?? 8983,
                'path' => $parts['path'] ?? '/',
                'core' => $parts['user'] ?? 'default',
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
        $this->alter($config, 'config');
        return $config;
    }

    public function getDatabases(): array
    {
        $db_url = parse_url($_SERVER['DATABASE_URL']);
        $databases = [
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
        $this->alter($databases, 'databases');
        return $databases;
    }

    public function getDatabaseName(): string
    {
        if (isset($this->databaseName)) {
            return $this->databaseName;
        }
        $result = parse_url($_SERVER['DATABASE_URL'], PHP_URL_PATH);
        if (NULL === $result || trim($result) === '/') {
            // Multi-site configuration detected. Use the site name.
            $result = $this->getSiteName();
            if ($result === 'default' && !$this->isMultiSiteDefaultSiteAllowed()) {
                header("HTTP/1.1 401 Unauthorized");
                die('Unauthorized');
            }
        } else {
            $result = substr($result, 1);
        }
        if (NULL === $result || preg_replace('/[^a-z0-9_]/', '', $result) === '') {
            throw new \UnexpectedValueException('Database name could not be computed.');
        }
        return $result;
    }

    public function setDatabaseName(string $database): void
    {
        $this->databaseName = $database;
    }

    public function isMultiSiteDefaultSiteAllowed(): bool
    {
        return $this->isMultiSiteDefaultSiteAllowed;
    }

    public function setMultiSiteDefaultSiteAllowed(bool $allowed = TRUE): void
    {
        $this->isMultiSiteDefaultSiteAllowed = $allowed;
    }

    public function getSettings(): array
    {
        $envName = $this->getEnvironmentName();
        $settings['update_free_access'] = FALSE;
        $settings['file_scan_ignore_directories'] = [
            'node_modules',
            'bower_components',
        ];
        $settings['entity_update_batch_size'] = 50;
        $settings['entity_update_backup'] = TRUE;
        $settings['migrate_node_migrate_type_classic'] = FALSE;
        $settings['config_sync_directory'] = $this->getConfigSyncPath();
        $settings['file_public_path'] = $this->getPublicFilePath();
        $settings['file_private_path'] = $this->getPrivateFilePath();
        $settings['file_temp_path'] = $this->getTemporaryFilePath();
        if (isset($_SERVER['HASH_SALT'])) {
            $settings['hash_salt'] = $_SERVER['HASH_SALT'];
        }
        switch ($envName) {
            case 'dev':
                $settings['container_yamls'] = [
                    $this->getAppPath() . '/sites/development.services.yml',
                ];
                $settings['cache']['bins'] = [
                    'render' => 'cache.backend.null',
                    'page' => 'cache.backend.null',
                    'dynamic_page_cache' => 'cache.backend.null',
                ];
                $settings['hash_salt'] = 'foo';
                $settings['rebuild_access'] = FALSE;
                if (isset($_SERVER['VIRTUAL_HOST'])) {
                    $settings['trusted_host_patterns'] = [
                        $_SERVER['VIRTUAL_HOST'],
                    ];
                }
                $settings['skip_permissions_hardening'] = TRUE;
                $settings['update_free_access'] = FALSE;
                break;

            default:
                $settings['container_yamls'] = [
                    $this->getAppPath() . '/sites/' . $envName . '.services.yml',
                ];
        }
        $this->alter($settings, 'settings');
        return $settings;
    }

    /**
     * Gets the Drupal-multi-site $sites array, based on environment variables.
     *
     * @return array
     *   The Drupal-multi-site $sites array, based on environment variables.
     */
    public function getSites(): array
    {
        $domains = explode(',', $_SERVER['DOMAINS'] ?? 'default.example');
        $sites = explode(',', $_SERVER['SITES'] ?? 'default');
        foreach ($sites as $site) {
            foreach ($domains as $domain) {
                $sites[$site . '.' . $domain] = $site;
            }
        }
        return $sites;
    }

    public function getAppPath(): string
    {
        return DRUPAL_ROOT;
    }

    public function getProjectPath(): string
    {
        return dirname(DRUPAL_ROOT, 1);
    }

    public function getPublicFilePath(): string
    {
        return $_SERVER['FILE_PUBLIC_PATH'] ?? 'sites/' . $this->getSiteName() . '/files';
    }

    public function getPrivateFilePath(): string
    {
        return $_SERVER['FILE_PRIVATE_PATH'] ?? $this->getProjectPath() . '/drupal/private_files';
    }

    public function getTemporaryFilePath(): string
    {
        return $_SERVER['FILE_TEMP_PATH'] ?? $this->getProjectPath() . '/drupal/temporary_files';
    }

    public function getConfigSyncPath(): string
    {
        return $_SERVER['CONFIG_SYNC_PATH'] ?? $this->getProjectPath() . '/drupal/config/sync';
    }

}
