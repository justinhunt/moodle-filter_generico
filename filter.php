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
	global $CFG, $COURSE, $USER, $PAGE;
	
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
	for($tempindex=1;$tempindex<=20;$tempindex++){
			if($filterprops['type']==$conf['templatekey_' . $tempindex]){
				break;
			}elseif($filterprops['type']==$conf['templatekey_' . $tempindex] . '_end'){
				$endtag = true;
				break;
			}
	}
	//no key could be found if got all the way to 21
	if($tempindex==21){return '';}
	
	//fetch our template
	if($endtag){
		$genericotemplate = $conf['templateend_' . $tempindex];
	}else{
		$genericotemplate = $conf['template_' . $tempindex];
	}
	
	//replace the specified names with spec values
	foreach($filterprops as $name=>$value){
		$genericotemplate = str_replace('@@' . $name .'@@',$value,$genericotemplate);
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
					$genericotemplate = str_replace('@@' . $name .'@@',strip_tags($value),$genericotemplate);
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
	
	//if we have user variables e.g @@USER:FIRSTNAME@@
	//It is a bit wordy, because trying to avoid loading a lib
	//or making a DB call if unneccessary
	if(strpos($genericotemplate,'@@USER:')!==false){
		$uservars = get_object_vars($USER);
		$propstubs = explode('@@USER:',$genericotemplate);
		$profileprops=false;
		$count=0;
		foreach($propstubs as $propstub){
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
				//stash this for passing to js
				$filterprops['USER:' . $userprop_allcase]=$propvalue;
			}
		}
	}
	
	//If this is the end tag we don't need to subseuqent CSS and JS stuff. We already did it.
	if($endtag){
		return $genericotemplate;
	}
	
	//figure out if we require jquery or external CSS/JS/
	$require_js = $conf['templaterequire_js_' . $tempindex];
	$require_css = $conf['templaterequire_css_' . $tempindex];
	$require_jquery = $conf['templaterequire_jquery_' . $tempindex];
	
	//figure out if this is https or http. We don't want to scare the browser
	if(strpos($PAGE->url->out(),'https:')===0){
		$scheme='https:';
	}else{
		$scheme='http:';
	}
	
	//load jquery
	if($require_jquery){
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
	if($require_js){
		if(strpos($require_js,'//')===0){
			$require_js = $scheme . $require_js;
		}elseif(strpos($require_js,'/')===0){
			$require_js = $CFG->wwwroot . $require_js;
		}
		$PAGE->requires->js(new moodle_url($require_js));
	}
	
	//if we have an uploaded JS file, then lets include that
	$uploadjsfile = $conf['uploadjs' . $tempindex];
	if($uploadjsfile){
		$uploadjsurl = filter_generico_setting_file_url($uploadjsfile,'uploadjs' . $tempindex);
		$PAGE->requires->js($uploadjsurl);
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
		
	//require any scripts from the template
	$PAGE->requires->js('/filter/generico/genericojs.php?t=' . $tempindex);	
		
	//setup our JS call
	$PAGE->requires->js_init_call('M.filter_generico.loadgenerico', array($filterprops),false,$jsmodule);
	
	//finally return our template text	
	return $genericotemplate;
}