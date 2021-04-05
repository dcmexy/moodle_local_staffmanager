<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see https://www.gnu.org/licenses/.

/**
 * Version details.
 *
 * @package    local_staffmanager
 * @copyright  2021 Dean Chimezie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
global $USER, $DB, $CFG;

$PAGE->set_url('/local/staffmanager/rates.php');
$PAGE->set_context(context_system::instance());
$PAGE->requires->js('/local/staffmanager/assets/js/staffmanager.js');

require_login();

if(!has_capability('local/staffmanager:admin', context_system::instance()))
{
  echo $OUTPUT->header();
  echo "<h3>You do not have permission to view this page.</h3>";
  echo $OUTPUT->footer();
  exit;
}

require_once('forms/rates.php');

$pagetitle = get_string('staffmanager', 'local_staffmanager');
$pageheading = get_string('rates', 'local_staffmanager');

$PAGE->set_title($pagetitle);
$PAGE->set_heading($pageheading);

$id = optional_param('id', '', PARAM_TEXT);
$table = 'local_staffmanager_rates';
$conditions_array = ['id' => $id];

// Instantiate rates_form 
$mform = new rates_form();
$toform = [];

// Form processing and displaying is done here
if ($mform->is_cancelled()) {
  // Handle form cancel operation, if cancel button is present on form
  redirect('/local/staffmanager/rates.php', '', 10);
} else if ($fromform = $mform->get_data()) {
  //In this case you process validated data. $mform->get_data() returns data posted in form.

  if($id) {
    // Has id. Perform update

    $dataobject = $DB->get_record($table, $conditions_array);
    $dataobject->month = $fromform->month;
    $dataobject->year = $fromform->year;
    $dataobject->assignmentrate = $fromform->assignmentrate;
    $dataobject->quizrate = $fromform->quizrate;

    $DB->update_record($table, $dataobject);

  } else {
    if($DB->record_exists('local_staffmanager_rates', ['year'=>$fromform->year,'month'=>$fromform->month])) {
      redirect("/local/staffmanager/ratesform.php", 'Duplicate rate - rate not created', 10,  \core\output\notification::NOTIFY_WARNING);
    } else {
      // No id. Add new record
      $dataobject = new stdClass();
      $dataobject->month = $fromform->month;
      $dataobject->year = $fromform->year;
      $dataobject->assignmentrate = $fromform->assignmentrate;
      $dataobject->quizrate = $fromform->quizrate;

      $orgid = $DB->insert_record($table, $dataobject, true, false);
    }
  }

  redirect("/local/staffmanager/rates.php?id=$id", 'Changes saved', 10, \core\output\notification::NOTIFY_SUCCESS);
} else {
  // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
  // or on the first display of the form.

  if($id) {
    $toform = $DB->get_record($table, $conditions_array);
  }

  //Set default data (if any)
  $mform->set_data($toform);

  echo $OUTPUT->header();

  //displays the form
  $mform->display();

  echo $OUTPUT->footer();
}
