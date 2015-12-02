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
 * Manual authentication plugin upgrade code
 *
 * @package    filter
 * @subpackage generico
 * @copyright  2015 Justin Hunt (http://poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_filter_generico_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();


    if ($oldversion < 2015080301) {
        
        
         $conf = get_object_vars(get_config('filter_generico'));

	
	   //determine which template we are using
		for($tempindex=1;$tempindex<=20;$tempindex++){
			switch ($conf['templatekey_' . $tempindex]){
				case 'lightboxyoutube':
				case 'piechart':
				case 'barchart':
				case 'linechart':
					set_config('filter_generico/template_amd_' . $tempindex,0);
					break;
				default:
					set_config('filter_generico/template_amd_' . $tempindex,1);	
			}
		}

        upgrade_plugin_savepoint(true, 2015080301, 'filter', 'generico');
    }



    return true;
}
