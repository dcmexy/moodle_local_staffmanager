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

require_login();

if(!has_capability('local/staffmanager:admin', context_system::instance()))
{
  echo $OUTPUT->header();
  echo "<h3>You do not have permission to view this page.</h3>";
  echo $OUTPUT->footer();
  exit;
}

$month = optional_param('month', '', PARAM_TEXT);
$year = optional_param('year', '', PARAM_TEXT);
$dataformat = optional_param('dataformat', '', PARAM_ALPHA);

$monthname = date('F', strtotime($year . '-' . $month));

$start = mktime(0, 0, 0, (int)$month, 1, (int)$year);
$end  = mktime(23, 59, 00, (int)$month+1, 0, (int)$year);

$table = 'local_staffmanager_rates';
$conditions_array = ['year' => $year, 'month' => $month];

$rate = $DB->get_record($table, $conditions_array);

$sql = "SELECT gg.id as gradeid,
concat('$year','') AS year,
concat('$monthname','') AS month,
grader.firstname AS graderfirstname,
grader.lastname AS graderlastname,
grader.email AS graderemail,
c.fullname as coursename,
u.firstname AS studentfirstname,
u.lastname AS studentlastname,
u.email AS studentemail,
gi.itemname AS gradeitemname,
gi.itemmodule AS modulename,
gg.finalgrade AS finalgrade,
gg.timemodified AS tmodified
FROM {grade_grades} AS gg
JOIN {user} AS u ON u.id = gg.userid
JOIN {user} AS grader ON grader.id = gg.usermodified
JOIN {grade_items} AS gi ON gi.id = gg.itemid
JOIN {course} AS c ON gi.courseid = c.id
WHERE gg.finalgrade > 0
AND gg.timemodified >= ". $start . "
AND gg.timemodified <=" . $end;

$grades = $DB->get_records_sql($sql);

foreach ($grades as $key => $value)
{
    $grades[$key]->value = 0;
    if($grades[$key]->modulename == 'assign')
    {
      $grades[$key]->value = "$".$rate->assignmentrate;
    }
    if($grades[$key]->modulename == 'quiz')
    {
      $grades[$key]->value = "$".$rate->quizrate;
    }
}

//print_r($grades);
$obj = new ArrayObject( $grades );
$it = $obj->getIterator();

$columns = array(
  'year' => "Year",
  'monthname' => "Month",
  'graderfirstname' => get_string('graderfirstname', 'local_staffmanager'),
  'graderlastname' => get_string('graderlastname', 'local_staffmanager'),
  'graderemail' => get_string('graderemail', 'local_staffmanager'),
  'coursename' => get_string('coursename', 'local_staffmanager'),
  'studentfirstname' => get_string('studentfirstname', 'local_staffmanager'),
  'studentlastname' => get_string('studentlastname', 'local_staffmanager'),
  'studentemail' => get_string('studentemail', 'local_staffmanager'),
  'gradeitemname' => get_string('gradeitemname', 'local_staffmanager'),
  'modulename' => get_string('modulename', 'local_staffmanager'),
  'finalgrade' => get_string('finalgrade', 'local_staffmanager'),
  'value' => get_string('value', 'local_staffmanager'),
  'datetimemodified' => get_string('datetimemodified', 'local_staffmanager')
);

\core\dataformat::download_data('graderdata', $dataformat, $columns, $it, function($record) {
  // Process record
  $record->datetimemodified = date('d-M-Y H:m',  $record->tmodified);
  unset($record->gradeid);
  unset($record->tmodified);
  return $record;
});

