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
 * @package    filter
 * @subpackage generico
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$settings = null;
defined('MOODLE_INTERNAL') || die;
if (is_siteadmin()) {
	global $PAGE;


	//add folder in property tree for settings pages
    $generico_category_name='generico_category';
    $generico_category = new admin_category($generico_category_name, 'Generico');
	$ADMIN->add('filtersettings',$generico_category);
	$conf = get_config('filter_generico');

	//add the common settings page
	// we changed this to use the default settings id for the top page. This way in the settings link on the manage filters
	 //page, we will arrive here. Else the link will show there, but it will error out if clicked.
   	//$settings_page = new admin_settingpage('filter_generico_commonsettingspage' ,get_string('commonpageheading', 'filter_generico'));
	$settings_page = new admin_settingpage('filtersettinggenerico' ,get_string('commonpageheading', 'filter_generico'));
	
	$settings_page->add(new admin_setting_configtext('filter_generico/templatecount', 
				get_string('templatecount', 'filter_generico'),
				get_string('templatecount_desc', 'filter_generico'), 
				 \filter_generico\generico_utils::FILTER_GENERICO_TEMPLATE_COUNT, PARAM_INT,20));

    //cloud poodll credentials
    $settings_page->add(new admin_setting_heading('filter_generico_cpapi_settings', get_string('cpapi_heading', 'filter_generico'), get_string('cpapi_heading_desc', 'filter_generico')));
    $settings_page->add(new admin_setting_configtext('filter_generico/cpapiuser', get_string('cpapiuser', 'filter_generico'),
        get_string('cpapiuser_details', 'filter_generico'), ''));
    $settings_page->add(new admin_setting_configtext('filter_generico/cpapisecret', get_string('cpapisecret', 'filter_generico'),
        get_string('cpapisecret_details', 'filter_generico'), ''));

	//add page to category
	$ADMIN->add($generico_category_name, $settings_page);

    $genericotemplatesadmin_settings = new admin_externalpage('genericotemplatesadmin', get_string('templates', 'filter_generico'),
        $CFG->wwwroot . '/filter/generico/genericotemplatesadmin.php' );

    $ADMIN->add($generico_category_name, $genericotemplatesadmin_settings);

    //Templates
    $template_pages =  \filter_generico\settingstools::fetch_template_pages($conf);
    foreach ($template_pages as $template_page) {
        $ADMIN->add($generico_category_name, $template_page);
    }
	
}
