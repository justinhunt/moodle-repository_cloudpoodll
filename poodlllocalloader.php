<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Loads the Cloud Poodll recorder from this site, for browsers (Safari, iOS) that need a same-site iframe.
 *
 * The recorder settings arrive in the query string and are read by the Cloud Poodll loader script.
 *
 * @package    repository_cloudpoodll
 * @copyright  2025 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing
require_once(__DIR__ . '/../../config.php');

header('Pragma: no-cache');
header('Expires: -1');
header('Cache-Control: no-cache');

// Always load the scripts from the configured server, never from a URL in the request.
$cloudpoodllurl = s(\repository_cloudpoodll\utils::get_cloud_poodll_server());
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Poodll Recorder</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="{$cloudpoodllurl}/local/cpapi/cloudstyles.css?v=023">
    <link rel="stylesheet" type="text/css"
        href="{$cloudpoodllurl}/local/cpapi/assets/font-awesome/css/font-awesome.min.css?v=023">
    <script type="text/javascript">
        M = {};
        M.cfg = {};
        M.cfg.wwwroot = "{$cloudpoodllurl}";
    </script>
    <script type="text/javascript" src="{$cloudpoodllurl}/local/cpapi/fastpoodllconfig.js?v=002"></script>
    <script type="text/javascript" src="{$cloudpoodllurl}/local/cpapi/lib/requirejs/require.js"></script>
    <script type="text/javascript" src="{$cloudpoodllurl}/local/cpapi/poodllloader.min.js?v=012"></script>
    <style type="text/css">
        #AUTOID, .iframe-bmr {
            max-width: 410px;
        }
        #AUTOID .poodll_mediarecorderbox_bmr {
            width: auto !important;
        }
        #AUTOID, .iframe-bmr, #settingsicon_filter_poodll_controlbar_AUTOID {
            bottom: 0px;
            right: 0px;
        }
    </style>
</head>
<body>
    <div id="AUTOID"></div>
</body>
</html>
HTML;
