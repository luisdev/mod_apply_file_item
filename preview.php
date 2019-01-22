<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * print a printview of apply-items
 *
 * @author Fumi Iseki
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_apply (modified from mod_feedback that by Andreas Grabs)
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id         = required_param('id', PARAM_INT);
$courseid   = optional_param('courseid', false, PARAM_INT);
$user_id    = optional_param('user_id', 0, PARAM_INT);
$submit_id  = optional_param('submit_id', 0, PARAM_INT);
$submit_ver = optional_param('submit_ver', -1, PARAM_INT);

$this_action = 'preview';


////////////////////////////////////////////////////////
//get the objects
if (! $cm = get_coursemodule_from_id('apply', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array("id"=>$cm->course))) {
    print_error('coursemisconf');
}
if (! $apply = $DB->get_record('apply', array("id"=>$cm->instance))) {
    print_error('invalidcoursemodule');
}
if (!$courseid) $courseid = $course->id;

$context = context_module::instance($cm->id);


////////////////////////////////////////////////////////
// Check
require_login($course, true, $cm);
require_capability('mod/apply:view', $context);


///////////////////////////////////////////////////////////////////////////
// Print the page header
$base_url = new moodle_url('/mod/apply/'.$this_action.'.php');
$base_url->params(array('id'=>$cm->id, 'courseid'=>$courseid));
$this_url = new moodle_url($base_url);
$this_url->params(array('submit_id'=>$submit_id, 'submit_ver'=>$submit_ver));

$PAGE->navbar->add(get_string('apply:'.$this_action, 'apply'));
$PAGE->set_url($this_url);
$PAGE->set_pagelayout('print');
$PAGE->set_title(format_string($apply->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo '<div align="center">';
echo $OUTPUT->heading(format_text($apply->name), 3);
echo '</div>';


///////////////////////////////////////////////////////////////////////////

$submit = $DB->get_record('apply_submit', array('id'=>$submit_id, 'user_id'=>$user_id));
if ($submit) {
    $items = $DB->get_records('apply_item', array('apply_id'=>$submit->apply_id), 'position');
    if (is_array($items)) {
        if ($submit_ver==-1 and apply_exist_draft_values($submit->id)) $submit_ver = 0;
        require('entry_view.php');
    }
}


///////////////////////////////////////////////////////////////////////////
/// Finish the page
echo $OUTPUT->footer();

