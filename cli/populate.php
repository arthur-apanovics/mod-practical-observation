<?php


use mod_observation\lib;
use mod_observation\observation;
use mod_observation\task;
use mod_observation\task_base;
use mod_observation\criteria;
use mod_observation\criteria_base;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');      // cli only functions
require_once('../lib.php');
require_once('../locallib.php');

$longparams = array(
    'help'     => false,
    'instance' => '',
    'cmid'     => '',
);
$shortparams = array(
    'h' => 'help',
    'i' => 'instance',
    'c' => 'cmid',
);

// now get cli options
list($options, $unrecognized) = cli_get_params($longparams, $shortparams);

if ($unrecognized)
{
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['instance'] != '')
{
    cli_heading('Populate instance');
    list($course, $cm) = get_course_and_cm_from_instance($options['instance'], \OBSERVATION);
}
else if ($options['cmid'] != '')
{
    cli_heading('Populate cmid');
    list($course, $cm) = get_course_and_cm_from_cmid($options['cmid'], \OBSERVATION);
}
else
{
    $help = "Use this tool to populate an existing observation activity with dummy data.
    
    Options:
    -i, --instance      Specify instance id to populate with data
    -c, --cmid          Specify course module id to populate with data
    ";

    echo $help;
    die;
}

// HELPER FUNCTIONS

function random_word_from_list($list)
{
    $list = explode(',', $list);

    return $list[floor((mt_rand() / mt_getrandmax()) * count($list))];
}

function get_random_todo()
{
    $actions = 'call,read,dress,buy,ring,hop,skip,jump,look,rob,find,fly,go,ask,raise,search';
    $bridges = 'the,a,an,my,as,by,to,in,on,with';
    $targets =
        'dog,doctor,store,dance,jig,friend,enemy,business,bull,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday,Mom,Dad';

    return sprintf(
        '%s %s %s',
        ucfirst(random_word_from_list($actions)),
        random_word_from_list($bridges),
        random_word_from_list($targets));
}

function get_random_lorem_paragraph()
{
    $lorem_ipsum_chunks = [
        'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Eget sit amet tellus cras adipiscing enim eu turpis egestas. Sem integer vitae justo eget. Diam quam nulla porttitor massa id. Orci a scelerisque purus semper eget duis at. Nunc non blandit massa enim nec dui. Integer eget aliquet nibh praesent. Et leo duis ut diam quam. Et tortor at risus viverra adipiscing. Enim diam vulputate ut pharetra sit amet. Nullam vehicula ipsum a arcu cursus vitae congue. Nullam vehicula ipsum a arcu cursus vitae.',
        'Porttitor massa id neque aliquam vestibulum morbi blandit cursus risus. Ac ut consequat semper viverra nam libero justo laoreet sit. Faucibus vitae aliquet nec ullamcorper sit amet risus. Vitae congue mauris rhoncus aenean vel elit. Vel pretium lectus quam id leo in vitae turpis. Mollis aliquam ut porttitor leo a diam sollicitudin tempor id. Lectus mauris ultrices eros in cursus turpis massa. Nam at lectus urna duis convallis convallis tellus. Etiam dignissim diam quis enim lobortis scelerisque fermentum dui faucibus. Tempus quam pellentesque nec nam aliquam sem. Massa tempor nec feugiat nisl pretium fusce id. Fermentum posuere urna nec tincidunt. Orci dapibus ultrices in iaculis. Vel pretium lectus quam id leo in vitae turpis. Quam quisque id diam vel quam. Eget egestas purus viverra accumsan. Venenatis urna cursus eget nunc scelerisque viverra mauris in.',
        'Arcu cursus vitae congue mauris rhoncus aenean vel elit. Arcu dictum varius duis at consectetur. Mattis rhoncus urna neque viverra justo nec ultrices. Nisl tincidunt eget nullam non nisi est sit. Elit scelerisque mauris pellentesque pulvinar pellentesque habitant morbi. Ac tortor dignissim convallis aenean et tortor at risus viverra. Arcu dui vivamus arcu felis bibendum ut tristique et egestas. Lorem ipsum dolor sit amet consectetur adipiscing elit ut. At ultrices mi tempus imperdiet nulla malesuada pellentesque elit. Commodo nulla facilisi nullam vehicula ipsum a arcu cursus vitae. Metus vulputate eu scelerisque felis imperdiet proin. Sit amet massa vitae tortor condimentum lacinia quis vel. Nunc aliquet bibendum enim facilisis gravida neque convallis a. Maecenas accumsan lacus vel facilisis. Egestas quis ipsum suspendisse ultrices. Nulla facilisi nullam vehicula ipsum. Turpis egestas integer eget aliquet nibh praesent tristique magna sit. Viverra aliquet eget sit amet tellus. Velit egestas dui id ornare arcu odio. Quam vulputate dignissim suspendisse in est ante in nibh mauris.',
        'Consequat mauris nunc congue nisi vitae suscipit. Ac felis donec et odio pellentesque diam volutpat commodo. Neque viverra justo nec ultrices. A scelerisque purus semper eget duis at tellus at urna. Odio aenean sed adipiscing diam donec. Eros donec ac odio tempor orci. Arcu bibendum at varius vel pharetra vel turpis nunc eget. At quis risus sed vulputate odio. Dolor magna eget est lorem ipsum dolor sit. Morbi tristique senectus et netus et malesuada fames ac. Egestas purus viverra accumsan in nisl. Massa sapien faucibus et molestie ac feugiat. Varius morbi enim nunc faucibus a pellentesque sit. Auctor augue mauris augue neque gravida in. Nunc sed augue lacus viverra vitae congue eu consequat. Sagittis orci a scelerisque purus semper eget. Lacus vestibulum sed arcu non odio euismod. Vitae tortor condimentum lacinia quis vel eros donec ac odio. Faucibus a pellentesque sit amet porttitor eget. Arcu cursus euismod quis viverra nibh cras pulvinar mattis.',
        'Iaculis eu non diam phasellus vestibulum lorem sed. Nunc scelerisque viverra mauris in aliquam sem. Vitae suscipit tellus mauris a diam maecenas sed enim ut. Leo a diam sollicitudin tempor id eu. Nisi porta lorem mollis aliquam ut porttitor leo a diam. Nunc lobortis mattis aliquam faucibus purus in massa tempor. Morbi tristique senectus et netus et malesuada fames ac turpis. Purus sit amet luctus venenatis lectus magna. Feugiat nisl pretium fusce id. Ornare lectus sit amet est placerat in. Vel pretium lectus quam id leo in vitae turpis. Vitae nunc sed velit dignissim. A cras semper auctor neque vitae tempus quam pellentesque. Elit pellentesque habitant morbi tristique.',
        'Non odio euismod lacinia at. Interdum velit euismod in pellentesque massa placerat duis. Tellus molestie nunc non blandit. Ac odio tempor orci dapibus ultrices in iaculis nunc. Et tortor consequat id porta nibh. Dignissim sodales ut eu sem integer vitae justo. At imperdiet dui accumsan sit amet nulla facilisi morbi tempus. Viverra nibh cras pulvinar mattis. Pretium quam vulputate dignissim suspendisse in. Diam sollicitudin tempor id eu nisl nunc mi ipsum faucibus. Nulla malesuada pellentesque elit eget gravida cum sociis.',
        'Justo eget magna fermentum iaculis eu non diam phasellus vestibulum. Non diam phasellus vestibulum lorem sed risus. Aliquet nec ullamcorper sit amet risus nullam eget felis. Pellentesque elit eget gravida cum sociis. Sollicitudin aliquam ultrices sagittis orci a. Sit amet dictum sit amet. Hendrerit gravida rutrum quisque non tellus orci ac auctor. Vel quam elementum pulvinar etiam. Morbi enim nunc faucibus a pellentesque sit amet porttitor. Vitae proin sagittis nisl rhoncus mattis rhoncus. Risus feugiat in ante metus dictum at. Hac habitasse platea dictumst vestibulum rhoncus est pellentesque elit. Velit aliquet sagittis id consectetur purus ut faucibus pulvinar elementum. Consectetur lorem donec massa sapien faucibus et molestie ac.',
        'Sed id semper risus in hendrerit gravida rutrum quisque. Nulla facilisi etiam dignissim diam quis enim. Consequat interdum varius sit amet mattis. Accumsan tortor posuere ac ut consequat semper viverra nam libero. Nisl pretium fusce id velit ut tortor pretium. Diam maecenas sed enim ut sem viverra. Faucibus turpis in eu mi bibendum. Enim sit amet venenatis urna. Metus vulputate eu scelerisque felis imperdiet proin fermentum leo vel. Neque egestas congue quisque egestas diam in arcu cursus euismod. Nisi est sit amet facilisis magna. Vestibulum sed arcu non odio euismod lacinia at. Platea dictumst quisque sagittis purus sit amet volutpat consequat. Id aliquet lectus proin nibh nisl condimentum.',
        'Enim tortor at auctor urna. Sed cras ornare arcu dui vivamus arcu felis. Vulputate enim nulla aliquet porttitor lacus luctus. In fermentum posuere urna nec. Sagittis aliquam malesuada bibendum arcu vitae. Malesuada pellentesque elit eget gravida cum sociis natoque penatibus. Habitasse platea dictumst vestibulum rhoncus est. Nisi porta lorem mollis aliquam. Donec enim diam vulputate ut pharetra sit. Augue mauris augue neque gravida in fermentum et sollicitudin. Lacus luctus accumsan tortor posuere ac ut consequat semper. Sodales ut eu sem integer vitae justo. Cum sociis natoque penatibus et magnis dis parturient. Amet venenatis urna cursus eget. Ligula ullamcorper malesuada proin libero nunc consequat interdum varius. Purus non enim praesent elementum facilisis leo vel fringilla est. Ipsum consequat nisl vel pretium lectus. Malesuada proin libero nunc consequat interdum varius sit. Sapien nec sagittis aliquam malesuada. Id diam maecenas ultricies mi.',
        'Turpis nunc eget lorem dolor sed viverra ipsum nunc aliquet. Sagittis vitae et leo duis ut diam quam. Nulla facilisi cras fermentum odio eu. Nisl nisi scelerisque eu ultrices vitae auctor eu augue ut. Bibendum at varius vel pharetra vel. A arcu cursus vitae congue. Et malesuada fames ac turpis egestas sed tempus. Convallis tellus id interdum velit laoreet id donec ultrices tincidunt. Sit amet nisl purus in. Purus sit amet luctus venenatis lectus. Felis imperdiet proin fermentum leo vel orci.',
    ];

    return $lorem_ipsum_chunks[rand(0, (count($lorem_ipsum_chunks) - 1))];
}

// CREATE STUFF

$observation = new observation($cm);
$existing_tasks = $observation->get_tasks();

$num_of_tasks = rand(3,7);
$task_names = [];
for ($i = 1; $i < $num_of_tasks; $i++)
{
    $task_names[] = get_random_todo();
}

foreach ($task_names as $index => $task_name)
{
    // check if task with same name already exists
    if (!$task = lib::find_in_assoc_array_key_value_or_null($existing_tasks, task::COL_NAME, $task_name))
    {
        $task = new task_base();
        $task->set(task::COL_NAME, $task_name);
        $task->set(task::COL_OBSERVATIONID, $observation->get_id_or_null());
        $task->set(task::COL_SEQUENCE, $task->get_next_sequence_number_in_activity());
        $task->set(task::COL_INTRO_LEARNER, get_random_lorem_paragraph());
        $task->set(task::COL_INTRO_OBSERVER, get_random_lorem_paragraph());
        $task->set(task::COL_INTRO_ASSESSOR, get_random_lorem_paragraph());
        $task->set(task::COL_INT_ASSIGN_OBS_LEARNER, get_random_lorem_paragraph());
        $task->set(task::COL_INT_ASSIGN_OBS_OBSERVER, get_random_lorem_paragraph());
        $task->set(task::COL_INTRO_LEARNER_FORMAT, 1);
        $task->set(task::COL_INTRO_OBSERVER_FORMAT, 1);
        $task->set(task::COL_INTRO_ASSESSOR_FORMAT, 1);
        $task->set(task::COL_INT_ASSIGN_OBS_LEARNER_FORMAT, 1);
        $task->set(task::COL_INT_ASSIGN_OBS_OBSERVER_FORMAT, 1);

        $task->create();
    }
    else
    {
        // remove this task so we don't have to loop over it again
        unset($existing_tasks[$task->get_id_or_null()]);
    }

    // create a full task instance
    $task = new task($task);
    if (empty($task->get_criteria()))
    {
        $num_of_criterias = rand(1, 8);
        for ($i = 1; $i < $num_of_criterias; $i++)
        {
            $criteria = new criteria_base();
            $criteria->set(criteria::COL_TASKID, $task->get_id_or_null());
            $criteria->set(criteria::COL_NAME, get_random_todo());
            $criteria->set(criteria::COL_DESCRIPTION, get_random_lorem_paragraph());
            $criteria->set(criteria::COL_DESCRIPTION_FORMAT, 1);
            $criteria->set(criteria::COL_FEEDBACK_REQUIRED, rand(0,1));
            $criteria->set(criteria::COL_SEQUENCE, $criteria->get_next_sequence_number_in_task());

            $criteria->create();
        }
    }
}

echo sprintf('Created %s tasks in %s', count($task_names), $observation->get_formatted_name());
exit(0);
