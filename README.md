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