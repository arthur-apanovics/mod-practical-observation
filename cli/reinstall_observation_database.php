<?php

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');

global $DB;

$file = '../db/install.xml';
$manager = $DB->get_manager();

echo 'Dropping...';
$manager->delete_tables_from_xmldb_file($file);

echo 'Creating...';
$manager->install_from_xmldb_file($file);

echo 'Done!';
exit(0);