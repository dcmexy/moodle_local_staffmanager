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
 * Main interface to Moodle PHP code check
 *
 * @package    local_staffmanager
 * @copyright  2021 Dean Chimezie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
global $USER, $DB, $CFG;

$PAGE->set_url('/local/staffmanager/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->requires->js('/local/staffmanager/assets/js/staffmanager.js');

require_login();

$month = optional_param('month', '', PARAM_TEXT);
$year = optional_param('year', '', PARAM_TEXT);

$obj = new stdClass();
$obj->month = (int)$month;
$obj->year = (int)$year;
$obj->monthname = date('F', strtotime($year . '-' . $month));

$pagetitle = get_string('staffmanager', 'local_staffmanager');
$pageheading = get_string('staffmanager', 'local_staffmanager');

$PAGE->set_title($pagetitle);
$PAGE->set_heading($pageheading);

// Database

$results = new stdClass();

$start = mktime(0, 0, 0, $obj->month, 1, $obj->year);
$end  = mktime(23, 59, 00, $obj->month+1, 0, $obj->year);

$table = 'local_staffmanager_rates';
$conditions_array = [
  'year' => $year,
  'month' => $month
];

$rate = $DB->get_record($table, $conditions_array);

$sql = "SELECT DISTINCT(gg.usermodified) as graderid
FROM {grade_grades} AS gg
LEFT JOIN {user} AS grader ON grader.id = gg.usermodified
WHERE gg.usermodified <> '' AND gg.finalgrade > 0 AND gg.timemodified >= ". $start." AND gg.timemodified <=".$end ;
$graders = $DB->get_records_sql($sql);
$fields = 'firstname, lastname, id, email';

foreach($graders as $key => $value) {
  $graders[$key] = $DB->get_record('user', ['id'=>$graders[$key]->graderid], $fields);

  // Graded Assignments
  $sql = "SELECT gg.id as gradeid, gi.itemmodule AS modulename, gg.timemodified AS tmodified
  FROM {grade_grades} AS gg
  JOIN {grade_items} AS gi ON gi.id = gg.itemid
  WHERE gg.usermodified = ". $graders[$key]->id." AND gg.finalgrade > 0 AND gg.timemodified >= " . $start . " AND gg.timemodified <=" . $end;

  $grades = $DB->get_records_sql($sql);

  $graders[$key]->totalvalue = 0;

  foreach($grades as $gradekey => $gradevalue) {
    $grades[$gradekey]->value = 0;

    if($grades[$gradekey]->modulename == 'assign') {
      $grades[$gradekey]->value = $rate->assignmentrate;
    }

    if($grades[$gradekey]->modulename == 'quiz') {
      $grades[$gradekey]->value = $rate->quizrate;
    }

    $graders[$key]->totalvalue += $grades[$gradekey]->value;
  }
  $graders[$key]->gradescounts = count($grades);
}

$results->data = array_values($graders);
$results->month = $month;
$results->year = $year;

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_staffmanager/searchbar', $obj);
echo $OUTPUT->render_from_template('local_staffmanager/searchresults', $results);
echo $OUTPUT->download_dataformat_selector('Download', 'download.php', 'dataformat', $conditions_array);
echo $OUTPUT->footer();
