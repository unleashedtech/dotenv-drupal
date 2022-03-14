# Environment-Based Drupal Settings
This package extends Symfony's [symfony/dotenv](https://symfony.com/components/Dotenv)
component to allow streamlined Drupal config via [Environment Variables](https://en.wikipedia.org/wiki/Environment_variable).
Please refer to the Symfony component's documentation about how `.env` files
should be used. It is important to note that `.env` files are _ignored_ if the
`APP_ENV` var has already been set by the server. For performance purposes,
production environments should ideally rely on pre-configured environment variables,
rather than environment variable values loaded from `.env` files.

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

##### Conditional Logic
If conditional logic is required for a given site, such logic is still supported.
This package will auto-load various `settings.{{environment}}.php`,
`config.{{environment}}.php` or `databases.{{environment}}.php` files, if they exist.
For instance, if you need to set a database prefix for staging, you can create
`databases.staging.php`:

```php
<?php
$databases['default']['default']['prefix'] = 'foo_';
```

Each included file only has the related variable in scope
(e.g. `config.dev.php` only has `$config` in scope).

##### Multi-Site Drupal
You can use the `default` settings file to provide base configuration for
a multi-site install:

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

###### Using the Multi-Site Default Site
If you need to use the `default` site as part of your multi-site install,
you can allow it by calling the `DotEnv::setMultiSiteDefaultSiteAllowed` method
in `default/settings.php`:

```php
<?php
use UnleashedTech\Drupal\Dotenv\Dotenv;
$dotenv = $dotenv ?? new Dotenv();
$dotenv->setMultiSiteDefaultSiteAllowed();
$config = $dotenv->getConfig();
$databases = $dotenv->getDatabases();
$settings = $dotenv->getSettings();
```

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

#### Installation Conclusion
That's it! Drupal will now attempt to load essential connection information from
Environment Variables.

### Configuring Drupal via ENV Variables
This package will provide many default setting & configuration values based on the
detected environment. Some of these values can be populated by environment variables.

Environment variables can be set in `.env` files, or via modifying server configuration.
For production environments, environment variables should ideally be defined via server
configuration.

* [DATABASE_URL](#database_url)
* [FILE_PUBLIC_PATH](#file_public_path)
* [FILE_PRIVATE_PATH](#file_private_path)
* [FILE_TEMP_PATH](#file_temp_path)
* [CONFIG_SYNC_PATH](#config_sync_path)
* [DOMAINS](#domains)
* [MAILGUN_URL](#mailgun_url)
* [SHIELD](#shield)
* [SHIELD_USERNAME](#shield_username)
* [SHIELD_PASSWORD](#shield_password)
* [SHIELD_MESSAGE](#shield_message)
* [SITES](#sites)
* [SOLR_URL](#solr_url)
* [TRUSTED_HOST_PATTERNS](#trusted_host_patterns)
* More configuration options coming soon!

#### DATABASE_URL
The default database connection can be configured via a [DSN](https://en.wikipedia.org/wiki/Data_source_name):

```dotenv
DATABASE_URL=driver://user:password@host:port/database
```

For example:

```dotenv
DATABASE_URL=mysql://foo:bar@host:3306/baz
```

For multi-site installations, do _not_ specify a database name in the `DATABASE_URL` variable:

```dotenv
DATABASE_URL=mysql://foo:bar@host:3306
```

#### FILE_PUBLIC_PATH
Allows you to override the default `$settings['file_public_path']` value:

```dotenv
FILE_PUBLIC_PATH=sites/all/files
```

Drupal expects this path to be _relative_ to `DRUPAL_ROOT`.

#### FILE_PRIVATE_PATH
Allows you to override the default `$settings['file_private_path']` value:

```dotenv
FILE_PRIVATE_PATH=/private
```

#### FILE_TEMP_PATH
Allows you to override the default `$settings['file_temp_path']` value:

```dotenv
FILE_TEMP_PATH=/tmp
```

#### CONFIG_SYNC_PATH
Allows you to override the default `$settings['config_sync_path']` value:

```dotenv
CONFIG_SYNC_PATH=/sync
```

#### DOMAINS
A CSV list of domains used by the given environment:

```dotenv
DOMAINS=foo.example,bar.example,baz.example
```

#### MAILGUN_URL
The information Drupal should use to authenticate with the Mailgun API.

The "user" in the [DSN](https://en.wikipedia.org/wiki/Data_source_name) is the API key.

##### US URL Example
```dotenv
MAILGUN_URL=https://key-1234567890abcdefghijklmnopqrstu@api.mailgun.net
```

##### EU URL Example
```dotenv
MAILGUN_URL=https://key-1234567890abcdefghijklmnopqrstu@api.eu.mailgun.net
```

#### SHIELD
A string allowing the enabling/disabling of Shield module auth functionality.

If empty, shield will not be enabled.

If filled, the string will be used as username & password by default.

Note: _Make sure the "Enable Shield" checkbox is checked in Drupal & that config is committed._

##### SHIELD_USERNAME
The username for Shield to require, if enabled.

This package will use the value of `SHIELD` as username, by default.

##### SHIELD_PASSWORD
The password for Shield to require, if enabled.

This package will use the value of `SHIELD` as password, by default.

##### SHIELD_MESSAGE
The _public_ message Shield should show in the auth prompt if enabled.

#### SITES
A CSV list of Drupal "sites" (e.g. "subdomains") used by the given environment:

```dotenv
SITES=foo,bar,baz,qux
```

#### SOLR_URL
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

#### TRUSTED_HOST_PATTERNS
Optional. A CSV list of patterns specifying [trusted hosts](https://www.drupal.org/docs/installing-drupal/trusted-host-settings#s-protecting-in-drupal-8). If this
variable is not set, this package will populate Drupal's `trusted_host_patterns` array
based on the value of [DOMAINS](#domains).

Start (`^`) & end (`$`) characters are added to every pattern, by default.

#### Configuration Conclusion
With these few Environment Variables, you will be able to configure Drupal in a streamlined
fashion similar to the way Symfony is configured. Support for many more common Drupal features
can be expected soon. Please consider creating a Pull Request with features you would like to
this package to support.
