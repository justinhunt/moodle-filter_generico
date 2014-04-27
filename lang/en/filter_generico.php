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
 * Strings for filter_generico
 *
 * @package    filter
 * @subpackage generico
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['embedimages'] = 'Embed images';
$string['embedimages_desc'] = 'Replace image urls with images in selected text formats.';
$string['filtername'] = 'Generico';
$string['pluginname'] = 'Generico';
$string['filterdescription'] = 'Convert filter strings into templates merged with data';
$string['settingformats'] = 'Apply to formats';
$string['settingformats_desc'] = 'The filter will be applied only if the original text was inserted in one of the selected formats.';
$string['templateheading'] = 'Settings for Generico Template';
$string['template'] = 'The body of template';
$string['template_desc'] = 'Put the template here, define variables by surround them with pairs of @ marks. eg @@variable@@';
$string['templatekey'] = 'The key that identifies template';
$string['templatekey_desc'] = 'The key should be one word and only contain numbers and letters, underscores, hyphens and dots .';
$string['templatedefaults'] = 'The defaults for template';
$string['templatedefaults_desc'] = 'Define the defaults in comma delimited sets of name=value pairs. eg width=800,height=900,feeling=joy';
