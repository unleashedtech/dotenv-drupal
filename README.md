# Environment-Based Drupal Settings
This package extends Symfony's [symfony/dotenv](https://symfony.com/components/Dotenv)
component to allow streamlined Drupal config via `.env` files. Please refer to the
component's documentation about how `.env` files are used.

## Installation

`composer require unleashedtech/dotenv-drupal`

### Configuring Drupal
First, you'll need to configure Drupal to use this package.

#### Drupal Settings & Sites Files
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
$dotenv->setSiteName(basename(__DIR__));
require __DIR__ . '/../default/settings.php';
```

If you'd like, you can manually set the database name for each site via the
`setDatabaseName` method:

```php
<?php
use UnleashedTech\Drupal\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->setSiteName(basename(__DIR__));
$dotenv->setDatabaseName('foo');
require __DIR__ . '/../default/settings.php';
```

If conditional logic is required for a given site, such logic is still supported.
This package will auto-load various `settings.{{environment}}.php` or
`config.{{environment}}.php` files, if they exist.

###### Sites Files
This package also provides functionality to configure Drupal's `$sites` variable
via `sites.php`. The `$sites` variable is built from data in the [DOMAINS](#domains)
& [SITES](#sites) environment variables. You will need to add the following code
to `sites.php`:

```php
<?php
use UnleashedTech\Drupal\Dotenv\Dotenv;
$dotenv = new Dotenv();
$sites = $dotenv->getSites();
```

#### Configuring Drupal via ENV Variables
This package will provide many default setting & configuration values based on the
detected environment. Some of these values can be populated by environment variables.

Environment variables can be set in `.env` files, or via modifying server configuration.
For production environments, environment variables should ideally be defined via server
configuration.

* [DATABASE_URL](#database_url)
* [DOMAINS](#domains)
* [SITES](#sites)
* [SOLR_URL](#solr_url)
* More configuration options coming soon!

##### DATABASE_URL
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

##### DOMAINS
A CSV list of domains used by the given environment:

```dotenv
DOMAINS=foo.example,bar.example,baz.example
```

##### SITES
A CSV list of Drupal "sites" (e.g. "subdomains") used by the given environment:

```dotenv
SITES=foo,bar,baz,qux
```

##### SOLR_URL
The default Solr connection can be configured via a [DSN](https://en.wikipedia.org/wiki/Data_source_name):

```dotenv
SOLR_URL=http://localhost
```

This package makes several assumptions, which can be overridden via the `SOLR_URL` DSN. The DSN in the
example above is automatically expanded to:

```dotenv
SOLR_URL=http://default@localhost:8983#default
```

In the expanded example above, the `user` is the name of the Solr core & the `fragment` is the Drupal machine
name for the connection. Consider revising Solr core & Drupal Solr server machine names to `default`,
so the shorter DSN can be used.

Streamlined environment-dependent configuration of _one_ Solr core is supported at this time.

##### Supported Placeholders
* `{{app_path}}`: The path where Drupal is located.
* `{{project_path}}`: The path where the project is located.
* `{{site_name}}`: The name of the Drupal site. Defaults to `default`.
* `{{virtual_host}}`: The value of `$_SERVER['VIRTUAL_HOST']`.
