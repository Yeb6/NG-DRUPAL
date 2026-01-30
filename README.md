# eWSDrupal.

1. DB configuration for all settings.php file

$databases['default']['default'] = [
  'database' => 'ews', ##### Please mention database of the site or for the subsite #####
  'username' => getenv('DRUPAL_DB_USERNAME'),
  'password' => getenv('DRUPAL_DB_PASSWORD'),
  'prefix' => '',
  'host' => getenv('DRUPAL_DB_HOST'),
  'port' => '3306',
  'driver' => 'mysql',
];

2. Create subsite
	2.1 Create directory of subsite in sites directory make sure it should have "settings.php" and "Files" directory
	2.2 Add subsite name in enterypoint.sh file line no 8. "sites=("wmr-usavan" "wmr-usapc")"