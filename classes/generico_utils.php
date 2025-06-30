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
 * Generico utilities
 *
 * @package    filter_generico
 * @subpackage generico
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generico_utils {
    /**
     * @var int number of generico templates
     */
    const FILTER_GENERICO_TEMPLATE_COUNT = 20;

    /**
     * @var int CloudPoodll val by registration code
     */
    const CLOUDPOODLL_VAL_BY_REGCODE = 1;

    /**
     * @var int CloudPoodll val by apicreds
     */
    const CLOUDPOODLL_VAL_BY_APICREDS = 2;

    /**
     * @var int Cloudpoodll is registered
     */
    const CLOUDPOODLL_IS_REGISTERED = 1;

    /**
     * @var int Cloudpoodll is unregistered
     */
    const CLOUDPOODLL_IS_UNREGISTERED = 0;

    /**
     * @var int Cloudpoodll is expired
     */
    const CLOUDPOODLL_IS_EXPIRED = 2;

    /**
     * Empty prop array
     * @return array
     */
    public static function fetch_emptyproparray() {
        $proparray = [];
        $proparray['AUTOID'] = '';
        $proparray['CSSLINK'] = '';
        return $proparray;
    }

    /**
     * Return an array of variable names
     *
     * @param string $template containing @@variable@@ variables
     * @return array of variable names parsed from template string
     */
    public static function fetch_variables($template) {
        $matches = [];
        $t = preg_match_all('/@@(.*?)@@/s', $template, $matches);
        if (count($matches) > 1) {
            return ($matches[1]);
        } else {
            return [];
        }
    }

    /**
     * Fetch filter properties
     *
     * @param string $filterstring
     * @return array
     */
    public static function fetch_filter_properties($filterstring) {
        // Lets do a general clean of all input here.
        // See: https://github.com/justinhunt/moodle-filter_generico/issues/7 .
        $filterstring = clean_param($filterstring, PARAM_TEXT);

        // This just removes the {GENERICO: .. }.
        $rawproperties = explode("{GENERICO:", $filterstring);
        // Here we remove any html tags we find. They should not be in here.
        $rawproperties = $rawproperties[1];
        $rawproperties = explode("}", $rawproperties);
        // Here we remove any html tags we find. They should not be in here
        // and we return the guts of the filter string for parsing.
        $rawproperties = strip_tags($rawproperties[0]);

        // Now we just have our properties string
        // Lets run our regular expression over them
        // string should be property=value,property=value
        // got this regexp from http://stackoverflow.com/questions/168171/regular-expression-for-parsing-name-value-pairs .
        $regexpression = '/([^=,]*)=("[^"]*"|[^,"]*)/';
        $matches = [];

        // Here we match the filter string and split into name array (matches[1]) and value array (matches[2]).
        // We then add those to a name value array.
        $itemprops = [];
        if (preg_match_all($regexpression, $rawproperties, $matches, PREG_PATTERN_ORDER)) {
            $propscount = count($matches[1]);
            for ($cnt = 0; $cnt < $propscount; $cnt++) {
                // Prepare the new value.
                $newvalue = $matches[2][$cnt];
                // This could be done better, I am sure. WE are removing the quotes from start and end.
                // This wil however remove multiple quotes id they exist at start and end. NG really.
                $newvalue = trim($newvalue, '"');

                // Remove any @@ characters from the new value - that would be some sort of variable injection.
                $newvalue = str_replace('@@', '', $newvalue);

                // Prepare the new key.
                $newkey = trim($matches[1][$cnt]);

                // Remove any attempts to overwrite simple system values via the key.
                $systemvars = ['AUTOID', 'WWWROOT', 'MOODLEPAGEID'];
                if (in_array($newkey, $systemvars)) {
                    continue;
                }

                // Remove any attempts to overwrite system values that are sets of data.
                $systemvarspartial = ['URLPARAM:', 'COURSE:', 'USER:', 'DATASET:'];
                foreach ($systemvarspartial as $systemvar) {
                    if (stripos($newkey, $systemvar) === 0) {
                        $newkey = '';
                        break;
                    }
                }
                if (empty($newkey)) {
                    continue;
                }

                // Store the key/value pair.
                $itemprops[$newkey] = $newvalue;
            }
        }
        return $itemprops;
    }

    /**
     * Returns URL to the stored file via pluginfile.php.
     *
     * theme revision is used instead of the itemid.
     *
     * @param string $filepath
     * @param string $filearea
     * @return string protocol relative URL or null if not present
     */
    public static function setting_file_url($filepath, $filearea) {
        global $CFG;

        $component = 'filter_generico';
        $itemid = 0;
        $syscontext = \context_system::instance();

        $url = \moodle_url::make_file_url("$CFG->wwwroot/pluginfile.php",
                "/$syscontext->id/$component/$filearea/$itemid" . $filepath);

        return $url;
    }

    /**
     * File serving
     *
     * @param string $filearea
     * @param array $args
     * @param bool $forcedownload
     * @param array $options
     */
    public static function setting_file_serve($filearea, $args, $forcedownload, $options) {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");

        $syscontext = \context_system::instance();
        $component = 'filter_generico';

        $revision = array_shift($args);
        if ($revision < 0) {
            $lifetime = 0;
        } else {
            $lifetime = 60 * 60 * 24 * 60;
        }

        $fs = get_file_storage();
        $relativepath = implode('/', $args);

        $fullpath = "/{$syscontext->id}/{$component}/{$filearea}/0/{$relativepath}";
        $fullpath = rtrim($fullpath, '/');
        if ($file = $fs->get_file_by_hash(sha1($fullpath))) {
            send_stored_file($file, $lifetime, 0, $forcedownload, $options);
            return true;
        } else {
            send_file_not_found();
        }
    }

    /**
     * Update generico revision
     */
    public static function update_revision() {
        set_config('revision', time(), 'filter_generico');
    }

    /**
     * Check registered url
     *
     * @param string $theurl
     * @param bool $wildcardok
     */
    protected function check_registered_url($theurl, $wildcardok = true) {
        global $CFG;

        // Get arrays of the wwwroot and registered url
        // Just in case, lowercase'ify them.
        $thewwwroot = strtolower($CFG->wwwroot);
        $theregisteredurl = strtolower($theurl);
        $theregisteredurl = trim($theregisteredurl);

        // Add http:// or https:// to URLs that do not have it.
        if (strpos($theregisteredurl, 'https://') !== 0 &&
                strpos($theregisteredurl, 'http://') !== 0) {
            $theregisteredurl = 'https://' . $theregisteredurl;
        }

        // If neither parsed successfully, thats a no straight up.
        $wwwrootbits = parse_url($thewwwroot);
        $registeredbits = parse_url($theregisteredurl);
        if (!$wwwrootbits || !$registeredbits) {
            return self::CLOUDPOODLL_IS_UNREGISTERED;
        }

        // Get the subdomain widlcard address, ie *.a.b.c.d.com .
        $wildcardsubdomainwwwroot = '';
        if (array_key_exists('host', $wwwrootbits)) {
            $wildcardparts = explode('.', $wwwrootbits['host']);
            $wildcardparts[0] = '*';
            $wildcardsubdomainwwwroot = implode('.', $wildcardparts);
        } else {
            return self::CLOUDPOODLL_IS_UNREGISTERED;
        }

        // Match either the exact domain or the wildcard domain or fail.
        if (array_key_exists('host', $registeredbits)) {
            // This will cover exact matches and path matches.
            if ($registeredbits['host'] === $wwwrootbits['host']) {
                return self::CLOUDPOODLL_IS_REGISTERED;
                // This will cover subdomain matches but only for institution bigdog and enterprise license.
            } else if (($registeredbits['host'] === $wildcardsubdomainwwwroot) && $wildcardok) {
                // Yay we are registered!!!!
                return self::CLOUDPOODLL_IS_REGISTERED;
            } else {
                return self::CLOUDPOODLL_IS_UNREGISTERED;
            }
        } else {
            return self::CLOUDPOODLL_IS_UNREGISTERED;
        }
    }


    /**
     * Fetch token for display
     *
     * This is called from the settings page and we do not want to make calls out to cloud.poodll.com on settings
     * page load, for performance and stability issues. So if the cache is empty and/or no token, we just show a
     * "refresh token" link
     *
     * @param string $apiuser
     * @param string $apisecret
     * @return string
     */
    public function fetch_token_for_display($apiuser, $apisecret) {
        global $CFG;

        // First check that we have an API id and secret
        // Refresh token.
        $refresh = \html_writer::link($CFG->wwwroot . '/filter/generico/refreshtoken.php',
                        get_string('refreshtoken', constants::MOD_FRANKY)) . '<br>';

        $message = '';
        $apiuser = trim($apiuser);
        $apisecret = trim($apisecret);
        if (empty($apiuser)) {
            $message .= get_string('noapiuser', constants::MOD_FRANKY) . '<br>';
        }
        if (empty($apisecret)) {
            $message .= get_string('noapisecret', constants::MOD_FRANKY);
        }

        if (!empty($message)) {
            return $refresh . $message;
        }

        // Fetch from cache and process the results and display.
        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::MOD_FRANKY, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');

        // If we have no token object the creds were wrong ... or something.
        if (!($tokenobject)) {
            $message = get_string('notokenincache', constants::MOD_FRANKY);
            // If we have an object but its no good, creds werer wrong ..or something.
        } else if (!property_exists($tokenobject, 'token') || empty($tokenobject->token)) {
            $message = get_string('credentialsinvalid', constants::MOD_FRANKY);
            // If we do not have subs, then we are on a very old token or something is wrong, just get out of here.
        } else if (!property_exists($tokenobject, 'subs')) {
            $message = 'No subscriptions found at all';
        }
        if (!empty($message)) {
            return $refresh . $message;
        }

        // We have enough info to display a report. Lets go.
        foreach ($tokenobject->subs as $sub) {
            $sub->expiredate = date('d/m/Y', $sub->expiredate);
            $message .= get_string('displaysubs', constants::MOD_FRANKY, $sub) . '<br>';
        }
        // Is site authorised.
        $haveauthsite = false;
        foreach ($tokenobject->sites as $site) {
            if ($this->check_registered_url($site) == self::CLOUDPOODLL_IS_REGISTERED) {
                $haveauthsite = true;
                break;
            }
        }
        if (!$haveauthsite) {
            $message .= get_string('appnotauthorised', constants::MOD_FRANKY) . '<br>';
        } else {

            // Is app authorised.
            if (in_array(constants::MOD_FRANKY, $tokenobject->apps)) {
                $message .= get_string('appauthorised', constants::MOD_FRANKY) . '<br>';
            } else {
                $message .= get_string('appnotauthorised', constants::MOD_FRANKY) . '<br>';
            }
        }
        return $refresh . $message;
    }

    /**
     * Fetch cloud poodll token
     *
     * @param string $apiuser
     * @param string $apisecret
     * @param bool $force
     * @return mixed
     */
    public static function fetch_token($apiuser, $apisecret, $force = false) {
        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::MOD_FRANKY, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');
        $tokenuser = $cache->get('recentpoodlluser');
        $apiuser = trim($apiuser);
        $apisecret = trim($apisecret);

        // If we got a token and its less than expiry time
        // use the cached one.
        if ($tokenobject && $tokenuser && $tokenuser == $apiuser && !$force) {
            if ($tokenobject->validuntil == 0 || $tokenobject->validuntil > time()) {
                return $tokenobject->token;
            }
        }

        // Send the request & save response to $resp.
        $tokenurl = "https://cloud.poodll.com/local/cpapi/poodlltoken.php";
        $postdata = [
                'username' => $apiuser,
                'password' => $apisecret,
                'service' => 'cloud_poodll'];
        $tokenresponse = self::curl_fetch($tokenurl, $postdata);
        if ($tokenresponse) {
            $respobject = json_decode($tokenresponse);
            if ($respobject && property_exists($respobject, 'token')) {
                $token = $respobject->token;
                // Store the expiry timestamp and adjust it for diffs between our server times.
                if ($respobject->validuntil) {
                    $validuntil = $respobject->validuntil - ($respobject->poodlltime - time());
                    // We refresh one hour out, to prevent any overlap.
                    $validuntil = $validuntil - (1 * HOURSECS);
                } else {
                    $validuntil = 0;
                }

                // Make sure the token has all the bits in it we expect before caching it.
                $tokenobject = $respobject;
                $tokenobject->validuntil = $validuntil;
                if (!property_exists($tokenobject, 'subs')) {
                    $tokenobject->subs = false;
                }
                if (!property_exists($tokenobject, 'apps')) {
                    $tokenobject->apps = false;
                }
                if (!property_exists($tokenobject, 'sites')) {
                    $tokenobject->sites = false;
                }
                $cache->set('recentpoodlltoken', $tokenobject);
                $cache->set('recentpoodlluser', $apiuser);

            } else {
                $token = false;
            }
        } else {
            $token = false;
        }
        return $token;
    }


    /**
     * Curl fetch helper
     *
     * we use curl to fetch transcripts from AWS and Tokens from cloudpoodll
     * this is our helper
     *
     * @param string $url
     * @param array $postdata
     * @return string $result
     */
    public static function curl_fetch($url, $postdata) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();

        $result = $curl->get($url, $postdata);
        return $result;
    }

    /**
     * Determins if a specific context is allowed to use a given template
     *
     * @param context|null $context
     * @param int $templateidx Template index
     * @return bool true if allowed, else false.
     */
    public static function is_context_allowed(?\context $context, int $templateidx): bool {
        // Allowed context levels, e.g. "system", "course", "mod_xxxx".
        $allowedcontexts = self::explode_csv_list((string) get_config('filter_generico', 'allowedcontexts_' . $templateidx));
        if (!empty($allowedcontexts) && !in_array(self::get_context_name($context), $allowedcontexts)) {
            return false;
        }

        // Allowed specific context ids.
        $allowedcontextids = self::explode_csv_list((string) get_config('filter_generico', 'allowedcontextids_' . $templateidx));
        if (!empty($allowedcontextids) && !in_array($context->id, $allowedcontextids)) {
            return false;
        }

        return true;
    }

    /**
     * Explodes a CSV list of values and cleans any extra whitespace.
     *
     * @param string $csvlist string with csv values in it
     * @return array exploded values
     */
    private static function explode_csv_list(string $csvlist): array {
        return array_filter(array_map(fn($v) => trim($v), explode(',', $csvlist)));
    }

    /**
     * Get the context name
     *
     * @param context|null $context
     * @return string
     */
    private static function get_context_name(?\context $context): string {
        if (empty($context)) {
            return 'empty';
        }

        switch ($context->contextlevel) {
            case CONTEXT_MODULE:
                return 'mod_' . get_coursemodule_from_id(null, $context->instanceid, 0, false, MUST_EXIST)->modname;
            // We would use get_short_name here, but that is only available in 4.2+, so we must hardcode it :(.
            case CONTEXT_SYSTEM:
                return 'system';
            case CONTEXT_USER:
                return 'user';
            case CONTEXT_COURSE:
                return 'course';
            case CONTEXT_COURSECAT:
                return 'coursecat';
            case CONTEXT_BLOCK:
                return 'block';
            default:
                throw new \coding_exception("Unhandled contextlevel " . $context->contextlevel);
        }
    }
}
