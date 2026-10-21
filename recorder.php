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
 * The Cloud Poodll audio/video recorder, shown in an iframe inside the file picker.
 *
 * @package    repository_cloudpoodll
 * @copyright  2025 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/repository/lib.php');

use repository_cloudpoodll\constants;
use repository_cloudpoodll\utils;

$repoid = required_param('repo_id', PARAM_INT);
$contextid = required_param('ctx_id', PARAM_INT);

require_login(null, false);
$context = context::instance_by_id($contextid);
$repository = repository::get_repository_by_id($repoid, $context);
$repository->check_capability();
if (!($repository instanceof repository_cloudpoodll) || !$repository->is_recorder()) {
    throw new moodle_exception('invalidrepositoryid', 'repository');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/repository/cloudpoodll/recorder.php', ['repo_id' => $repoid, 'ctx_id' => $contextid]));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('mode_' . $repository->get_mode(), constants::M_COMPONENT));
$PAGE->add_body_class('repository_cloudpoodll_recorderpage');

$apiuser = get_config(constants::M_SHORTNAME, 'apiuser');
$apisecret = get_config(constants::M_SHORTNAME, 'apisecret');
$token = '';
if ($apiuser && $apisecret) {
    $token = utils::fetch_token($apiuser, $apisecret);
}
$tokenerror = $apiuser && $apisecret ? utils::fetch_token_error($token) :
    get_string('nocredentials', constants::M_COMPONENT, $CFG->wwwroot . constants::M_PLUGINSETTINGS);

echo $OUTPUT->header();
if ($tokenerror !== '') {
    echo $OUTPUT->notification($tokenerror, 'warning');
} else {
    $recorder = $repository->get_recorder();
    echo $OUTPUT->render_from_template('repository_cloudpoodll/recorder', $recorder->get_recorder_context($token));
    $PAGE->requires->js_call_amd('repository_cloudpoodll/recorder', 'init', [[
        'recorderid' => constants::ID_RECORDER,
        'repositoryid' => $repoid,
        'contextid' => $contextid,
        'timeout' => constants::CONVERSION_TIMEOUT,
        'pollinterval' => constants::CONVERSION_POLLINTERVAL,
    ]]);
}
echo $OUTPUT->footer();
