<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');      // cli only functions
require_once($CFG->libdir . '/outputlib.php');

global $CFG, $PAGE;

$next = time();
if (isset($CFG->themerev) and $next <= $CFG->themerev and $CFG->themerev - $next < 60 * 60)
{
    // This resolves problems when reset is requested repeatedly within 1s,
    // the < 1h condition prevents accidental switching to future dates
    // because we might not recover from it.
    $next = $CFG->themerev + 1;
}

set_config('themerev', $next); // time is unique even when you reset/switch database

if (!empty($CFG->themedesignermode))
{
    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'core', 'themedesigner');
    $cache->purge();
}

// Purge compiled post processed css.
cache::make('core', 'postprocessedcss')->purge();

if ($PAGE)
{
    $PAGE->reload_theme();
}

echo 'Theme cache purged, reload page to build new cache with latest styles.css (don\'t forget to compile your .less files if you don\'t see your changes)';

exit(0);
