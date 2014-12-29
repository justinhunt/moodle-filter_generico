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

$string['filtername'] = 'Generico';
$string['pluginname'] = 'Generico';
$string['filterdescription'] = 'Convert filter strings into templates merged with data';
$string['templatepageheading'] = 'Template: {$a}';
$string['templateheading'] = 'Settings for Generico Template {$a}';
$string['template'] = 'The body of template';
$string['template_desc'] = 'Put the template here, define variables by surrounding them with @@ marks at either e. eg @@variable@@';
$string['templatekey'] = 'The key that identifies template';
$string['templatekey_desc'] = 'The key should be one word and only contain numbers and letters, underscores, hyphens and dots .';
$string['templateend'] = 'Template end tags';
$string['templateend_desc'] = 'If your template encloses user content, eg an info box, put the closing tags here. The user will enter something like {GENERICO:mytag_end} to close out the filter.';
$string['templatescript'] = 'Template script';
$string['templatescript_desc'] = 'If your template needs to run custom javascript, enter that here. It will be run once all the elements have loaded on the page.';
$string['templatedefaults'] = 'The defaults for template';
$string['templatedefaults_desc'] = 'Define the defaults in comma delimited sets of name=value pairs. eg width=800,height=900,feeling=joy';
$string['templaterequire_css'] = 'Requires CSS Template';
$string['templaterequire_js'] = 'Requires JS Template';
$string['templaterequire_jquery'] = 'Requires JQuery Template';
$string['templaterequire_css_desc'] = 'A link(1 only) to an external CSS file that this template requires. optional.';
$string['templaterequire_js_desc'] = 'A link(1 only) to an external JS file that this template requires. optional.';
$string['templaterequire_jquery_desc'] = 'If the template requires JQuery check this.';

$string['uploadjs'] = 'Upload JS';
$string['uploadjs_desc'] = 'You can upload one js library file which will be loaded for your template. Only one.';

$string['uploadcss'] = 'Upload CSS';
$string['uploadcss_desc'] = 'You can upload one CSS file which will be loaded for your template. Only one.';
