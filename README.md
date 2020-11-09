# UTexas Migrate
This module serves as a base for migrating UT Drupal Kit v2 to v3. It consists of:

- a base module, `utexas_migrate`, that provides two migration groups, `utexas` (which migrates all of the standard content) and `utexas_end` (which migrates content that is dependent on node references)
- sub-modules for each of the add-ons, `utprof_migrate`, `utnews_migrate`, and `utevent_migrate`.

It has two dependencies, `migrate_plus`, which allows for grouping migration tasks and additional processing plugins, and `migrate_tools`, which provides CLI commands for migration execution.

## Summary of migration changes from version 2 to version 3

### Roles

Version 2 used a more verbose role delegation schema, consisting of individual roles controlling permission to edit the site announcement, create landing pages, etc. Version 3 reduces this to two roles, "Site Manager" and "Content Editor." Users that had been assigned the Version 2 role of `Site Manager` or `Announcement Editor` will be assigned the version 3 role `Site Manager`. Users that had been assigned the Version 2 role of `Site Builder`, `Landing Page Editor`, `Standard Page Editor`, `Events Editor`, `News Editor`, or `Team Member Editor` will be assigned the version 3 role `Content Editor`.

Version 2 roles | Version 3 role
-- | --
Landing PageEditor<br>Standard Page Editor<br>Site Builder<br>Team Member Editor<br>News Editor<br>Event Editor | Content Editor
Site Manager<br>Announcement Editor | Site Manager


## Quickstart Setup (Development & Testing)

A migration requires configuration that allows the migration code to discover the source database & files. The simplest way to do this locally -- and also the model for testing this migration -- is described first. Alternate methods follow.

1. Add the following to the very end of your `settings.php` file, adjusting the base_url as appropriate.

```php
// Migration connection.
$databases['utexas_migrate']['default'] = [
  'database' => 'utexas_migrate',
  'username' => 'user',
  'password' => 'user',
  'host' => 'db',
  'driver' => 'mysql',
  'collation' => 'utf8mb4_general_ci',
];
$settings['migration_source_base_url'] = 'https://dev-utqs-migration-tester.pantheonsite.io/';
$settings['migration_source_public_file_path'] = 'sites/default/files';
// Private files cannot be retrieved over HTTP.
$settings['migration_source_base_path'] = '/var/www';
$settings['migration_source_private_file_path'] = 'sites/default/files/private';
```

2. Import the remote database into the local `utexas_migrate` database:

```
export SOURCE_SITE="utqs-migration-tester"
fin db create utexas_migrate && \
terminus env:wake $SOURCE_SITE.dev && \
terminus drush $SOURCE_SITE.dev cc all && \
terminus backup:create $SOURCE_SITE.dev --element=db && \
terminus backup:get $SOURCE_SITE.dev  --element=db --to=./db.sql.gz && \
gunzip -c ./db.sql.gz > db.sql && \
fin db import db.sql --db=utexas_migrate && \
rm db.sql db.sql.gz
```

## Add & enable this module & dependencies
```
composer require utexas/utexas_migrate
composer require utexas/utprof
composer require utexas/utnews
composer require utexas/utevent
(If testing off of a branch, make sure you check out the appropriate branch before enabling the module)
fin drush en utexas_migrate -y
```

## If running one or more of the add-on migrations...

```
fin drush en utprof_migrate -y
fin drush en utnews_migrate -y
fin drush en utevent_migrate -y
```

## List the migration status by group
```
fin drush migrate-status
```

## Run the import
```
fin drush migrate-import --group=utexas
```

## Rollback the migration
```
fin drush migrate-rollback --group=utexas
```



## Alternate setups

### 1. Add source database connection

The migration relies on available credentials in your `settings.php` or
`settings.local.php`. You need to have the `utexas_migrate` key with the
source site specific information.

For a container-based migration (e.g., `lando/docksal`), you can find the host & port for the source migration via:

- `docker network ls`: note the name of the container (e.g., `managed-cms_default`)
- `docker network inspect <network>`: note the Gateway IP address:

```
        "IPAM": {
            "Driver": "default",
            "Options": null,
            "Config": [
                {
                    "Subnet": "172.25.0.0/16",
                    "Gateway": "172.25.0.1"
                }
            ]
        },
```

- From the document root of the source migration, `fin ps` or `lando info` will provide the database port number:

```
managed-cms_db_1        docker-entrypoint.sh mysqld      Up (healthy)   0.0.0.0:32769->3306/tcp
```

A complete database connection would look like this:

```bash
$databases['utexas_migrate']['default'] = [
'database' => 'default',
'username' => 'user',
'password' => 'user',
'host' => '172.25.0.1',
'port' => '32769',
'driver' => 'mysql',
'prefix' => '',
'collation' => 'utf8mb4_general_ci',
];
```

### 1. Add source filesystem information

For file migration purposes, you'll need to also define a setting for the
- `migration_source_base_url`
- `migration_source_public_file_path`
- `migration_source_private_file_path`

Example:
```
// The destination (D8) file private path must be an absolute path.
$settings['file_private_path'] = '/Users/nnn/Sites/utdk_scaffold/web/sites/default/files/private';

$settings['migration_source_base_url'] = 'http://managed-cms.docksal';
$settings['migration_source_public_file_path'] = 'sites/default/files';
// Private files cannot be retrieved over HTTP.
$settings['migration_source_base_path'] = '/Users/nnn/Sites/quicksites';
$settings['migration_source_private_file_path'] = 'sites/default/files/private';
```

# Usage
## Running migrations via the command line & drush
* To install a site without default content (menu links & default page), you can run `drush si utexas utexas_installation_options.default_content=NULL -y`.
* Use `drush ms` to list all available migrations. You'll get
information on available migrations sorted by their group:
```
 Group: Import from UTDK Drupal 7 (utexas)  Status  Total  Imported  Unprocessed
 utexas_node                                Idle    15     0         15
```

* To execute all migrations in a migration group, use the machine
name of the group (listed in parentheses after the group label) to run
`drush migrate-import --group=GROUP_NAME`, e.g.:
```
drush mim --group=utexas
```

* You can execute a specific migration in a group by using the machine name
to run `drush migrate-import MIGRATE_NAME`, e.g.:
```
drush mim utexas_node
```

# Migration Behavior

## Breadcrumb visibility
In the Drupal 7 version of UT Drupal Kit and QuickSites, Standard Pages and Landing Pages may individually specify whether breadcrumbs should display or not. Drupal 8's equivalent supports this for all node types. Thus, the breadcrumb display value, if set in Drupal 7, will be migrated to Drupal 8. In the unlikely scenario that it has not been set, the breadcrumb display will default to the content type setting, as defined in `/admin/structure/types/manage/utexas_flex_page`.

To migrate the breadcrumb value for other node types in other migrations, ensure that the source plugin retrieves the show_breadcrumb value from D7's `node` table:

Example from `NodeSource.php`:

 ```php
 'show_breadcrumb' => $this->t('Show breadcrumb'),
```

On the destination end, map this value to `display_breadcrumbs`. Example from `migrate_plus.migration.utexas_standard_page.yml`:

```yml
display_breadcrumbs: show_breadcrumb
```

## Troubleshooting

### Failed to open stream: Connection refused
Check your `$settings['migration_source_base_url']` value. If the base URL has an `https` scheme and the site does not have a valid certificate, you get this error when trying to migrate files. The fix is to use `http` as your base URL scheme in this setting.
