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

require_login();

$pagetitle = get_string('staffmanager', 'local_staffmanager');
$pageheading = get_string('rates', 'local_staffmanager');

$PAGE->set_title($pagetitle);
$PAGE->set_heading($pageheading);

$rates = $DB->get_records('local_staffmanager_rates', null, 'year DESC, month ASC');

foreach ($rates as $key => $value) {
  $rates[$key]->monthname = date('F', mktime(0, 0, 0, $rates[$key]->month, 10));
}

$results = new stdClass();
$results->data = array_values($rates);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_staffmanager/rates', $results);
echo $OUTPUT->footer();