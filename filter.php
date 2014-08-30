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
			$itemprops[$matches[1][$cnt]]=$matches[2][$cnt];
		}
	}
	return $itemprops;
}

/*
*	Callback function , exists outside of class definition(because its a callback ...)
*
*/
function filter_generico_callback(array $link){
	global $CFG, $COURSE, $USER;
	
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
	
	return $genericotemplate;
}