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
     * @var string The machine name of the Drupal app being configured.
     *
     * e.g. "earth"
     */
    private string $appName = 'default';

    /**
     * @var string The machine name of the Drupal app site being configured.
     *
     * e.g. "antarctica", which is a site of the "earth" Drupal multi-site app.
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
        if (! $this->getEnvironmentName()) {
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
     * Gets the name of the App.
     *
     * @return string
     *   The name of the App.
     */
    public function getAppName(): string
    {
        return $this->appName;
    }

    /**
     * Sets the name of the App.
     */
    public function setAppName(string $appName): string
    {
        return $this->appName = $appName;
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
    public function setSiteName(string $siteName): string
    {
        return $this->siteName = $siteName;
    }

    /**
     * Gets the environment name.
     *
     * @return string:null
     *   The environment name.
     */
    public function getEnvironmentName(): ?string
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
        $files[] = DRUPAL_ROOT . '/sites/default/' . $type . '.' . $this->getEnvironmentName() . '.php';
        $files[] = DRUPAL_ROOT . '/sites/default/' . $type . '.local.php';

        // Allow alteration via non-`default` directories.
        $siteName = $this->getSiteName();
        if ($siteName !== 'default') {
            $files[] = DRUPAL_ROOT . '/sites/' . $siteName . '/' . $type . '.' . $this->getEnvironmentName() . '.php';
            $files[] = DRUPAL_ROOT . '/sites/' . $siteName . '/' . $type . '.local.php';
        }

        foreach ($files as $file) {
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

        // Default to having Shield enabled.
        $isPublic = $this->get('public');
        if ($isPublic) {
            $config['shield.settings']['shield_enable'] = (bool) $isPublic;
        } else {
            $config['shield.settings']['shield_enable'] = TRUE;
        }

        // Apply configuration based on environment name.
        switch ($this->getEnvironmentName()) {
            case 'dev':
                $config['shield.settings']['shield_enable'] = FALSE;
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
            case 'production':
                $config['environment_indicator.indicator'] = [
                    'name' => 'Production',
                    'fg_color' => '#ffb6b6',
                    'bg_color' => '#870000',
                ];
                break;
        }

        // Configure Mailgun.
        $mailgunUrl = $this->get('mailgun_url');
        if ($mailgunUrl) {
            $parts = parse_url($mailgunUrl);
            $config['mailgun.settings']['api_endpoint'] = vsprintf('%s://%s', [
                'scheme' => $parts['scheme'] ?? 'https',
                'host' => $parts['host'] ?? 'api.mailgun.net',
            ]);
            $config['mailgun.settings']['api_key'] = $parts['user'] ?? '';
        }

        // Configure Shield if enabled.
        if ($config['shield.settings']['shield_enable']) {
            $config['shield.settings']['credentials']['shield']['user'] = $this->getAppName();
            $config['shield.settings']['credentials']['shield']['pass'] = $this->getSiteName();
        }

        // Configure Solr.
        $solrUrl = $this->get('solr_url');
        if ($solrUrl) {
            $parts = parse_url($solrUrl);
            $name = $parts['fragment'] ?? 'default';
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

    public function getDatabases(): array
    {
        $db_url = parse_url($this->get('database_url'));
        $username = $db_url['user'] ?? $this->get('database_user');
        $password = $db_url['pass'] ?? $this->get('database_password');
        $databases = [
            'default' =>
                [
                    'default' =>
                        [
                            'database' => $this->getDatabaseName(),
                            'host' => $db_url['host'],
                            'username' => $username,
                            'password' => $password,
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
        $result = parse_url($this->get('database_url'), PHP_URL_PATH);
        if (NULL === $result || trim($result) === '/') {
            // Multi-site configuration detected. Use the site name.
            $result = $this->getSiteName();
            if ($result === 'default' && !$this->isMultiSiteDefaultSiteAllowed()) {
                if (PHP_SAPI === 'cli') {
                    throw new \Exception('The "default" site in this multi-site install is not allowed. Please run something like `drush -l {{site}}` instead.');
                } else {
                    header("HTTP/1.1 401 Unauthorized");
                    die('Unauthorized');
                }
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

    private function getAppSiteNamespace(): string {
        return strtoupper($this->getAppName() . '__' . $this->getSiteName() . '__');
    }

    public function isMultiSite(): bool
    {
        return count($this->getSites()) > 1;
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

        // Configure hash salt.
        $hashSalt = $this->get('hash_salt');
        if ($hashSalt) {
            $settings['hash_salt'] = $hashSalt;
        }

        // Configure trusted host patterns.
        // @see https://github.com/unleashedtech/dotenv-drupal/blob/main/README.md#trusted_host_patterns
        $trustedHostPatterns = $this->get('trusted_host_patterns');
        $settings['trusted_host_patterns'] = [];
        if ($trustedHostPatterns) {
            foreach (explode(',', $trustedHostPatterns) as $pattern) {
                $settings['trusted_host_patterns'][] = '^' . $pattern . '$';
            }
        }
        else {
            foreach ($this->getDomains() as $domain) {
                if (! $this->isMultiSite() || $this->isMultiSiteDefaultSiteAllowed()) {
                    $settings['trusted_host_patterns'][] = '^' . \str_replace('.', '\.', $domain) . '$';
                    $settings['trusted_host_patterns'][] = '^www\.' . \str_replace('.', '\.', $domain) . '$';
                }

                foreach ($this->getSites() as $site) {
                    if ($site === 'default') {
                        continue;
                    }

                    $settings['trusted_host_patterns'][] = \vsprintf('^%s\.%s$', [
                        $site,
                        \str_replace('.', '\.', $domain),
                    ]);
                }
            }
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
                $virtualHost = $this->get('virtual_host');
                if ($virtualHost) {
                    $settings['trusted_host_patterns'][] = $virtualHost;
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

    public function get($key): ?string {
        $key = strtoupper($key);
        $namespacedKey = strtoupper($this->getAppName() . '__' . $this->getSiteName()) . '__' . $key;
        if (isset($_SERVER[$namespacedKey])) {
            return $_SERVER[$namespacedKey];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        return NULL;
    }

    /**
     * Gets the domains for this environment.
     *
     * @return array
     *   The domains for this environment.
     */
    public function getDomains(): array {
        return \explode(',', $this->get('domains') ?? 'default.example');
    }

    /**
     * Gets the Drupal-multi-site $sites array, based on environment variables.
     *
     * @return array
     *   The Drupal-multi-site $sites array, based on environment variables.
     */
    public function getSites(): array
    {
        $domains   = $this->getDomains();
        $siteNames = \explode(',', $this->get('sites') ?? 'default');
        $sites     = [];
        foreach ($siteNames as $siteName) {
            foreach ($domains as $domain) {
                $sites[$siteName . '.' . $domain] = $siteName;
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
        return $this->get('file_public_path') ?? 'sites/' . $this->getSiteName() . '/files';
    }

    public function getPrivateFilePath(): string
    {
        return $this->get('file_private_path') ?? $this->getProjectPath() . '/drupal/private_files';
    }

    public function getTemporaryFilePath(): string
    {
        return $this->get('file_temp_path') ?? $this->getProjectPath() . '/drupal/temporary_files';
    }

    public function getConfigSyncPath(): string
    {
        return $this->get('config_sync_path') ?? $this->getProjectPath() . '/drupal/config/sync';
    }

}
