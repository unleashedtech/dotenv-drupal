<?php

declare(strict_types=1);

namespace PHPUnit;

use PHPUnit\Framework\TestCase;
use UnleashedTech\Drupal\Dotenv\Dotenv;

final class DotenvTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        \define('DRUPAL_ROOT', './tests');
    }

    public function setUp(): void
    {
        $_SERVER['APP_ENV']      = 'foo';
        $_SERVER['DATABASE_URL'] = 'mysql://user:password@host:0/database';
    }

    public function testAlterDefaultSiteConfig(): void
    {
        $dotenv             = new Dotenv();
        $_SERVER['APP_ENV'] = 'dev';
        $this->assertSame([
            'shield.settings' =>
                [
                    'shield_enable' => false,
                    'allow_cli' => true,
                ],
            'config_split.config_split.local' =>
                [
                    'status' => true,
                ],
            'environment_indicator.indicator' =>
                [
                    'name' => 'Development',
                    'fg_color' => '#110011',
                    'bg_color' => '#33aa33',
                ],
            'system.logging' =>
                [
                    'error_level' => 'verbose',
                ],
            'system.performance' =>
                [
                    'css' =>
                        [
                            'preprocess' => false,
                        ],
                    'js' =>
                        [
                            'preprocess' => false,
                        ],
                ],
        ], $dotenv->getConfig());
    }

    public function testAlterDefaultSiteDatabases(): void
    {
        $dotenv             = new Dotenv();
        $_SERVER['APP_ENV'] = 'dev';
        $this->assertSame([
            'default' =>
                [
                    'default' =>
                        [
                            'database' => 'db',
                            'host' => 'host',
                            'username' => 'db',
                            'password' => 'db',
                            'prefix' => 'local_',
                            'port' => 0,
                            'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
                            'driver' => 'mysql',
                        ],
                ],
        ], $dotenv->getDatabases());
    }

    public function testAlterDefaultSiteSettings(): void
    {
        $dotenv             = new Dotenv();
        $_SERVER['APP_ENV'] = 'dev';
        $this->assertSame([
            'update_free_access' => false,
            'file_scan_ignore_directories' =>
                [
                    0 => 'node_modules',
                    1 => 'bower_components',
                ],
            'entity_update_batch_size' => 50,
            'entity_update_backup' => true,
            'migrate_node_migrate_type_classic' => false,
            'config_sync_directory' => './drupal/config/sync',
            'file_public_path' => 'sites/default/files',
            'file_private_path' => './drupal/private_files',
            'file_temp_path' => './drupal/temporary_files',
            'container_yamls' =>
                [
                    0 => './tests/sites/development.services.yml',
                ],
            'cache' =>
                [
                    'bins' =>
                        [
                            'render' => 'cache.backend.null',
                            'page' => 'cache.backend.null',
                            'dynamic_page_cache' => 'cache.backend.null',
                        ],
                ],
            'hash_salt' => 'baz',
            'rebuild_access' => true,
            'skip_permissions_hardening' => true,
        ], $dotenv->getSettings());
    }

    public function testAlterAltSiteConfig(): void
    {
        $dotenv = new Dotenv();
        $dotenv->setSiteName('alt');
        $_SERVER['APP_ENV'] = 'dev';
        $this->assertSame([
            'shield.settings' =>
                [
                    'shield_enable' => false,
                    'allow_cli' => true,
                    'debug_header' => true,
                ],
            'config_split.config_split.local' =>
                [
                    'status' => true,
                ],
            'environment_indicator.indicator' =>
                [
                    'name' => 'Development',
                    'fg_color' => '#110011',
                    'bg_color' => '#33aa33',
                ],
            'system.logging' =>
                [
                    'error_level' => 'verbose',
                ],
            'system.performance' =>
                [
                    'css' =>
                        [
                            'preprocess' => false,
                        ],
                    'js' =>
                        [
                            'preprocess' => false,
                        ],
                ],
        ], $dotenv->getConfig());
    }

    public function testAlterAltSiteDatabases(): void
    {
        $dotenv = new Dotenv();
        $dotenv->setSiteName('alt');
        $_SERVER['APP_ENV'] = 'dev';
        $this->assertSame([
            'default' =>
                [
                    'default' =>
                        [
                            'database' => 'alt',
                            'host' => 'host',
                            'username' => 'db',
                            'password' => 'db',
                            'prefix' => 'local_',
                            'port' => 0,
                            'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
                            'driver' => 'mysql',
                        ],
                ],
        ], $dotenv->getDatabases());
    }

    public function testAlterAltSiteSettings(): void
    {
        $dotenv = new Dotenv();
        $dotenv->setSiteName('alt');
        $_SERVER['APP_ENV'] = 'dev';
        $this->assertSame([
            'update_free_access' => true,
            'file_scan_ignore_directories' =>
                [
                    0 => 'node_modules',
                    1 => 'bower_components',
                ],
            'entity_update_batch_size' => 50,
            'entity_update_backup' => true,
            'migrate_node_migrate_type_classic' => false,
            'config_sync_directory' => './drupal/config/sync',
            'file_public_path' => 'sites/alt/files',
            'file_private_path' => './drupal/private_files',
            'file_temp_path' => './drupal/temporary_files',
            'container_yamls' =>
                [
                    0 => './tests/sites/development.services.yml',
                ],
            'cache' =>
                [
                    'bins' =>
                        [
                            'render' => 'cache.backend.null',
                            'page' => 'cache.backend.null',
                            'dynamic_page_cache' => 'cache.backend.null',
                        ],
                ],
            'hash_salt' => 'baz',
            'rebuild_access' => true,
            'skip_permissions_hardening' => true,
        ], $dotenv->getSettings());
    }

    public function testDefaultSiteName(): void
    {
        $dotenv = new Dotenv();
        $this->assertSame('default', $dotenv->getSiteName());
    }

    public function testEnvironmentNameUndefined(): void
    {
        unset($_SERVER['APP_ENV']);
        $dotenv = new Dotenv();
        $this->expectNotice();
        $dotenv->getEnvironmentName();
        $this->expectNoticeMessageMatches('/Undefined index: APP_ENV/');
    }

    public function testGetAppPath(): void
    {
        $dotenv = new Dotenv();
        $this->assertSame('./tests', $dotenv->getAppPath());
    }

    public function testGetDefaultConfig(): void
    {
        $dotenv = new Dotenv();
        $this->assertSame([
            'shield.settings' => [
                'shield_enable' => true,
                'allow_cli' => true,
            ],
        ], $dotenv->getConfig());
    }

    public function testGetDefaultConfigSyncPath(): void
    {
        $dotenv = new Dotenv();
        $this->assertSame('./drupal/config/sync', $dotenv->getConfigSyncPath());
    }

    public function testGetDefaultDatabases(): void
    {
        $dotenv = new Dotenv();
        $this->assertSame([
            'default' =>
                [
                    'default' =>
                        [
                            'database' => 'database',
                            'host' => 'host',
                            'username' => 'user',
                            'password' => 'password',
                            'prefix' => 'local_',
                            'port' => 0,
                            'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
                            'driver' => 'mysql',
                        ],
                ],
        ], $dotenv->getDatabases());
    }

    public function testGetDefaultPublicFilePath(): void
    {
        $dotenv = new Dotenv();
        $this->assertSame('sites/default/files', $dotenv->getPublicFilePath());
    }

    public function testGetDefaultPrivateFilePath(): void
    {
        $dotenv = new Dotenv();
        $this->assertSame('./drupal/private_files', $dotenv->getPrivateFilePath());
    }

    public function testGetDefaultProjectPath(): void
    {
        $dotenv = new Dotenv();
        $this->assertSame('.', $dotenv->getProjectPath());
    }

    public function testGetDefaultSettings(): void
    {
        $dotenv = new Dotenv();
        $this->assertSame([
            'update_free_access' => false,
            'file_scan_ignore_directories' =>
                [
                    0 => 'node_modules',
                    1 => 'bower_components',
                ],
            'entity_update_batch_size' => 50,
            'entity_update_backup' => true,
            'migrate_node_migrate_type_classic' => false,
            'config_sync_directory' => './drupal/config/sync',
            'file_public_path' => 'sites/default/files',
            'file_private_path' => './drupal/private_files',
            'file_temp_path' => './drupal/temporary_files',
            'container_yamls' =>
                [
                    0 => './tests/sites/foo.services.yml',
                ],
            'hash_salt' => 'baz',
        ], $dotenv->getSettings());
    }

    public function testGetDefaultSites(): void
    {
        $dotenv = new Dotenv();
        $this->assertSame([
            'default.default.example' => 'default',
        ], $dotenv->getSites());
    }

    public function testGetEnvironmentName(): void
    {
        $dotenv             = new Dotenv();
        $_SERVER['APP_ENV'] = 'foo';
        $this->assertSame('foo', $dotenv->getEnvironmentName());
    }

    public function testGetTemporaryFilePath(): void
    {
        $dotenv = new Dotenv();
        $this->assertSame('./drupal/temporary_files', $dotenv->getTemporaryFilePath());
    }

    public function testIsDefaultMultiSiteDefaultSiteAllowed(): void
    {
        $dotenv = new Dotenv();
        $this->assertFalse($dotenv->isMultiSiteDefaultSiteAllowed());
    }

    public function testSetDatabaseName(): void
    {
        $dotenv = new Dotenv();
        $dotenv->setDatabaseName('foo');
        $this->assertSame('foo', $dotenv->getDatabaseName());
    }

    public function testSetIsMultiSiteDefaultSiteAllowed(): void
    {
        $dotenv = new Dotenv();
        $dotenv->setMultiSiteDefaultSiteAllowed();
        $this->assertTrue($dotenv->isMultiSiteDefaultSiteAllowed());
    }

    public function testSetSiteName(): void
    {
        $dotenv = new Dotenv();
        $dotenv->setSiteName('foo');
        $this->assertSame('foo', $dotenv->getSiteName());
    }
}
