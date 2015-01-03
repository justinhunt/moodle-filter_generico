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
	
	require_once($CFG->dirroot . '/filter/generico/lib.php');
	require_once($CFG->dirroot . '/filter/generico/locallib.php');

	//add folder in property tree for settings pages
	$ADMIN->add('filtersettings',new admin_category('filter_generico_category', 'Generico'));
	$conf = get_config('filter_generico');
	
	//add the common settings page
   	$settings_page = new admin_settingpage('filter_generico_commonsettingspage' ,get_string('commonpageheading', 'filter_generico'));
	$settings_page->add(new admin_setting_configtext('filter_generico/jqueryurl', 
				get_string('jqueryurl', 'filter_generico'),
				get_string('jqueryurl_desc', 'filter_generico'), 
				 '//code.jquery.com/jquery-1.11.2.min.js', PARAM_RAW,50));
	//add page to category
	$ADMIN->add('filter_generico_category', $settings_page);

				 
	//Add the template pages
	for($tindex=1;$tindex<=FILTER_GENERICO_TEMPLATE_COUNT;$tindex++){
		 
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
				
		//presets
		$settings_page->add(new admin_setting_genericopresets('filter_generico/templatepresets_' . $tindex, 
				get_string('presets', 'filter_generico'), get_string('presets_desc', 'filter_generico'),$tindex));
			
				
		//template key
		 $settings_page->add(new admin_setting_configtext('filter_generico/templatekey_' . $tindex , 
				get_string('templatekey', 'filter_generico',$tindex),
				get_string('templatekey_desc', 'filter_generico'), 
				 '', PARAM_ALPHANUMEXT));
		
		//template body
		 $settings_page->add(new admin_setting_configtextarea('filter_generico/template_' . $tindex,
					get_string('template', 'filter_generico',$tindex),
					get_string('template_desc', 'filter_generico'),''));
		
		//template body end
		 $settings_page->add(new admin_setting_configtextarea('filter_generico/templateend_' . $tindex,
					get_string('templateend', 'filter_generico',$tindex),
					get_string('templateend_desc', 'filter_generico'),''));
		
		//template defaults			
		 $settings_page->add(new admin_setting_configtextarea('filter_generico/templatedefaults_' . $tindex,
					get_string('templatedefaults', 'filter_generico', $tindex),
					get_string('templatedefaults_desc', 'filter_generico'),''));
					
		//template page JS heading
		$settings_page->add(new admin_setting_heading('filter_generico/templateheading_js' . $tindex, 
				get_string('templateheadingjs', 'filter_generico',$tname), ''));
					
		//additional JS (external link)
		 $settings_page->add(new admin_setting_configtext('filter_generico/templaterequire_js_' . $tindex , 
				get_string('templaterequire_js', 'filter_generico',$tindex),
				get_string('templaterequire_js_desc', 'filter_generico'), 
				 '', PARAM_RAW,50));
		
		//template jquery		
		 $settings_page->add(new admin_setting_configcheckbox('filter_generico/templaterequire_jquery_' . $tindex, 
				get_string('templaterequire_jquery', 'filter_generico',$tindex),
				get_string('templaterequire_jquery_desc', 'filter_generico'), 
				 0));		 
				 
		
		//template body script
		 $settings_page->add(new admin_setting_configtextarea('filter_generico/templatescript_' . $tindex,
					get_string('templatescript', 'filter_generico',$tindex),
					get_string('templatescript_desc', 'filter_generico'),
					'',PARAM_RAW));
		
		//additional JS (upload)
		//see here: for integrating this https://moodle.org/mod/forum/discuss.php?d=227249
		$name = 'filter_generico/uploadjs' . $tindex;
		$title =get_string('uploadjs', 'filter_generico',$tindex);
		$description = get_string('uploadjs_desc', 'filter_generico');
		$settings_page->add(new admin_setting_configstoredfile($name, $title, $description, 'uploadjs' . $tindex));
				 
		//template page CSS heading
		$settings_page->add(new admin_setting_heading('filter_generico/templateheading_css_' . $tindex, 
				get_string('templateheadingcss', 'filter_generico',$tname), ''));
				 
		//additional CSS (external link)
		$settings_page->add(new admin_setting_configtext('filter_generico/templaterequire_css_' . $tindex , 
				get_string('templaterequire_css', 'filter_generico',$tindex),
				get_string('templaterequire_css_desc', 'filter_generico'), 
				 '', PARAM_RAW,50));
				 
		//template body css
		 $settings_page->add(new admin_setting_configtextarea('filter_generico/templatestyle_' . $tindex,
					get_string('templatestyle', 'filter_generico',$tindex),
					get_string('templatestyle_desc', 'filter_generico'),
					'',PARAM_RAW));
		
		//additional CSS (upload)
		$name = 'filter_generico/uploadcss' . $tindex;
		$title =get_string('uploadcss', 'filter_generico',$tindex);
		$description = get_string('uploadcss_desc', 'filter_generico');
		$settings_page->add(new admin_setting_configstoredfile($name, $title, $description, 'uploadcss' . $tindex));


					
		//add page to category
		$ADMIN->add('filter_generico_category', $settings_page);
		//$settings->add($settings_page);
	}
	
}
