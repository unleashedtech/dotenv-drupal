# Environment-Based Drupal Settings
This package extends Symfony's [symfony/dotenv](https://symfony.com/components/Dotenv)
component to allow streamlined Drupal config via `.env` files. Please refer to the
component's documentation about how `.env` files are used.

## Installation

`composer require unleashedtech/dotenv-drupal`

### Configuring Drupal
First, you'll need to configure Drupal to use this package.

#### Drupal Settings Files
Drupal is typically configured via `settings.php` files in various directories.
To use this package with Drupal, some code will need to be added to the top of
the relevant `settings.php` file(s):

```php
<?php
use UnleashedTech\Drupal\Dotenv\Dotenv;
$dotenv = $dotenv ?? new Dotenv();
$config = $dotenv->getConfig();
$databases = $dotenv->getDatabases();
$settings = $dotenv->getSettings();
```

##### Multi-Site Drupal
Many multi-site installations leave the `default` site unused. If this is the
case, you can use the `default` settings file for base configuration in the
`settings.php` file for each site.

In your relevant environment file(s), leave the "database" (e.g. "path")
empty to allow automatic database name selection based on the site name.

```php
<?php
use UnleashedTech\Drupal\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->setSiteName(__DIR__);
require __DIR__ . '../default/settings.php';
```

If you'd like, you can manually set the database name for each site via the
`setDatabaseName` method:

```php
<?php
use UnleashedTech\Drupal\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->setSiteName(__DIR__);
$dotenv->setDatabaseName('foo');
require __DIR__ . '../default/settings.php';
```

If conditional logic is required for a given site, such logic is still supported.
This package will auto-load various `settings.{{environment}}.php` or
`config.{{environment}}.php` files, if they exist.

#### Configuring Drupal via ENV Variables
This package will provide many default setting & configuration values based on the
detected environment. Some of these values can be populated by environment variables.
If the project requires complex configuration in Drupal settings files, this package
will attempt to auto-load those files based on environment
(e.g. `settings.dev.php`, `config.dev.php`, etc.).

Environment variables can be set in `.env` files, or via modifying server configuration.
For production environments, environment variables should ideally be defined via server
configuration.

* [Database Configuration](#database-configuration)
* [Solr Configuration](#solr-configuration)
* More configuration options coming soon!

##### Database Configuration
The default database connection can be configured via a [DSN](https://en.wikipedia.org/wiki/Data_source_name):

```dotenv
DATABASE_URL=driver://user:password@host:port/database
```

For example:

```dotenv
DATABASE_URL=mysql://foo:bar@host:3306/baz
```

For multi-site installations, do _not_ specify a database name in the ENV file(s):

```dotenv
DATABASE_URL=mysql://foo:bar@host:3306
```

##### Solr Configuration
The default Solr connection can be configured via a [DSN](https://en.wikipedia.org/wiki/Data_source_name):

```dotenv
SOLR_URL=host:port
```

For example:

```dotenv
SOLR_URL=solr.foo.site:8983
```

##### Supported Placeholders
* `{{app_path}}`: The path where Drupal is located.
* `{{project_path}}`: The path where the project is located.
* `{{site_name}}`: The name of the Drupal site. Defaults to `default`.
* `{{virtual_host}}`: The value of `$_SERVER['VIRTUAL_HOST']`.
