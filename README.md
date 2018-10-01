# UTexas Migrate
This module serves as a base for migrating UTDK 7 to 8.

# Setup
## Configuring local-settings.php for migration
The migration relies on available credentials in your `settings.php` or 
`settings.local.php`. You need to have the `utexas_migrate` key with the 
source site specific information, e.g.:

```
$databases['utexas_migrate']['default'] = array(
  'driver' => 'mysql',
  'database' => 'myUTDK7Site.local',
  'username' => 'DB_USERNAME',
  'password' => 'DB_PASSWORD',
  'host' => 'localhost',
  'port' => '3306',
);
```

For file migration purposes, you'll need to also define a setting for the
- `migration_source_base_url`
- `migration_source_public_file_path`
- `migration_source_private_file_path`

Example:
```
// The destination (D8) file private path must be an absolute path.
$settings['file_private_path'] = '/Users/nnn/Sites/utdk8/web/sites/default/files/private';

$settings['migration_source_base_url'] = 'http://quicksites.local';
$settings['migration_source_public_file_path'] = 'sites/default/files';
// Private files cannot be retrieved over HTTP.
$settings['migration_source_base_path'] = '/Users/nnn/Sites/quicksites';
$settings['migration_source_private_file_path'] = 'sites/default/files/private';
```

# Usage
## Running migrations via the command line & drush
* Use `drush ms` to list all available migrations. You'll get 
information on available migrations sorted by their group:
```
 Group: Import from UTDK Drupal 7 (utexas)  Status  Total  Imported  Unprocessed
 utexas_node                                Idle    15     0         15        
```

* To execute all migrations in a migration group, use the machine 
name of the group (listed in parentheses after the group label) to run 
`drush mim --group=GROUP_NAME`, e.g.:
```
drush mim --group=utexas
```

* You can execute a specific migration in a group by using the machine name
to run `drush mim MIGRATE_NAME`, e.g.:
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