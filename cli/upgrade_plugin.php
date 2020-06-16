<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../lib/upgradelib.php');

$component = 'mod_observation';

echo 'Updating capabilities', PHP_EOL;
update_capabilities($component);

echo 'Updating web services', PHP_EOL;
external_update_descriptions($component);

echo 'Resetting scheduled tasks', PHP_EOL;
\core\task\manager::reset_scheduled_tasks_for_component($component);

echo 'Resetting messaging handlers', PHP_EOL;
\core\message\inbound\manager::update_handlers_for_component($component);

echo 'Updating MNET functions', PHP_EOL;
upgrade_plugin_mnet_functions($component);

echo 'Updating DB Tags', PHP_EOL;
core_tag_area::reset_definitions_for_component($component);

echo 'Done!';
exit(0);