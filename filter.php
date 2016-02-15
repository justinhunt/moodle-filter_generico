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
 * Filter for expanding Generico templates 
 *
 * @package    filter
 * @subpackage generico
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

class filter_generico extends moodle_text_filter {

    /**
     * Apply the filter to the text
     *
     * @see filter_manager::apply_filter_chain()
     * @param string $text to be processed by the text
     * @param array $options filter options
     * @return string text after processing
     */
    public function filter($text, array $options = array()) {
		//if we don't even have our tag, just bail out
		if(strpos($text,'{GENERICO:')===false){
			return $text;
		}
	
         $search = '/{GENERICO:.*?}/is';
		 if (!is_string($text)) {
				// non string data can not be filtered anyway
				return $text;
		}
		$newtext=$text;

		$newtext = preg_replace_callback($search, 'filter_generico_callback', $newtext);
		
		if (is_null($newtext) or $newtext === $text) {
			// error or not filtered
			return $text;
		}

		return $newtext;
    }
   
}//end of class


/*
*	Callback function , exists outside of class definition(because its a callback ...)
*
*/
function filter_generico_callback(array $link){
	global $CFG, $COURSE, $USER, $PAGE, $DB;
	
	 $conf = get_object_vars(get_config('filter_generico'));
	
	//get our filter props
	$filterprops=filter_generico_fetch_filter_properties($link[0]);
	
	//if we have no props, quit
	if(empty($filterprops)){return "";}
	
	//if we want to ignore the filter (for "how to use generico" or "cut and paste" this style use) we let it go
	//to use this, make the last parameter of the filter passthrough=1
	if (!empty($filterprops['passthrough'])) return str_replace( ",passthrough=1","",$link[0]);
	
	//determine which template we are using
	$endtag=false;
	for($tempindex=1;$tempindex<=$conf['templatecount'];$tempindex++){
			if($filterprops['type']==$conf['templatekey_' . $tempindex]){
				break;
			}elseif($filterprops['type']==$conf['templatekey_' . $tempindex] . '_end'){
				$endtag = true;
				break;
			}
	}
	//no key could be found if got all the way to 21
	if($tempindex==$conf['templatecount']+1){return '';}
	
	//fetch our template
	if($endtag){
		$genericotemplate = $conf['templateend_' . $tempindex];
	}else{
		$genericotemplate = $conf['template_' . $tempindex];
	}

	//fetch dataset info
	$dataset_body = $conf['dataset_' . $tempindex];
	$dataset_vars = $conf['datasetvars_' . $tempindex];
	
	//replace the specified names with spec values
	foreach($filterprops as $name=>$value){
		$genericotemplate = str_replace('@@' . $name .'@@',$value,$genericotemplate);
		$dataset_vars  = str_replace('@@' . $name .'@@',$value,$dataset_vars);
	}
	
	//fetch defaults for this template
	$defaults = $conf['templatedefaults_'. $tempindex];
	if(!empty($defaults)){
		$defaults = "{GENERICO:" . $defaults . "}";
		$defaultprops=filter_generico_fetch_filter_properties($defaults);
		//replace our defaults, if not spec in the the filter string
		if(!empty($defaultprops)){
			foreach($defaultprops as $name=>$value){
				if(!array_key_exists($name,$filterprops)){
					//if we have options as defaults, lets just take the first one
					if(strpos($value,'|')!==false){
						$value=explode('|',$value)[0];
					}
					$genericotemplate = str_replace('@@' . $name .'@@',strip_tags($value),$genericotemplate);
					$dataset_vars  = str_replace('@@' . $name .'@@',strip_tags($value),$dataset_vars);
					//stash for using in JS later
					$filterprops[$name]=$value;
				}
			}
		}
	}
	
	//If we have autoid lets deal with that
	$autoid = 'fg_' . time() . (string)rand(100,32767) ;
	$genericotemplate = str_replace('@@AUTOID@@',$autoid,$genericotemplate);
	//stash this for passing to js
	$filterprops['AUTOID']=$autoid;

	//If template requires a MOODLEPAGEID lets give them one
	//this is a bit of a special case.
	$moodlepageid = optional_param('id',0,PARAM_INT);
	$genericotemplate = str_replace('@@MOODLEPAGEID@@',$moodlepageid,$genericotemplate);
	$dataset_vars  = str_replace('@@MOODLEPAGEID@@',$moodlepageid,$dataset_vars);
	//stash this for passing to js
	$filterprops['MOODLEPAGEID']=$moodlepageid;

	
	
	//if we have course variables e.g @@COURSE:ID@@
	if(strpos($genericotemplate . ' ' . $dataset_vars ,'@@COURSE:')!==false){
			$coursevars = get_object_vars($COURSE);
			$coursepropstubs = explode('@@COURSE:',$genericotemplate);
		    $d_stubs = explode('@@COURSE:',$dataset_vars);
		    if($d_stubs){
			 	$coursepropstubs = array_merge($coursepropstubs,$d_stubs);
		    }


		//Course Props
			$profileprops=false;
			$count=0;
			foreach($coursepropstubs as $propstub){
				//we don't want the first one, its junk
				$count++;
				if($count==1){continue;}
				//init our prop value
				$propvalue=false;
				
				//fetch the property name
				//user can use any case, but we work with lower case version
				$end = strpos($propstub,'@@');
				$courseprop_allcase = substr($propstub,0,$end);
				$courseprop=strtolower($courseprop_allcase);
				
				//check if it exists in course
				if(array_key_exists($courseprop,$coursevars)){
					$propvalue=$coursevars[$courseprop];
				}elseif($courseprop=='contextid'){
					$context = context_course::instance($COURSE->id);
					if($context){
						$propvalue=$context->id;
					}
				}
				//if we have a propname and a propvalue, do the replace
				if(!empty($courseprop) && !empty($propvalue)){
					$genericotemplate = str_replace('@@COURSE:' . $courseprop_allcase .'@@',$propvalue,$genericotemplate);
					$dataset_vars  = str_replace('@@COURSE:' . $courseprop_allcase .'@@',$propvalue,$dataset_vars);
					//stash this for passing to js
					$filterprops['COURSE:' . $courseprop_allcase]=$propvalue;
				}
			}
	}//end of if @@COURSE

	//if we have user variables e.g @@USER:FIRSTNAME@@
	//It is a bit wordy, because trying to avoid loading a lib
	//or making a DB call if unneccessary
	if(strpos($genericotemplate . ' ' . $dataset_vars ,'@@USER:')!==false){
		$uservars = get_object_vars($USER);
		$userpropstubs = explode('@@USER:',$genericotemplate);
		$d_stubs = explode('@@USER:',$dataset_vars);
		if($d_stubs){
			$userpropstubs = array_merge($userpropstubs,$d_stubs);
		}
		
		//User Props
		$profileprops=false;
		$count=0;
		foreach($userpropstubs as $propstub){
			//we don't want the first one, its junk
			$count++;
			if($count==1){continue;}
			//init our prop value
			$propvalue=false;
			
			//fetch the property name
			//user can use any case, but we work with lower case version
			$end = strpos($propstub,'@@');
			$userprop_allcase = substr($propstub,0,$end);
			$userprop=strtolower($userprop_allcase);
			
			//check if it exists in user, else look for it in profile fields
			if(array_key_exists($userprop,$uservars)){
				$propvalue=$uservars[$userprop];
			}else{
				if(!$profileprops){
					require_once("$CFG->dirroot/user/profile/lib.php");
					$profileprops = get_object_vars(profile_user_record($USER->id));
				}
				if($profileprops && array_key_exists($userprop,$profileprops)){
					$propvalue=$profileprops[$userprop];
				}else{
					switch($userprop){
						case 'picurl':
							require_once("$CFG->libdir/outputcomponents.php");
							global $PAGE;
							$user_picture=new user_picture($USER);
							$propvalue = $user_picture->get_url($PAGE);
							break;
							
						case 'pic':
							global $OUTPUT;
							$propvalue = $OUTPUT->user_picture($USER, array('popup'=>true));
							break;
					}
				}
			}
			
			//if we have a propname and a propvalue, do the replace
			if(!empty($userprop) && !empty($propvalue)){
				//echo "userprop:" . $userprop . '<br/>propvalue:' . $propvalue;
				$genericotemplate = str_replace('@@USER:' . $userprop_allcase .'@@',$propvalue,$genericotemplate);
				$dataset_vars  = str_replace('@@USER:' . $userprop_allcase .'@@',$propvalue,$dataset_vars);
				//stash this for passing to js
				$filterprops['USER:' . $userprop_allcase]=$propvalue;
			}
		}
	}//end of of we @@USER

	//if we have a dataset body
	//we split the $data_vars string passed in by user (which should have had all the replacing done)
	//into the vars array. This is passed to get_records_sql and the returned result is stored
	//in filter props. If its a single record, its available to the body area.
	//otherwise it needs to be accessewd from javascript in the DATASET variable
	$filterprops['DATASET']=false;
	if($dataset_body){
		$vars = array();
		if($dataset_vars){
			$vars=explode(',',$dataset_vars);
		}
		try {
			$alldata = $DB->get_records_sql($dataset_body, $vars);
			if($alldata) {
				$filterprops['DATASET'] = $alldata;
				//replace the specified names with spec values, if its a one element array
				if (sizeof($filterprops['DATASET']) == 1) {
					$thedata = get_object_vars(array_pop($alldata));
					foreach ($thedata as $name => $value) {
						$genericotemplate = str_replace('@@DATASET:' . $name . '@@', $value, $genericotemplate);
					}
				}
			}
		}catch(Exception $e){
			//do nothing;
		}
	}//end of if dataset
	
	//If this is the end tag we don't need to subsequent CSS and JS stuff. We already did it.
	if($endtag){
		return $genericotemplate;
	}
	
	//get the conf info we need for this template
	$thescript = $conf['templatescript_' . $tempindex];
	$defaults=$conf['templatedefaults_' . $tempindex];
	$require_js = $conf['templaterequire_js_' . $tempindex];
	$require_css = $conf['templaterequire_css_' . $tempindex];
	$require_jquery = $conf['templaterequire_jquery_' . $tempindex];
	//are we AMD and Moodle 2.9 or more?
	$require_amd = $conf['template_amd_' . $tempindex] && $CFG->version>=2015051100;
	
	//figure out if this is https or http. We don't want to scare the browser
	if(strpos($PAGE->url->out(),'https:')===0){
		$scheme='https:';
	}else{
		$scheme='http:';
	}
	
	//load jquery
	//We ALWAYS load jquery using require js  so this can be deleted soon I hopw
	if($require_jquery && !$require_amd){
		//we don't use moodle jquery. To keep things consistent, though the user could point jqueryurl to moodle's one
		//if(!$PAGE->headerprinted && !$PAGE->requires->is_head_done()){
		if(false){
			$PAGE->requires->jquery();
		}else{
			//use this for external JQuery
			$PAGE->requires->js(new moodle_url($scheme . $conf['jqueryurl']));
		}
	}
	
	//massage the js URL depending on schemes and rel. links etc. Then insert it
	//with AMD we set these as dependencies, so we don't need this song and dance
	if(!$require_amd){
		$filterprops['JSLINK']=false;
		if($require_js){
			if(strpos($require_js,'//')===0){
				$require_js = $scheme . $require_js;
			}elseif(strpos($require_js,'/')===0){
				$require_js = $CFG->wwwroot . $require_js;
			}
			
			//for load method: NO AMD
			$PAGE->requires->js(new moodle_url($require_js));
		
			//for load method: AMD
			//$require_js = substr($require_js, 0, -3);
			$filterprops['JSLINK'] = $require_js;
		}
	
		//if we have an uploaded JS file, then lets include that
		$filterprops['JSUPLOAD']=false;
		$uploadjsfile = $conf['uploadjs' . $tempindex];
		if($uploadjsfile){
			$uploadjsurl = filter_generico_setting_file_url($uploadjsfile,'uploadjs' . $tempindex);
			
			//for load method: NO AMD
			$PAGE->requires->js($uploadjsurl);
			
			//for load method: AMD
			//$uploadjsurl = substr($uploadjsurl, 0, -3);
			$filterprops['JSUPLOAD'] = $uploadjsurl;
		}
	}

	//massage the CSS URL depending on schemes and rel. links etc. 
	if(!empty($require_css)){
		if(strpos($require_css,'//')===0){
			$require_css = $scheme . $require_css;
		}elseif(strpos($require_css,'/')===0){
			$require_css = $CFG->wwwroot . $require_css;
		}
	}
	
	//if we have an uploaded CSS file, then lets include that
	$uploadcssfile = $conf['uploadcss' . $tempindex];
	if($uploadcssfile){
		$uploadcssurl = filter_generico_setting_file_url($uploadcssfile,'uploadcss' . $tempindex);
	}
	
	//if not too late: load css in header
	// if too late: inject it there via JS
	$filterprops['CSSLINK']=false;
	$filterprops['CSSUPLOAD']=false;
	$filterprops['CSSCUSTOM']=false;
	
	//require any scripts from the template
	$customcssurl=false;
	if($conf['templatestyle_' . $tempindex]){
		$customcssurl =new moodle_url( '/filter/generico/genericocss.php?t=' . $tempindex);

	}
	
	if(!$PAGE->headerprinted && !$PAGE->requires->is_head_done()){
		if($require_css){
			$PAGE->requires->css( new moodle_url($require_css));
		}
		if($uploadcssfile){
			$PAGE->requires->css($uploadcssurl);
		}
		if($customcssurl){
			$PAGE->requires->css($customcssurl);
		}
	}else{
		if($require_css){
			$filterprops['CSSLINK']=$require_css;
		}
		if($uploadcssfile){
			$filterprops['CSSUPLOAD']=$uploadcssurl->out();
		}
		if($customcssurl){
			$filterprops['CSSCUSTOM']=$customcssurl->out();
		}
		
	}
	
	
	//Tell javascript which template this is
	$filterprops['TEMPLATEID'] = $tempindex;

		
	$jsmodule = array(
			'name'     => 'filter_generico',
			'fullpath' => '/filter/generico/module.js',
			'requires' => array('json')
		);
		

	//AMD or not, and then load our js for this template on the page
	if($require_amd){

		$generator = new filter_generico_template_script_generator($tempindex);
		$template_amd_script = $generator->get_template_script();

		//props can't be passed at much length , Moodle complains about too many
		//so we do this ... lets hope it don't break things
		$jsonstring = json_encode($filterprops);
		$props_html = \html_writer::tag('input', '', array('id' => 'filter_generico_amdopts_' . $filterprops['AUTOID'], 'type' => 'hidden', 'value' => $jsonstring));
		$genericotemplate = $props_html . $genericotemplate;
		
		//load define for this template. Later it will be called from loadgenerico
		$PAGE->requires->js_amd_inline($template_amd_script);
		//for AMD generico script
		$PAGE->requires->js_call_amd('filter_generico/generico_amd','loadgenerico', array(array('AUTOID'=>$filterprops['AUTOID'])));


	}else{

		//require any scripts from the template
		$PAGE->requires->js('/filter/generico/genericojs.php?t=' . $tempindex);	
	
		//for no AMD
		$PAGE->requires->js_init_call('M.filter_generico.loadgenerico', array($filterprops),false,$jsmodule);
	}
	
	//finally return our template text	
	return $genericotemplate;
}
