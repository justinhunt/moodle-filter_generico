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
 * Generico template admin
 *
 * @package    filter_generico
 * @copyright  2016 Justin Hunt {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('genericotemplatesadmin');

// Get template to update.
$updatetemplate = optional_param('updatetemplate', 0, PARAM_INT);

$updated = 0;
$redirecturl = new moodle_url($CFG->wwwroot . '/filter/generico/genericotemplatesadmin.php', []);
if ($updatetemplate == -1) {
    $updated = \filter_generico\presets_control::update_all_templates();
    redirect($redirecturl, get_string('templateupdated', 'filter_generico', $updated));
} else if ($updatetemplate > 0) {
    $updated = \filter_generico\presets_control::update_template($updatetemplate);
    redirect($redirecturl, get_string('templateupdated', 'filter_generico', $updated));
}

// If we are exporting html, do that.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('genericotemplatesadmin', 'filter_generico'), 3);
echo \filter_generico\templateadmintools::fetch_template_table();
echo $OUTPUT->footer();


