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

$PAGE->set_url('/local/staffmanager/graderdetails.php');
$PAGE->set_context(context_system::instance());

require_login();

$pagetitle = get_string('staffmanager', 'local_staffmanager');
$pageheading = get_string('searchstaff', 'local_staffmanager');

$PAGE->set_title($pagetitle);
$PAGE->set_heading($pageheading);

$month = optional_param('month', '', PARAM_TEXT);
$year = optional_param('year', '', PARAM_TEXT);
$graderid = optional_param('grader', '', PARAM_TEXT);

$start = mktime(0, 0, 0, $month, 1, $year);
$end = mktime(23, 59, 0, $month + 1, 0, $year);

$table = 'local_staffmanager_rates';
$table_user = 'user';
$grader_conditions_array = ['id' => $graderid];
$rates_conditions_array = ['year' => $year, 'month' => $month];
$grader_fields = 'firstname, lastname, id, email';

$results = new stdClass();

// Get Rates
// TODO - Check if rates exist!
$rates = $DB->get_record($table, $rates_conditions_array);

$grader = $DB->get_record($table_user, $grader_conditions_array, $grader_fields);
$results->grader = $grader;

// Graded Assignments
$sql = "SELECT gg.id as gradeid, c.fullname as coursename, u.firstname AS studentfirstname, u.lastname AS studentlastname, gi.itemname AS gradeitemname,
gi.itemmodule AS modulename, gg.finalgrade AS finalgrade, gg.feedback AS gradefeedback, gg.timemodified AS tmodified
FROM {grade_grades} AS gg
JOIN {user} AS u ON u.id = gg.userid
JOIN {grade_items} AS gi ON gi.id = gg.itemid
JOIN {course} AS c ON gi.courseid = c.id
WHERE gg.usermodified = " . $graderid . " AND gg.finalgrade > 0 AND gg.timemodified >= " . $start . " AND gg.timemodified <=" . $end;

$grades = $DB->get_records_sql($sql);
$totalvalue = 0;

// Format modified time
foreach($grades as $key => $value) {
  $grades[$key]->value = 0;

  if($grades[$key]->modulename == 'assign') {
    $grades[$key]->value = $rates->assignmentrate;
  }

  if($grades[$key]->modulename == 'quiz') {
    $grades[$key]->value = $rates->quizrate;
  }

  $totalvalue  += $grades[$key]->value;

  $grades[$key]->datetimemodified = date('d-M-Y H:m', $grades[$key]->tmodified);
}

$results->data = array_values($grades);
$results->month = $month;
$results->year = $year;
$results->monthname = date('F', strtotime($year . '-' . $month));
$results->totalvalue  = number_format($totalvalue, 2, '.', ' ');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_staffmanager/graderdetail', $results);
echo $OUTPUT->footer();