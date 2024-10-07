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

namespace filter_generico;

/**
 * Filter for expanding Generico templates
 *
 * @package    filter
 * @subpackage generico
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (class_exists('\core_filters\text_filter')) {
    class_alias('\core_filters\text_filter', 'generico_base_text_filter');
} else {
    class_alias('\moodle_text_filter', 'generico_base_text_filter');
}


class text_filter extends \generico_base_text_filter {

    /**
     * Apply the filter to the text
     *
     * @see filter_manager::apply_filter_chain()
     * @param string $text to be processed by the text
     * @param array $options filter options
     * @return string text after processing
     */
    public function filter($text, array $options = []) {
        // if we don't even have our tag, just bail out
        if (strpos($text, '{GENERICO:') === false) {
            return $text;
        }

        $search = '/{GENERICO:.*?}/is';
        if (!is_string($text)) {
            // non string data can not be filtered anyway
            return $text;
        }
        $newtext = $text;

        $newtext = preg_replace_callback($search, [$this, 'filter_generico_callback'], $newtext);

        if (is_null($newtext) or $newtext === $text) {
            // error or not filtered
            return $text;
        }

        return $newtext;
    }



    /*
    *    Callback function 
    *
    */
    private function filter_generico_callback(array $link) {
        global $CFG, $COURSE, $USER, $PAGE, $DB;

        $conf = get_object_vars(get_config('filter_generico'));
        $context = false;// we get this if when we need it

        // get our filter props
        $filterprops = \filter_generico\generico_utils::fetch_filter_properties($link[0]);

        // if we have no props, quit
        if (empty($filterprops)) {
            return "";
        }

        // we use this to see if its a web service calling this,
        // in which case we return the alternate content
        $climode = defined('CLI_SCRIPT') && CLI_SCRIPT;
        $iswebservice = false;
        if (!$climode) {
            // we get a warning here if the PAGE url is not set. But its not dangerous. just annoying.,
            $iswebservice = strpos($PAGE->url, $CFG->wwwroot . '/webservice/') === 0;
        }

        // if we want to ignore the filter (for "how to use generico" or "cut and paste" this style use) we let it go
        // to use this, make the last parameter of the filter passthrough=1
        if (!empty($filterprops['passthrough'])) {
            return str_replace(",passthrough=1", "", $link[0]);
        }

        // Perform role/permissions check on this filter
        if (!empty($filterprops['viewcapability'])) {
            if (!$context) {
                $context = \context_course::instance($COURSE->id);
            }
            if (!has_capability($filterprops['viewcapability'], $context)) {
                return '';
            }
        }
        if (!empty($filterprops['hidecapability'])) {
            if (!$context) {
                $context = \context_course::instance($COURSE->id);
            }
            if (has_capability($filterprops['hidecapability'], $context)) {
                return '';
            }
        }

        // determine which template we are using
        $endtag = false;
        for ($tempindex = 1; $tempindex <= $conf['templatecount']; $tempindex++) {
            if ($filterprops['type'] == $conf['templatekey_' . $tempindex]) {
                break;
            } else if ($filterprops['type'] == $conf['templatekey_' . $tempindex] . '_end') {
                $endtag = true;
                break;
            }
        }
        // no key could be found if got all the way to the last template
        if ($tempindex == $conf['templatecount'] + 1) {
            return '';
        }

        // fetch our template
        if ($endtag) {
            $genericotemplate = $conf['templateend_' . $tempindex];
            // fetch alternate content (for use when no css or js available ala mobile app.)
            $alternatecontent = $conf['templatealternate_end_' . $tempindex];
        } else {
            $genericotemplate = $conf['template_' . $tempindex];
            // fetch alternate content (for use when no css or js available ala mobile app.)
            $alternatecontent = $conf['templatealternate_' . $tempindex];
        }

        // fetch dataset info
        $datasetbody = $conf['dataset_' . $tempindex];
        $datasetvars = $conf['datasetvars_' . $tempindex];

        // js custom script
        // we really just want to be sure anything that appears in custom script
        // is stored in $filterprops and passed to js. we dont replace it server side because
        // of caching
        $jscustomscript = $conf['templatescript_' . $tempindex];

        // replace the specified names with spec values
        foreach ($filterprops as $name => $value) {
            $genericotemplate = str_replace('@@' . $name . '@@', $value, $genericotemplate);
            $datasetvars = str_replace('@@' . $name . '@@', $value, $datasetvars);
            $alternatecontent = str_replace('@@' . $name . '@@', $value, $alternatecontent);
        }

        // fetch defaults for this template
        $defaults = $conf['templatedefaults_' . $tempindex];
        if (!empty($defaults)) {
            $defaults = "{GENERICO:" . $defaults . "}";
            $defaultprops = \filter_generico\generico_utils::fetch_filter_properties($defaults);
            // replace our defaults, if not spec in the the filter string
            if (!empty($defaultprops)) {
                foreach ($defaultprops as $name => $value) {
                    if (!array_key_exists($name, $filterprops)) {
                        // if we have options as defaults, lets just take the first one
                        if (strpos($value, '|') !== false) {
                            $value = explode('|', $value)[0];
                        }
                        $genericotemplate = str_replace('@@' . $name . '@@', strip_tags($value), $genericotemplate);
                        $datasetvars = str_replace('@@' . $name . '@@', strip_tags($value), $datasetvars);
                        $alternatecontent = str_replace('@@' . $name . '@@', strip_tags($value), $alternatecontent);

                        // stash for using in JS later
                        $filterprops[$name] = $value;
                    }
                }
            }
        }

        // If we have autoid lets deal with that
        $autoid = 'fg_' . time() . (string) rand(100, 32767);
        $genericotemplate = str_replace('@@AUTOID@@', $autoid, $genericotemplate);
        $alternatecontent = str_replace('@@AUTOID@@', $autoid, $alternatecontent);
        // stash this for passing to js
        $filterprops['AUTOID'] = $autoid;

        // If we need a Cloud Poodll token, lets fetch it
        if (strpos($genericotemplate, '@@CLOUDPOODLLTOKEN@@') &&
            !empty($conf['cpapiuser']) &&
            !empty($conf['cpapisecret'])) {
            $token = \filter_generico\generico_utils::fetch_token($conf['cpapiuser'], $conf['cpapisecret']);
            if ($token) {
                $genericotemplate = str_replace('@@CLOUDPOODLLTOKEN@@', $token, $genericotemplate);
                // stash this for passing to js
                $filterprops['CLOUDPOODLLTOKEN'] = $token;
            } else {
                $genericotemplate = str_replace('@@CLOUDPOODLLTOKEN@@', 'INVALID TOKEN', $genericotemplate);
                // stash this for passing to js
                $filterprops['CLOUDPOODLLTOKEN'] = 'INVALID TOKEN';
            }
        }

        // If this is a renderer call, lets do it
        // it will be a function in a renderer with a name that begins with "embed_" .. e.g "embed_something"
        // the args filterprops will be a pipe delimited string of args, eg {POODLL:type="mod_ogte",function="embed_table",args="arg1|arg2|arg3"}
        // if the args string contains "cloudpoodlltoken" it will be replaced with the actual cloud poodll token.
        if(isset($filterprops['renderer']) && isset($filterprops['function']) && strpos($filterprops['function'], 'embed_') === 0){
            try {
                if (!isset($token)) {
                    $token = false;
                }
                $somerenderer = $PAGE->get_renderer($filterprops['renderer']);
                $args = [];
                if (isset($filterprops['args'])) {
                    $argsstring = str_replace('cloudpoodlltoken', $token, $filterprops['args']);
                    $argsarray = explode('|', $argsstring);
                }
                $renderedcontent = call_user_func_array([$somerenderer, $filterprops['function']], $argsarray);
                $genericotemplate = str_replace('@@renderedcontent@@', $renderedcontent, $genericotemplate);
            } catch (Exception $e) {
                $genericotemplate = str_replace('@@renderedcontent@@', 'Failed to render!!', $genericotemplate);
            }
        }

        // If template requires a MOODLEPAGEID lets give them one
        // this is legacy really. Now we have @@URLPARAM we could do it that way
        $moodlepageid = optional_param('id', 0, PARAM_INT);
        $genericotemplate = str_replace('@@MOODLEPAGEID@@', $moodlepageid, $genericotemplate);
        $datasetvars = str_replace('@@MOODLEPAGEID@@', $moodlepageid, $datasetvars);
        $alternatecontent = str_replace('@@MOODLEPAGEID@@', $moodlepageid, $alternatecontent);

        // stash this for passing to js
        $filterprops['MOODLEPAGEID'] = $moodlepageid;

        // if we have urlparam variables e.g @@URLPARAM:id@@
        if (strpos($genericotemplate . ' ' . $datasetvars . ' '
                    . $alternatecontent . ' ' . $jscustomscript, '@@URLPARAM:') !== false) {
            $urlparamstubs = explode('@@URLPARAM:', $genericotemplate);

            $dvstubs = explode('@@URLPARAM:', $datasetvars);
            if ($dvstubs) {
                $urlparamstubs = array_merge($urlparamstubs, $dvstubs);
            }

            $jsstubs = explode('@@URLPARAM:', $jscustomscript);
            if ($jsstubs) {
                $urlparamstubs = array_merge($urlparamstubs, $jsstubs);
            }

            $altstubs = explode('@@URLPARAM:', $alternatecontent);
            if ($altstubs) {
                $urlparamstubs = array_merge($urlparamstubs, $altstubs);
            }

            // URL Param Props
            $count = 0;
            foreach ($urlparamstubs as $propstub) {
                // we don't want the first one, its junk
                $count++;
                if ($count == 1) {
                    continue;
                }
                // init our prop value
                $propvalue = false;

                // fetch the property name
                // user can use any case, but we work with lower case version
                $end = strpos($propstub, '@@');
                $urlprop = substr($propstub, 0, $end);
                if (empty($urlprop)) {
                    continue;
                }

                // check if it exists in the params to the url and if so, set it.
                $propvalue = optional_param($urlprop, '', PARAM_TEXT);
                $genericotemplate = str_replace('@@URLPARAM:' . $urlprop . '@@', $propvalue, $genericotemplate);
                $datasetvars = str_replace('@@URLPARAM:' . $urlprop . '@@', $propvalue, $datasetvars);
                $alternatecontent = str_replace('@@URLPARAM:' . $urlprop . '@@', $propvalue, $alternatecontent);

                // stash this for passing to js
                $filterprops['URLPARAM:' . $urlprop] = $propvalue;
            }//end of for each
        }//end of if we have@@URLPARAM

        // we should stash our wwwroot too
        $genericotemplate = str_replace('@@WWWROOT@@', $CFG->wwwroot, $genericotemplate);
        $datasetvars = str_replace('@@WWWROOT@@', $CFG->wwwroot, $datasetvars);
        $alternatecontent = str_replace('@@WWWROOT@@', $CFG->wwwroot, $alternatecontent);

        // actually this is available from JS anyway M.cfg.wwwroot . But lets make it easy for people
        $filterprops['WWWROOT'] = $CFG->wwwroot;

        // if we have course variables e.g @@COURSE:ID@@
        if (strpos($genericotemplate . ' ' . $datasetvars, '@@COURSE:') !== false) {
            $coursevars = false;
            if(!empty($filterprops['courseid']) && is_numeric($filterprops['courseid'] )) {
                $thecourse = get_course($filterprops['courseid']);
                if($thecourse) {
                    $coursevars = get_object_vars($thecourse);
                }
            }else{
                $coursevars = get_object_vars($COURSE);
                $filterprops['courseid'] = $COURSE->id;
            }
            if($coursevars){
                // custom fields
                if(class_exists('\core_customfield\handler')) {
                    $handler = \core_customfield\handler::get_handler('core_course', 'course');
                    $customfields = $handler->get_instance_data($filterprops['courseid']);
                    foreach ($customfields as $customfield) {
                        if (empty($customfield->get_value())) {
                            continue;
                        }
                        $shortname = $customfield->get_field()->get('shortname');
                        $coursevars[$shortname] = $customfield->get_value();
                    }
                }
            }

            $coursepropstubs = explode('@@COURSE:', $genericotemplate);
            $dstubs = explode('@@COURSE:', $datasetvars);
            if ($dstubs) {
                $coursepropstubs = array_merge($coursepropstubs, $dstubs);
            }
            $jstubs = explode('@@COURSE:', $jscustomscript);
            if ($jstubs) {
                $coursepropstubs = array_merge($coursepropstubs, $jstubs);
            }
            $altstubs = explode('@@COURSE:', $alternatecontent);
            if ($altstubs) {
                $coursepropstubs = array_merge($coursepropstubs, $altstubs);
            }

            // Course Props
            $profileprops = false;
            $count = 0;
            foreach ($coursepropstubs as $propstub) {
                // we don't want the first one, its junk
                $count++;
                if ($count == 1) {
                    continue;
                }
                // init our prop value
                $propvalue = false;

                // fetch the property name
                // user can use any case, but we work with lower case version
                $end = strpos($propstub, '@@');
                $coursepropallcase = substr($propstub, 0, $end);
                $courseprop = strtolower($coursepropallcase);

                // check if it exists in course
                if (array_key_exists($courseprop, $coursevars)) {
                    $propvalue = $coursevars[$courseprop];
                } else if ($courseprop == 'contextid') {
                    if (!$context) {
                        $context = \context_course::instance($COURSE->id);
                    }
                    if ($context) {
                        $propvalue = $context->id;
                    }
                }
                // if we have a propname and a propvalue, do the replace
                if (!empty($courseprop) && !is_null($propvalue)) {
                    $genericotemplate = str_replace('@@COURSE:' . $coursepropallcase . '@@', $propvalue, $genericotemplate);
                    $datasetvars = str_replace('@@COURSE:' . $coursepropallcase . '@@', $propvalue, $datasetvars);
                    $alternatecontent = str_replace('@@COURSE:' . $coursepropallcase . '@@', $propvalue, $alternatecontent);
                    // stash this for passing to js
                    $filterprops['COURSE:' . $coursepropallcase] = $propvalue;
                }
            }
        }//end of if @@COURSE

        // if we have user variables e.g @@USER:FIRSTNAME@@
        // It is a bit wordy, because trying to avoid loading a lib
        // or making a DB call if unneccessary
        if (strpos($genericotemplate . ' ' . $datasetvars . ' ' . $jscustomscript, '@@USER:') !== false) {
            $uservars = get_object_vars($USER);
            $userpropstubs = explode('@@USER:', $genericotemplate);
            $dstubs = explode('@@USER:', $datasetvars);
            if ($dstubs) {
                $userpropstubs = array_merge($userpropstubs, $dstubs);
            }
            $jstubs = explode('@@USER:', $jscustomscript);
            if ($jstubs) {
                $userpropstubs = array_merge($userpropstubs, $jstubs);
            }

            // User Props
            $profileprops = false;
            $count = 0;
            foreach ($userpropstubs as $propstub) {
                // we don't want the first one, its junk
                $count++;
                if ($count == 1) {
                    continue;
                }
                // init our prop value
                $propvalue = false;

                // fetch the property name
                // user can use any case, but we work with lower case version
                $end = strpos($propstub, '@@');
                $userpropallcase = substr($propstub, 0, $end);
                $userprop = strtolower($userpropallcase);

                // check if it exists in user, else look for it in profile fields
                if (array_key_exists($userprop, $uservars)) {
                    $propvalue = $uservars[$userprop];
                } else {
                    if (!$profileprops) {
                        require_once("$CFG->dirroot/user/profile/lib.php");
                        $profileprops = get_object_vars(profile_user_record($USER->id));
                    }
                    if ($profileprops && array_key_exists($userprop, $profileprops)) {
                        $propvalue = $profileprops[$userprop];
                    } else {
                        switch ($userprop) {
                            case 'picurl':
                                require_once("$CFG->libdir/outputcomponents.php");
                                global $PAGE;
                                $userpicture = new \user_picture($USER);
                                $propvalue = $userpicture->get_url($PAGE);
                                break;

                            case 'pic':
                                global $OUTPUT;
                                $propvalue = $OUTPUT->user_picture($USER, ['popup' => true]);
                                break;
                        }
                    }
                }

                // if we have a propname and a propvalue, do the replace
                if (!empty($userprop) && !is_null($propvalue)) {
                    // echo "userprop:" . $userprop . '<br/>propvalue:' . $propvalue;
                    $genericotemplate = str_replace('@@USER:' . $userpropallcase . '@@', $propvalue, $genericotemplate);
                    $datasetvars = str_replace('@@USER:' . $userpropallcase . '@@', $propvalue, $datasetvars);
                    $alternatecontent = str_replace('@@USER:' . $userpropallcase . '@@', $propvalue, $alternatecontent);
                    // stash this for passing to js
                    $filterprops['USER:' . $userpropallcase] = $propvalue;
                }
            }
        }//end of of we @@USER

        // if we have a dataset body
        // we split the $data_vars string passed in by user (which should have had all the replacing done)
        // into the vars array. This is passed to get_records_sql and the returned result is stored
        // in filter props. If its a single record, its available to the body area.
        // otherwise it needs to be accessewd from javascript in the DATASET variable
        $filterprops['DATASET'] = false;
        if ($datasetbody) {
            $vars = [];
            if ($datasetvars) {
                $vars = explode(',', $datasetvars);
            }
            // turn numeric vars into numbers (not strings)
            $queryvars = [];
            for ($i = 0; $i < sizeof($vars); $i++) {
                if (is_numeric($vars[$i])) {
                    $queryvars[] = 0 + $vars[$i];
                } else {
                    $queryvars[] = $vars[$i];
                }
            }

            try {
                $alldata = $DB->get_records_sql($datasetbody, $queryvars);
                if ($alldata) {
                    $filterprops['DATASET'] = $alldata;
                    // replace the specified names with spec values, if its a one element array
                    if (sizeof($filterprops['DATASET']) == 1) {
                        $thedata = get_object_vars(reset($alldata));
                        foreach ($thedata as $name => $value) {
                            $genericotemplate = str_replace('@@DATASET:' . $name . '@@', $value, $genericotemplate);
                            $alternatecontent = str_replace('@@DATASET:' . $name . '@@', $value, $alternatecontent);
                        }
                    }
                }
            } catch (Exception $e) {
                // do nothing;
            }
        }//end of if dataset

        // If this is a webservice request, we don't need subsequent CSS and JS stuff
        if ($iswebservice && !empty($alternatecontent)) {
            return $alternatecontent;
        }

        // If this is the end tag we don't need to subsequent CSS and JS stuff. We already did it.
        if ($endtag) {
            return $genericotemplate;
        }

        // get the conf info we need for this template
        $thescript = $conf['templatescript_' . $tempindex];
        $defaults = $conf['templatedefaults_' . $tempindex];
        $requirejs = $conf['templaterequire_js_' . $tempindex];
        $requirecss = $conf['templaterequire_css_' . $tempindex];
        // are we AMD and Moodle 2.9 or more?
        $requireamd = $conf['template_amd_' . $tempindex] && $CFG->version >= 2015051100;

        // figure out if this is https or http. We don't want to scare the browser
        if (!$climode && strpos($PAGE->url->out(), 'https:') === 0) {
            $scheme = 'https:';
        } else {
            $scheme = 'http:';
        }

        // massage the js URL depending on schemes and rel. links etc. Then insert it
        // with AMD we set these as dependencies, so we don't need this song and dance
        if (!$requireamd) {
            $filterprops['JSLINK'] = false;
            if ($requirejs) {
                if (strpos($requirejs, '//') === 0) {
                    $requirejs = $scheme . $requirejs;
                } else if (strpos($requirejs, '/') === 0) {
                    $requirejs = $CFG->wwwroot . $requirejs;
                }

                // for load method: NO AMD
                $PAGE->requires->js(new \moodle_url($requirejs));

                // for load method: AMD
                // $require_js = substr($require_js, 0, -3);
                $filterprops['JSLINK'] = $requirejs;
            }

            // if we have an uploaded JS file, then lets include that
            $filterprops['JSUPLOAD'] = false;
            $uploadjsfile = $conf['uploadjs' . $tempindex];
            if ($uploadjsfile) {
                $uploadjsurl = \filter_generico\generico_utils::setting_file_url($uploadjsfile, 'uploadjs' . $tempindex);

                // for load method: NO AMD
                $PAGE->requires->js($uploadjsurl);

                // for load method: AMD
                // $uploadjsurl = substr($uploadjsurl, 0, -3);
                $filterprops['JSUPLOAD'] = $uploadjsurl;
            }
        }

        // massage the CSS URL depending on schemes and rel. links etc.
        if (!empty($requirecss)) {
            if (strpos($requirecss, '//') === 0) {
                $requirecss = $scheme . $requirecss;
            } else if (strpos($requirecss, '/') === 0) {
                $requirecss = $CFG->wwwroot . $requirecss;
            }
        }

        // if we have an uploaded CSS file, then lets include that
        $uploadcssfile = $conf['uploadcss' . $tempindex];
        if ($uploadcssfile) {
            $uploadcssurl = \filter_generico\generico_utils::setting_file_url($uploadcssfile, 'uploadcss' . $tempindex);
        }

        // set up our revision flag for forcing cache refreshes etc
        if (!empty($conf['revision'])) {
            $revision = $conf['revision'];
        } else {
            $revision = '0';
        }

        // if not too late: load css in header
        // if too late: inject it there via JS
        $filterprops['CSSLINK'] = false;
        $filterprops['CSSUPLOAD'] = false;
        $filterprops['CSSCUSTOM'] = false;

        // require any scripts from the template
        $customcssurl = false;
        if ($conf['templatestyle_' . $tempindex]) {
            $url = '/filter/generico/genericocss.php';
            $params = [
                't' => $tempindex,
                'rev' => $revision,
            ];
            $customcssurl = new \moodle_url($url, $params);
        }

        if (!$PAGE->headerprinted && !$PAGE->requires->is_head_done()) {
            if ($requirecss) {
                $PAGE->requires->css(new \moodle_url($requirecss));
            }
            if ($uploadcssfile) {
                $PAGE->requires->css($uploadcssurl);
            }
            if ($customcssurl) {
                $PAGE->requires->css($customcssurl);
            }
        } else {
            if ($requirecss) {
                $filterprops['CSSLINK'] = $requirecss;
            }
            if ($uploadcssfile) {
                $filterprops['CSSUPLOAD'] = $uploadcssurl->out();
            }
            if ($customcssurl) {
                $filterprops['CSSCUSTOM'] = $customcssurl->out();
            }

        }

        // Tell javascript which template this is
        $filterprops['TEMPLATEID'] = $tempindex;

        $jsmodule = [
            'name' => 'filter_generico',
            'fullpath' => '/filter/generico/module.js',
            'requires' => ['json'],
        ];

        // AMD or not, and then load our js for this template on the page
        if ($requireamd) {

            $generator = new \filter_generico\template_script_generator($tempindex);
            $templateamdscript = $generator->get_template_script();

            // props can't be passed at much length , Moodle complains about too many
            // so we do this ... lets hope it don't break things
            $jsonstring = json_encode($filterprops);
            $propshtml = \html_writer::tag('input', '',
                ['id' => 'filter_generico_amdopts_' . $filterprops['AUTOID'], 'type' => 'hidden', 'value' => $jsonstring]);
            $genericotemplate = $propshtml . $genericotemplate;

            // load define for this template. Later it will be called from loadgenerico
            $PAGE->requires->js_amd_inline($templateamdscript);
            // for AMD generico script
            $PAGE->requires->js_call_amd('filter_generico/generico_amd', 'loadgenerico',
                [['AUTOID' => $filterprops['AUTOID']]]);

        } else {

            // require any scripts from the template
            $url = '/filter/generico/genericojs.php';
            $params = [
                't' => $tempindex,
                'rev' => $revision,
            ];
            $moodleurl = new \moodle_url($url, $params);
            $PAGE->requires->js($moodleurl);

            // for no AMD
            $PAGE->requires->js_init_call('M.filter_generico.loadgenerico', [$filterprops], false, $jsmodule);
        }

        // finally return our template text
        return $genericotemplate;
    }// end of function filter_generico_callback

}//end of class
