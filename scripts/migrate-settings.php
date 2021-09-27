// Migration connection.
$databases['utexas_migrate']['default'] = [
  'database' => 'utexas_migrate',
  'username' => 'user',
  'password' => 'user',
  'host' => 'db',
  'driver' => 'mysql',
  'collation' => 'utf8mb4_general_ci',
];
$settings['migration_source_public_file_path'] = 'sites/default/files';
// Private files cannot be retrieved over HTTP.
$settings['migration_source_base_path'] = '/var/www';
$settings['migration_source_private_file_path'] = 'web/sites/default/files/private';
