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

namespace repository_cloudpoodll;

/**
 * Class utils
 *
 * @package    repository_cloudpoodll
 * @copyright  2025 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /**
     * Get the Cloud Poodll server URL, without a trailing slash.
     *
     * The repository type settings are stored under the type name (cloudpoodll), not the component.
     *
     * @return string
     */
    public static function get_cloud_poodll_server() {
        $server = get_config(constants::M_SHORTNAME, 'cloudpoodllserver');
        $server = trim((string) $server);
        if ($server === '') {
            $server = constants::M_DEFAULT_CLOUDPOODLL;
        }
        if (strpos($server, 'https://') !== 0 && strpos($server, 'http://') !== 0) {
            $server = 'https://' . $server;
        }
        return rtrim($server, '/');
    }

    // we use curl to fetch transcripts from AWS and Tokens from cloudpoodll
    // this is our helper
    public static function curl_fetch($url, $postdata = false, $ispost = false)
    {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();

        if ($ispost) {
            $result = $curl->post($url, $postdata);
        } else {
            $result = $curl->get($url, $postdata);
        }
        return $result;
    }

    // This is called from the settings page and we do not want to make calls out to cloud.poodll.com on settings
    // page load, for performance and stability issues. So if the cache is empty and/or no token, we just show a
    // "refresh token" links
    public static function fetch_token_for_display($apiuser, $apisecret)
    {
        global $CFG;

        // First check that we have an API id and secret
        // refresh token
        $refresh = \html_writer::link(
            $CFG->wwwroot . '/repository/cloudpoodll/refreshtoken.php',
            get_string('refreshtoken', constants::M_COMPONENT)
        ) . '<br>';

        $message = '';
        $apiuser = self::super_trim($apiuser);
        $apisecret = self::super_trim($apisecret);
        if (empty($apiuser)) {
            $message .= get_string('noapiuser', constants::M_COMPONENT) . '<br>';
        }
        if (empty($apisecret)) {
            $message .= get_string('noapisecret', constants::M_COMPONENT);
        }

        if (!empty($message)) {
            return $refresh . $message;
        }

        // Fetch from cache and process the results and display
        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');

        // if we have no token object the creds were wrong ... or something
        if (!($tokenobject)) {
            $message = get_string('notokenincache', constants::M_COMPONENT);
            // if we have an object but its no good, creds werer wrong ..or something
        } else if (!property_exists($tokenobject, 'token') || empty($tokenobject->token)) {
            $message = get_string('credentialsinvalid', constants::M_COMPONENT);
            // if we do not have subs, then we are on a very old token or something is wrong, just get out of here.
        } else if (!property_exists($tokenobject, 'subs')) {
            $message = 'No subscriptions found at all';
        }
        if (!empty($message)) {
            return $refresh . $message;
        }

        // we have enough info to display a report. Lets go.
        foreach ($tokenobject->subs as $sub) {
            $sub->expiredate = date('d/m/Y', $sub->expiredate);
            $message .= get_string('displaysubs', constants::M_COMPONENT, $sub) . '<br>';
        }

        // Is app authorised
        if (
            in_array(constants::M_COMPONENT, $tokenobject->apps) &&
            self::is_site_registered($tokenobject->sites, true)
        ) {
            $message .= get_string('appauthorised', constants::M_COMPONENT) . '<br>';
        } else {
            $message .= get_string('appnotauthorised', constants::M_COMPONENT) . '<br>';
        }

        return $refresh . $message;

    }

    // We need a Poodll token to make all this recording and transcripts happen
    public static function fetch_token($apiuser, $apisecret, $force = false)
    {

        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');
        $tokenuser = $cache->get('recentpoodlluser');
        $apiuser = self::super_trim($apiuser);
        $apisecret = self::super_trim($apisecret);

        // if we got a token and its less than expiry time
        // use the cached one
        if ($tokenobject && $tokenuser && $tokenuser == $apiuser && !$force) {
            if ($tokenobject->validuntil == 0 || $tokenobject->validuntil > time()) {
                return $tokenobject->token;
            }
        }

        // Send the request & save response to $resp
        $tokenurl = self::get_cloud_poodll_server() . "/local/cpapi/poodlltoken.php";
        $postdata = [
            'username' => $apiuser,
            'password' => $apisecret,
            'service' => 'cloud_poodll',
        ];
        $tokenresponse = self::curl_fetch($tokenurl, $postdata);
        if ($tokenresponse) {
            $respobject = json_decode($tokenresponse);
            if ($respobject && property_exists($respobject, 'token')) {
                $token = $respobject->token;
                // store the expiry timestamp and adjust it for diffs between our server times
                if ($respobject->validuntil) {
                    $validuntil = $respobject->validuntil - ($respobject->poodlltime - time());
                    // we refresh one hour out, to prevent any overlap
                    $validuntil = $validuntil - (1 * HOURSECS);
                } else {
                    $validuntil = 0;
                }

                // cache the token
                $tokenobject = new \stdClass();
                $tokenobject->token = $token;
                $tokenobject->validuntil = $validuntil;
                $tokenobject->subs = false;
                $tokenobject->apps = false;
                $tokenobject->sites = false;
                if (property_exists($respobject, 'subs')) {
                    $tokenobject->subs = $respobject->subs;
                }
                if (property_exists($respobject, 'apps')) {
                    $tokenobject->apps = $respobject->apps;
                }
                if (property_exists($respobject, 'sites')) {
                    $tokenobject->sites = $respobject->sites;
                }
                if (property_exists($respobject, 'awsaccesssecret')) {
                    $tokenobject->awsaccesssecret = $respobject->awsaccesssecret;
                }
                if (property_exists($respobject, 'awsaccessid')) {
                    $tokenobject->awsaccessid = $respobject->awsaccessid;
                }

                $cache->set('recentpoodlltoken', $tokenobject);
                $cache->set('recentpoodlluser', $apiuser);

            } else {
                $token = '';
                if ($respobject && property_exists($respobject, 'error')) {
                    // ERROR = $resp_object->error
                }
            }
        } else {
            $token = '';
        }
        return $token;
    }

    // check site URL is actually registered
    static function is_site_registered($sites, $wildcardok = true)
    {
        global $CFG;

        foreach ($sites as $site) {

            // get arrays of the wwwroot and registered url
            // just in case, lowercase'ify them
            $thewwwroot = strtolower($CFG->wwwroot);
            $theregisteredurl = strtolower($site);
            $theregisteredurl = self::super_trim($theregisteredurl);

            // add http:// or https:// to URLs that do not have it
            if (
                strpos($theregisteredurl, 'https://') !== 0 &&
                strpos($theregisteredurl, 'http://') !== 0
            ) {
                $theregisteredurl = 'https://' . $theregisteredurl;
            }

            // if neither parsed successfully, that a no straight up
            $wwwrootbits = parse_url($thewwwroot);
            $registeredbits = parse_url($theregisteredurl);
            if (!$wwwrootbits || !$registeredbits) {
                // this is not a match
                continue;
            }

            // get the subdomain widlcard address, ie *.a.b.c.d.com
            $wildcardsubdomainwwwroot = '';
            if (array_key_exists('host', $wwwrootbits)) {
                $wildcardparts = explode('.', $wwwrootbits['host']);
                $wildcardparts[0] = '*';
                $wildcardsubdomainwwwroot = implode('.', $wildcardparts);
            } else {
                // this is not a match
                continue;
            }

            // match either the exact domain or the wildcard domain or fail
            if (array_key_exists('host', $registeredbits)) {
                // this will cover exact matches and path matches
                if ($registeredbits['host'] === $wwwrootbits['host']) {
                    // this is a match
                    return true;
                    // this will cover subdomain matches
                } else if (($registeredbits['host'] === $wildcardsubdomainwwwroot) && $wildcardok) {
                    // yay we are registered!!!!
                    return true;
                } else {
                    // not a match
                    continue;
                }
            } else {
                // not a match
                return false;
            }
        }
        return false;
    }

    // check token and tokenobject(from cache)
    // return error message or blank if its all ok
    public static function fetch_token_error($token)
    {
        global $CFG;

        // check token authenticated
        if (empty($token)) {
            $message = get_string(
                'novalidcredentials',
                constants::M_COMPONENT,
                $CFG->wwwroot . constants::M_PLUGINSETTINGS
            );
            return $message;
        }

        // Fetch from cache and process the results and display.
        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');

        // we should not get here if there is no token, but lets gracefully die, [v unlikely]
        if (!($tokenobject)) {
            $message = get_string('notokenincache', constants::M_COMPONENT);
            return $message;
        }

        // We have an object but its no good, creds were wrong ..or something. [v unlikely]
        if (!property_exists($tokenobject, 'token') || empty($tokenobject->token)) {
            $message = get_string('credentialsinvalid', constants::M_COMPONENT);
            return $message;
        }
        // if we do not have subs.
        if (!property_exists($tokenobject, 'subs')) {
            $message = get_string('nosubscriptions', constants::M_COMPONENT);
            return $message;
        }
        // Is app authorised?
        if (!property_exists($tokenobject, 'apps') || !in_array(constants::M_COMPONENT, $tokenobject->apps)) {
            $message = get_string('appnotauthorised', constants::M_COMPONENT);
            return $message;
        }

        // just return empty if there is no error.
        return '';
    }

    public static function get_region_options()
    {
        return [
            "useast1" => get_string("useast1", constants::M_COMPONENT),
            "tokyo" => get_string("tokyo", constants::M_COMPONENT),
            "sydney" => get_string("sydney", constants::M_COMPONENT),
            "dublin" => get_string("dublin", constants::M_COMPONENT),
            "ottawa" => get_string("ottawa", constants::M_COMPONENT),
            "frankfurt" => get_string("frankfurt", constants::M_COMPONENT),
            "london" => get_string("london", constants::M_COMPONENT),
            "saopaulo" => get_string("saopaulo", constants::M_COMPONENT),
            "singapore" => get_string("singapore", constants::M_COMPONENT),
            "mumbai" => get_string("mumbai", constants::M_COMPONENT),
            "capetown" => get_string("capetown", constants::M_COMPONENT),
            "bahrain" => get_string("bahrain", constants::M_COMPONENT),
            "ningxia" => get_string("ningxia", constants::M_COMPONENT),
        ];
    }


        /**
     * array_key_last polyfill
     *
     * @param mixed $arr
     * @return int|string|null
     */
    public static function array_key_last($arr) {
        if (function_exists('array_key_last')) {
            return array_key_last($arr);
        }
        if (!empty($arr)) {
            return key(array_slice($arr, -1, 1, true));
        }
        return null;
    }

    public static function super_trim($str)
    {
        if ($str == null) {
            return '';
        } else {
            $str = trim($str);
            return $str;
        }
    }




    /**
     * Is this URL a converted recording in one of the Poodll output buckets?
     *
     * The URL comes back from the browser, so only fetch it if it points at a Poodll bucket.
     * Both virtual-hosted (bucket.s3.region.amazonaws.com) and path-style (s3-region.amazonaws.com/bucket)
     * URLs are used, depending on the region.
     *
     * @param string $url
     * @return bool
     */
    public static function is_poodll_media_url($url) {
        $bits = parse_url((string) $url);
        if (!$bits || ($bits['scheme'] ?? '') !== 'https' || empty($bits['host']) || empty($bits['path'])) {
            return false;
        }
        if (isset($bits['user']) || isset($bits['pass']) || isset($bits['port'])) {
            return false;
        }
        $host = strtolower($bits['host']);
        $bucket = 'poodll-(audio|video)processing-out[a-z0-9-]*';
        $s3 = 's3[a-z0-9.-]*\.amazonaws\.com(\.cn)?';
        if (preg_match('/^' . $bucket . '\.' . $s3 . '$/', $host)) {
            return true;
        }
        if (preg_match('/^' . $s3 . '$/', $host) && preg_match('#^/' . $bucket . '/#', $bits['path'])) {
            return true;
        }
        return false;
    }

    /**
     * Has the converted recording arrived in the bucket yet?
     *
     * Until the conversion is finished the object does not exist and S3 answers 403 (AccessDenied).
     *
     * @param string $url a URL that passed is_poodll_media_url()
     * @return bool
     */
    public static function is_media_ready($url) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $curl = new \curl();
        $curl->head($url, ['CURLOPT_TIMEOUT' => 15]);
        $info = $curl->get_info();
        if ($curl->get_errno() || empty($info['http_code']) || (int) $info['http_code'] !== 200) {
            return false;
        }
        // A zero byte object is not a finished conversion.
        return !isset($info['download_content_length']) || (int) $info['download_content_length'] !== 0;
    }

    /**
     * Create a system level repository instance for each mode that does not have one yet.
     *
     * Called when the repository type is enabled, and on upgrade from the image-only version.
     *
     * @return void
     */
    public static function create_default_instances() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/repository/lib.php');

        $type = $DB->get_record('repository', ['type' => constants::M_SHORTNAME]);
        if (!$type) {
            return;
        }

        $existingmodes = [];
        $instances = $DB->get_records('repository_instances', ['typeid' => $type->id]);
        foreach ($instances as $instance) {
            $mode = $DB->get_field(
                'repository_instance_config',
                'value',
                ['instanceid' => $instance->id, 'name' => 'mode']
            );
            if ($mode === false || $mode === null || $mode === '') {
                // Instances from before recording was added are image generators.
                $mode = constants::MODE_IMAGE;
                $DB->delete_records('repository_instance_config', ['instanceid' => $instance->id, 'name' => 'mode']);
                $DB->insert_record(
                    'repository_instance_config',
                    (object) ['instanceid' => $instance->id, 'name' => 'mode', 'value' => $mode]
                );
            }
            $existingmodes[$mode] = true;
        }

        $defaults = [
            constants::MODE_IMAGE => ['recorderskin' => '', 'timelimit' => 0],
            constants::MODE_AUDIO => ['recorderskin' => constants::SKIN_FRESH, 'timelimit' => 0],
            constants::MODE_VIDEO => ['recorderskin' => constants::SKIN_BMR, 'timelimit' => 0],
        ];
        foreach ($defaults as $mode => $options) {
            if (isset($existingmodes[$mode])) {
                continue;
            }
            $options['mode'] = $mode;
            $options['name'] = get_string('instancename_' . $mode, constants::M_COMPONENT);
            \repository::static_function(
                constants::M_SHORTNAME,
                'create',
                constants::M_SHORTNAME,
                0,
                \context_system::instance(),
                $options
            );
        }
        \cache::make('core', 'repositories')->purge();
    }
}
