# Observation activity

#### Class structure
* all persistent objects inherit from `db_model_base`, this is a small ORM layer that adds methods 
like `create`, `update`, etc.
* all persistent objects have a **base class** and a **'instance'** class. Base classes contain database info 
(e.g. column names, table name, etc.), allow creation of new records and contain helper methods. 
Instance classes are populated with related data, e.g. `learner_task_submission` instance class will be 
populated with `learner_attempt` class instances and so on.

#### Main workflow for rendering a page:
1. root page (e.g. `view.php`) sets up the page, performs permission checks and initializes any required variables
2. appropriate `renderer` method is called from the root page (e.g. `view.php` calls `view_activity`)
3. renderer method performs more granular permission check and will contain most of the logic (not ideal, but it works)
4. renderer method populates template data, usually starting with `export_template_data` method on the `templateable`
interface, *any additional data not exported by the `templateable` interface* is appended to the **'extra'** array to
make it easier to find the data source when working with mustache templates
5. finally, `render_from_template` is passed template data and result is returned and rendered

#### `external.php`
Contains methods called by AJAX. These are a major pain in the ass to work with, refer to the *almighty
[moodle documentation](https://docs.moodle.org/dev/External_functions_API)*

#### Events
Act as both log entries and event emitters. Careerforce' Marker block depends on these to populate its data.

#### Backup / Restore
Avoid altering, creating or deleting columns in this project. If altered, you **must** update the backup/restore logic
too, which is **much harder** than it seems at first glance. Pay special attention to how files are handled, some 
corners had to be cut there...

#### JS / CSS
Do not rely on jQuery plugins, they are notoriously difficult to initialize in Moodle. Always write your JS as `AMD`
modules and don't forget to minify using the built-in grunt task.
Similar rules apply to CSS, write your CSS in `./less/styles.less` and compile using grunt to `./styles.css` for 
Moodle to pick up the styles.

#### locallib.php
Contains helper methods.

#### `cli/`
Contains scripts that help to get around Moodle's annoying logic, e.g. upgrading the plugin, reinstalling database, etc.
