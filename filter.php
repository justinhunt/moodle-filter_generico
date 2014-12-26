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


function filter_generico_fetch_filter_properties($filterstring){
	//this just removes the {GENERICO: .. } 
	$rawproperties = explode ("{GENERICO:", $filterstring);
	$rawproperties = $rawproperties[1];
	$rawproperties = explode ("}", $rawproperties);	
	$rawproperties = $rawproperties[0];

	//Now we just have our properties string
	//Lets run our regular expression over them
	//string should be property=value,property=value
	//got this regexp from http://stackoverflow.com/questions/168171/regular-expression-for-parsing-name-value-pairs
	$regexpression='/([^=,]*)=("[^"]*"|[^,"]*)/';
	$matches; 	

	//here we match the filter string and split into name array (matches[1]) and value array (matches[2])
	//we then add those to a name value array.
	$itemprops = array();
	if (preg_match_all($regexpression, $rawproperties,$matches,PREG_PATTERN_ORDER)){		
		$propscount = count($matches[1]);
		for ($cnt =0; $cnt < $propscount; $cnt++){
			// echo $matches[1][$cnt] . "=" . $matches[2][$cnt] . " ";
			$newvalue = $matches[2][$cnt];
			//this could be done better, I am sure. WE are removing the quotes from start and end
			//this wil however remove multiple quotes id they exist at start and end. NG really
			$newvalue = trim($newvalue,'"');
			$itemprops[$matches[1][$cnt]]=$newvalue;
		}
	}
	return $itemprops;
}

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
	for($tempindex=1;$tempindex<11;$tempindex++){
			if($filterprops['type']==$conf['templatekey_' . $tempindex]){
				break;
			}
	}
	//no key could be found if got all the way to 11
	if($tempindex==11){return '';}
	
	//fetch our template
	$genericotemplate = $conf['template_' . $tempindex];
	
	//replace the specified names with spec values
	foreach($filterprops as $name=>$value){
		$genericotemplate = str_replace('@@' . $name .'@@',$value,$genericotemplate);
	}
	
	//fetch defaults for this template
	$defaults = $conf['templatedefaults_'. $tempindex];
	if(!empty($defaults)){
		$defaults = "{GENERICO:" . $defaults . "}";
		$defaultprops=filter_generico_fetch_filter_properties($defaults);
		//replace our defaults
		if(!empty($defaultprops)){
			foreach($defaultprops as $name=>$value){
				$genericotemplate = str_replace('@@' . $name .'@@',strip_tags($value),$genericotemplate);
			}
		}
	}
	
	//If we have autoid lets deal with that
	$autoid = time() . (string)rand(100,32767) ;
	$genericotemplate = str_replace('@@AUTOID@@',$autoid,$genericotemplate);
	
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
			}
		}
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
		//moodle jquery
		//$PAGE->requires->jquery();
		
		//use this for external JQUery
		$PAGE->requires->js(new moodle_url($scheme . '//code.jquery.com/jquery-latest.js'));
	}
	
	//massage the js URLdepending on schemes and rel. links etc. Then insert it
	if($require_js){
		if(strpos($require_js,'//')===0){
			$require_js = $scheme . $require_js;
		}elseif(strpos($require_js,'/')===0){
			$require_js = $CFG->wwwroot . $require_js;
		}
		$PAGE->requires->js(new moodle_url($require_js));
	}
	
	//set up property array for passing to JS
	$proparray=Array();
	
	//massage the CSS URL depending on schemes and rel. links etc. 
	if(strpos($require_css,'//')===0){
		$require_css = $scheme . $require_css;
	}elseif(strpos($require_css,'/')===0){
		$require_css = $CFG->wwwroot . $require_css;
	}
	
	//if not too late: load css in header
	// if too late: inject it there via JS
	$proparray['CSSLINK']=false;
	if($require_css && !$PAGE->headerprinted && !$PAGE->requires->is_head_done()){
		$PAGE->requires->css( new moodle_url($require_css));
	}else{
		$proparray['CSSLINK']=$require_css;
	}
	
	
	//Set up our javascript variables
	$proparray['TEMPLATEID'] = $tempindex;
	$proparray['AUTOID'] = $autoid;

		
	$jsmodule = array(
			'name'     => 'filter_generico',
			'fullpath' => '/filter/generico/module.js',
			'requires' => array()
		);
		
	//setup our JS call
	$PAGE->requires->js_init_call('M.filter_generico.loadgenerico', array($proparray),false,$jsmodule);

	
	//finally return our template text	
	return $genericotemplate;
}