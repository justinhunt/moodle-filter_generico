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
 * Returns the JS for a specified template
 * Its php but looks to browser like js file, cos that is what it returns.
 *
 * @package    filter_generico
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$tindex = required_param('t',PARAM_TEXT);

$conf = get_config('filter_generico');
$template=$conf->{'template_' . $tindex};

//get presets
$thescript=$conf->{'templatescript_' . $tindex};
$defaults=$conf->{'templatedefaults_' . $tindex};

//we no longer do this. We used the actual used variables
////merge defaults with blank proparray  to get all fields
//$defaultsarray = filter_generico_fetch_filter_properties('{GENERICO:' . $defaults);
//$proparray=array_merge(filter_generico_fetch_emptyproparray(), $defaultsarray);

//fetch all the variables we use (make sure we have no duplicates)
$allvariables = filter_generico_fetch_variables($thescript. $template);
$uniquevariables = array_unique($allvariables);

//these props are in the opts array in the allopts[] array on the page
//since we are writing the JS we write the opts['name'] into the js, but 
//have to remove quotes from template eg "@@VAR@@" => opts['var'] //NB no quotes.
//thats worth knowing for the admin who writed the JS load code for the template.
foreach($uniquevariables as $propname){
	//case: single quotes
	$thescript = str_replace("'@@" . $propname ."@@'",'opts["' . $propname . '"]',$thescript);
	//case: double quotes
	$thescript = str_replace('"@@' . $propname .'@@"',"opts['" . $propname . "']",$thescript);
	//case: no quotes
	$thescript = str_replace('@@' . $propname .'@@',"opts['" . $propname . "']",$thescript);
}

$thefunction = "if(typeof filter_generico_extfunctions == 'undefined'){filter_generico_extfunctions={};}";
$thefunction .= "filter_generico_extfunctions['" . $tindex . "']= function(opts) {" . $thescript. "};";
header('Content-Type: application/javascript');
echo $thefunction;
