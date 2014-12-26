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

	//add folder in property tree for settings pages
	$ADMIN->add('filtersettings',new admin_category('filter_generico_category', 'Generico'));
	 $conf = get_config('filter_generico');
	
	for($tindex=1;$tindex<11;$tindex++){
		 
		 //template display name
		 if($conf && property_exists($conf,'templatekey_' . $tindex)){
		 	$tname = $conf->{'templatekey_' . $tindex};
		 	if(empty($tname)){$tname=$tindex;}
		 }else{
		 	$tname = $tindex;
		 }
		 
		 //template settings Page Settings 
   		$settings_page = new admin_settingpage('filter_generico_templatepage_' . $tindex,get_string('templatepageheading', 'filter_generico',$tname));
		
		//template page heading
		$settings_page->add(new admin_setting_heading('filter_generico/templateheading_' . $tindex, 
				get_string('templateheading', 'filter_generico',$tname), ''));
				
		//template key
		 $settings_page->add(new admin_setting_configtext('filter_generico/templatekey_' . $tindex , 
				get_string('templatekey', 'filter_generico') . ' ' . $tindex,
				get_string('templatekey_desc', 'filter_generico'), 
				 '', PARAM_ALPHANUMEXT));
		//template body
		 $settings_page->add(new admin_setting_configtextarea('filter_generico/template_' . $tindex,
					get_string('template', 'filter_generico') . ' ' . $tindex,
					get_string('template_desc', 'filter_generico'),''));
		//template defaults			
		 $settings_page->add(new admin_setting_configtextarea('filter_generico/templatedefaults_' . $tindex,
					get_string('templatedefaults', 'filter_generico') . ' ' . $tindex,
					get_string('templatedefaults_desc', 'filter_generico'),''));
					
		//additional JS (external link)
		 $settings_page->add(new admin_setting_configtext('filter_generico/templaterequire_js_' . $tindex , 
				get_string('templaterequire_js', 'filter_generico') . ' ' . $tindex,
				get_string('templaterequire_js_desc', 'filter_generico'), 
				 '', PARAM_RAW));
				 
		//additional CSS (external link)
		$settings_page->add(new admin_setting_configtext('filter_generico/templaterequire_css_' . $tindex , 
				get_string('templaterequire_css', 'filter_generico') . ' ' . $tindex,
				get_string('templaterequire_css_desc', 'filter_generico'), 
				 '', PARAM_RAW));
		
		//template jquery heading		
		 $settings_page->add(new admin_setting_configcheckbox('filter_generico/templaterequire_jquery_' . $tindex, 
				get_string('templaterequire_jquery', 'filter_generico') . ' ' . $tindex,
				get_string('templaterequire_jquery_desc', 'filter_generico'), 
				 0));		 
				 
		
		//additional JS (upload)
		//see here: for integrating this https://moodle.org/mod/forum/discuss.php?d=227249
		/*
		$name = 'theme_essential/slide1image';
    $title = get_string('slide1image', 'theme_essential');
    $description = get_string('slide1imagedesc', 'theme_essential');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'slide1image');
		*/

		//additional CSS (upload)
			/*
		$name = 'theme_essential/slide1image';
    $title = get_string('slide1image', 'theme_essential');
    $description = get_string('slide1imagedesc', 'theme_essential');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'slide1image');
		*/
		
					
		//add page to category
		$ADMIN->add('filter_generico_category', $settings_page);
		//$settings->add($settings_page);
	}
	
}
