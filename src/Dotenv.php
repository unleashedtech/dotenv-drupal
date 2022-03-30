<?php

declare(strict_types=1);

namespace UnleashedTech\Drupal\Dotenv;

use Symfony\Component\Dotenv\Dotenv as SymfonyDotenv;

/**
 * A class to help configure Drupal based on ENV file variables.
 */
class Dotenv
{
    /** @var string Optional. The name of the database to use. */
    private string $databaseName;

    /** @var string The name of the Drupal site being configured. */
    private string $siteName = 'default';

    /**
     * The class constructor.
     */
    public function __construct()
    {
        // Load data from ENV file(s) if APP_ENV is not defined.
        if (isset($_SERVER['APP_ENV'])) {
            return;
        }

        $root   = $this->getProjectPath();
        $dotenv = new SymfonyDotenv();
        if (\file_exists($root . '/.env') || \file_exists($root . '/.env.dist')) {
            $dotenv->loadEnv(DRUPAL_ROOT . '/../.env');
        } elseif (\file_exists($root . '/.env.dev')) {
            $_SERVER['APP_ENV'] = 'dev';
            $dotenv->load(DRUPAL_ROOT . '/../.env.dev');
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
     *
     * @param string $siteName
     *   The name the site.
     */
    public function setSiteName(string $siteName): string
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
        return \strtolower($_SERVER['APP_ENV']);
    }

    /**
     * Alters the given data with data of the same type defined in PHP files.
     *
     * @param \array[][] $data
     *   The data to alter.
     * @param string     $type
     *   The type of data being altered (e.g. settings, config, databases).
     */
    private function alter(array &$data, string $type): void
    {
        $$type = &$data;

        // Allow alteration via the `default` directory.
        $appPath = $this->getAppPath();
        $files[] = $appPath . '/sites/default/' . $type . '.' . $this->getEnvironmentName() . '.php';
        $files[] = $appPath . '/sites/default/' . $type . '.local.php';

        // Allow alteration via non-`default` directories.
        $siteName = $this->getSiteName();
        if ($siteName !== 'default') {
            $files[] = $appPath . '/sites/' . $siteName . '/' . $type . '.' . $this->getEnvironmentName() . '.php';
            $files[] = $appPath . '/sites/' . $siteName . '/' . $type . '.local.php';
        }

        foreach ($files as $file) {
            if (\file_exists($file)) {
                include $file;
            }
        }
    }

    /**
     * Gets Drupal configuration overrides.
     *
     * @return \array[][]
     *   Drupal configuration overrides.
     */
    public function getConfig(): array
    {
        $config = [];

        // Default to having shield enabled.
        if (isset($_SERVER['SHIELD'])) {
            $config['shield.settings']['shield_enable'] = (bool) $_SERVER['SHIELD'];
        } else {
            $config['shield.settings']['shield_enable'] = true;
        }

        // Apply configuration based on environment name.
        switch ($this->getEnvironmentName()) {
            case 'dev':
                $config['shield.settings']['shield_enable']          = false;
                $config['config_split.config_split.local']['status'] = true;
                $config['environment_indicator.indicator']           = [
                    'name' => 'Development',
                    'fg_color' => '#110011',
                    'bg_color' => '#33aa33',
                ];
                $config['system.logging']['error_level']             = 'verbose';
                $config['system.performance']                        = [
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
            case 'production':
                $config['environment_indicator.indicator'] = [
                    'name' => 'Production',
                    'fg_color' => '#ffb6b6',
                    'bg_color' => '#870000',
                ];
                break;
        }

        // Configure Mailgun.
        if (isset($_SERVER['MAILGUN_URL'])) {
            $parts                                      = \parse_url($_SERVER['MAILGUN_URL']);
            $config['mailgun.settings']['api_endpoint'] = \vsprintf('%s://%s', [
                'scheme' => $parts['scheme'] ?? 'https',
                'host' => $parts['host'] ?? 'api.mailgun.net',
            ]);
            $config['mailgun.settings']['api_key']      = $parts['user'] ?? 'key-1234567890abcdefghijklmnopqrstu';
        }

        // Configure Shield if enabled.
        if ($config['shield.settings']['shield_enable']) {
            if (isset($_SERVER['SHIELD_USERNAME'])) {
                $config['shield.settings']['credentials']['shield']['user'] = $_SERVER['SHIELD_USERNAME'];
            } elseif (isset($_SERVER['SHIELD'])) {
                $config['shield.settings']['credentials']['shield']['user'] = $_SERVER['SHIELD'];
            }

            if (isset($_SERVER['SHIELD_PASSWORD'])) {
                $config['shield.settings']['credentials']['shield']['pass'] = $_SERVER['SHIELD_PASSWORD'];
            } elseif (isset($_SERVER['SHIELD'])) {
                $config['shield.settings']['credentials']['shield']['pass'] = $_SERVER['SHIELD'];
            }

            if (isset($_SERVER['SHIELD_MESSAGE'])) {
                $config['shield.settings']['print'] = $_SERVER['SHIELD_MESSAGE'];
            }
        }

        // Configure Solr.
        if (isset($_SERVER['SOLR_URL'])) {
            $parts                                                                      = \parse_url($_SERVER['SOLR_URL']);
            $name                                                                       = $parts['fragment'] ?? 'default';
            $config['search_api.server.' . $name]['backend_config']['connector_config'] = [
                'scheme' => $parts['scheme'] ?? 'http',
                'host' => $parts['host'] ?? 'localhost',
                'port' => $parts['port'] ?? 8983,
                'path' => $parts['path'] ?? '/',
                'core' => $parts['user'] ?? 'default',
            ];
        }

        $this->alter($config, 'config');

        return $config;
    }

    /**
     * @return \array[][]
     */
    public function getDatabases(): array
    {
        $dbUrl     = \parse_url($_SERVER['DATABASE_URL']);
        $databases = [
            'default' =>
                [
                    'default' =>
                        [
                            'database' => $this->getDatabaseName(),
                            'host' => $dbUrl['host'],
                            'username' => $dbUrl['user'],
                            'password' => $dbUrl['pass'],
                            'prefix' => '',
                            'port' => $dbUrl['port'],
                            'namespace' => 'Drupal\\Core\\Database\\Driver\\' . $dbUrl['scheme'],
                            'driver' => $dbUrl['scheme'],
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

        $result = \parse_url($_SERVER['DATABASE_URL'], PHP_URL_PATH);
        if ($result === false) {
            throw new \UnexpectedValueException(\sprintf('DSN "%s" could not be parsed.', $_SERVER['DATABASE_URL']));
        }

        if ($result === null || \trim($result) === '/') {
            // Multi-site configuration detected. Use the site name.
            $result = $this->getSiteName();
            if ($result === 'default' && ! $this->isMultiSiteDefaultSiteAllowed()) {
                if (PHP_SAPI === 'cli') {
                    throw new \DomainException('The "default" site in this multi-site install is not allowed. Please run something like `drush -l {{site}}` instead.');
                }

                \header('HTTP/1.1 401 Unauthorized');
                die('Unauthorized');
            }
        } else {
            $result = \substr($result, 1);
        }

        if ($result === null || \preg_replace('/[^a-z0-9_]/', '', $result) === '') {
            throw new \UnexpectedValueException('Database name could not be computed from ' . $_SERVER['DATABASE_URL']);
        }

        return $result;
    }

    public function setDatabaseName(string $database): void
    {
        $this->databaseName = $database;
    }

    public function isMultiSite(): bool
    {
        return \count($this->getSites()) > 1;
    }

    public function isMultiSiteDefaultSiteAllowed(): bool
    {
        return (bool) ($_SERVER['MULTISITE_DEFAULT_SITE_ALLOWED'] ?? false);
    }

    /**
     * @return string[][]
     */
    public function getSettings(): array
    {
        $envName                                       = $this->getEnvironmentName();
        $settings['update_free_access']                = false;
        $settings['file_scan_ignore_directories']      = [
            'node_modules',
            'bower_components',
        ];
        $settings['entity_update_batch_size']          = 50;
        $settings['entity_update_backup']              = true;
        $settings['migrate_node_migrate_type_classic'] = false;
        $settings['config_sync_directory']             = $this->getConfigSyncPath();
        $settings['file_public_path']                  = $this->getPublicFilePath();
        $settings['file_private_path']                 = $this->getPrivateFilePath();
        $settings['file_temp_path']                    = $this->getTemporaryFilePath();
        if (isset($_SERVER['HASH_SALT'])) {
            $settings['hash_salt'] = $_SERVER['HASH_SALT'];
        }

        $settings['trusted_host_patterns'] = $this->getTrustedHostPatterns();

        switch ($envName) {
            case 'dev':
                $settings['container_yamls'] = [
                    $this->getAppPath() . '/sites/development.services.yml',
                ];
                $settings['cache']['bins']   = [
                    'render' => 'cache.backend.null',
                    'page' => 'cache.backend.null',
                    'dynamic_page_cache' => 'cache.backend.null',
                ];
                $settings['hash_salt']       = 'foo';
                $settings['rebuild_access']  = false;
                if (isset($_SERVER['VIRTUAL_HOST'])) {
                    $settings['trusted_host_patterns'][] = $_SERVER['VIRTUAL_HOST'];
                }

                $settings['skip_permissions_hardening'] = true;
                $settings['update_free_access']         = false;
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
     * @see https://github.com/unleashedtech/dotenv-drupal/blob/main/README.md#trusted_host_patterns
     *
     * @return string[]
     */
    public function getTrustedHostPatterns(): array
    {
        $trustedHostPatterns = [];
        if (isset($_SERVER['TRUSTED_HOST_PATTERNS'])) {
            foreach (\explode(',', $_SERVER['TRUSTED_HOST_PATTERNS']) as $pattern) {
                $trustedHostPatterns[] = '^' . $pattern . '$';
            }
        } else {
            foreach ($this->getDomains() as $domain) {
                if (! $this->isMultiSite() || $this->isMultiSiteDefaultSiteAllowed()) {
                    $trustedHostPatterns[] = '^' . \str_replace('.', '\.', $domain) . '$';
                    $trustedHostPatterns[] = '^www\.' . \str_replace('.', '\.', $domain) . '$';
                }

                foreach ($this->getSites() as $site) {
                    if ($site === 'default') {
                        continue;
                    }

                    $trustedHostPatterns[] = \vsprintf('^%s\.%s$', [
                        $site,
                        \str_replace('.', '\.', $domain),
                    ]);
                }
            }
        }

        return $trustedHostPatterns;
    }

    /**
     * Gets the domains for this environment.
     *
     * @return string[]
     *   The domains for this environment.
     */
    public function getDomains(): array
    {
        return \explode(',', $_SERVER['DOMAINS'] ?? 'default.example');
    }

    /**
     * Gets the Drupal-multi-site $sites array, based on environment variables.
     *
     * @return string[]
     *   The Drupal-multi-site $sites array, based on environment variables.
     */
    public function getSites(): array
    {
        $domains   = $this->getDomains();
        $siteNames = \explode(',', $_SERVER['SITES'] ?? 'default');
        $sites     = [];
        foreach ($siteNames as $siteName) {
            foreach ($domains as $domain) {
                $site         = $siteName === 'default' ? $domain : $siteName . '.' . $domain;
                $sites[$site] = $siteName;
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
        return \dirname($this->getAppPath());
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
