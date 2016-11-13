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
	require_once($CFG->dirroot . '/filter/generico/lib.php');
	require_once($CFG->dirroot . '/filter/generico/locallib.php');

	//add folder in property tree for settings pages
	$ADMIN->add('filtersettings',new admin_category('filter_generico_category', 'Generico'));
	$conf = get_config('filter_generico');
	
	//add the common settings page
	// we changed this to use the default settings id for the top page. This way in the settings link on the manage filters
	 //page, we will arrive here. Else the link will show there, but it will error out if clicked.
   	//$settings_page = new admin_settingpage('filter_generico_commonsettingspage' ,get_string('commonpageheading', 'filter_generico'));
	$settings_page = new admin_settingpage('filtersettinggenerico' ,get_string('commonpageheading', 'filter_generico'));
	
	$settings_page->add(new admin_setting_configtext('filter_generico/templatecount', 
				get_string('templatecount', 'filter_generico'),
				get_string('templatecount_desc', 'filter_generico'), 
				 FILTER_GENERICO_TEMPLATE_COUNT, PARAM_INT,20));

	//add page to category
	$ADMIN->add('filter_generico_category', $settings_page);

				 
	//Add the template pages
	if($conf && property_exists($conf,'templatecount')){
		$templatecount = $conf->templatecount;
	}else{
		$templatecount = FILTER_GENERICO_TEMPLATE_COUNT;
	}
	for($tindex=1;$tindex<=$templatecount;$tindex++){
		 
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

		//template instructions
		$settings_page->add(new admin_setting_configtextarea('filter_generico/templateinstructions_' . $tindex,
			get_string('templateinstructions', 'filter_generico',$tindex),
			get_string('templateinstructions_desc', 'filter_generico'),
			'',PARAM_RAW));
		
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
				 
		//template requiredjs_shim
		$defvalue= '';
		 $settings_page->add(new admin_setting_configtext('filter_generico/templaterequire_js_shim_' . $tindex , 
				get_string('templaterequirejsshim', 'filter_generico',$tindex),
				get_string('templaterequirejsshim_desc', 'filter_generico'), 
				$defvalue, PARAM_RAW));
				 
		//template amd
		$yesno = array('0'=>get_string('no'),'1'=>get_string('yes'));
		 $settings_page->add(new admin_setting_configselect('filter_generico/template_amd_' . $tindex,
				get_string('templaterequire_amd', 'filter_generico',$tindex),
				get_string('templaterequire_amd_desc', 'filter_generico'), 
				 1,$yesno));
		
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
		
		//template uploadjs_shim
		$defvalue= '';
		 $settings_page->add(new admin_setting_configtext('filter_generico/uploadjs_shim_' . $tindex , 
				get_string('templateuploadjsshim', 'filter_generico',$tindex),
				get_string('templateuploadjsshim_desc', 'filter_generico'), 
				$defvalue, PARAM_RAW));
				 
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

		//dataset
		$settings_page->add(new admin_setting_configtextarea('filter_generico/dataset_' . $tindex,
			get_string('dataset', 'filter_generico',$tindex),
			get_string('dataset_desc', 'filter_generico'),
			'',PARAM_RAW));

		//dataset vars
		$settings_page->add(new admin_setting_configtext('filter_generico/datasetvars_' . $tindex ,
			get_string('datasetvars', 'filter_generico',$tindex),
			get_string('datasetvars_desc', 'filter_generico'),
			'', PARAM_RAW,50));
			
		//alternative content
		 $settings_page->add(new admin_setting_configtextarea('filter_generico/templatealternate_' . $tindex,
					get_string('templatealternate', 'filter_generico',$tindex),
					get_string('templatealternate_desc', 'filter_generico'),
					'',PARAM_RAW));
					
		//alternative content end
		 $settings_page->add(new admin_setting_configtextarea('filter_generico/templatealternate_end_' . $tindex,
					get_string('templatealternate_end', 'filter_generico',$tindex),
					get_string('templatealternate_end_desc', 'filter_generico'),
					'',PARAM_RAW));
 
		//add page to category
		$ADMIN->add('filter_generico_category', $settings_page);
		//$settings->add($settings_page);
	}
	
}
