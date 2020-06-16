<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once(__DIR__ . '/../lib.php');
require_once($CFG->dirroot.'/course/lib.php');

global $DB;

$user_input = cli_input(
    "This will DELETE ALL existing observation activity instances. Are you sure? (y/n)",
    'n',
    ['y', 'n'],
    true);
if ($user_input == 'n')
{
    die('Cancelled by user');
}

echo 'Deleting all existing instances...';
foreach ($DB->get_records(OBSERVATION) as $record)
{
    $cm = get_coursemodule_from_instance(OBSERVATION, $record->id);
    course_delete_module($cm->id);
}

$file = '../db/install.xml';
$manager = $DB->get_manager();

echo 'Dropping...';
$manager->delete_tables_from_xmldb_file($file);

echo 'Creating...';
$manager->install_from_xmldb_file($file);

echo 'Done!';
exit(0);