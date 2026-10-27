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

namespace repository_cloudpoodll\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/repository/lib.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use repository_cloudpoodll\utils;

/**
 * Check whether Cloud Poodll has finished converting a recording.
 *
 * @package    repository_cloudpoodll
 * @copyright  2025 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_media_ready extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'repositoryid' => new external_value(PARAM_INT, 'Repository instance id'),
            'contextid' => new external_value(PARAM_INT, 'Context the file picker was opened in'),
            'mediaurl' => new external_value(PARAM_URL, 'URL of the converted recording'),
        ]);
    }

    /**
     * Check whether the converted recording exists yet.
     *
     * @param int $repositoryid
     * @param int $contextid
     * @param string $mediaurl
     * @return array
     */
    public static function execute($repositoryid, $contextid, $mediaurl) {
        $params = self::validate_parameters(self::execute_parameters(), [
            'repositoryid' => $repositoryid,
            'contextid' => $contextid,
            'mediaurl' => $mediaurl,
        ]);

        $context = \context::instance_by_id($params['contextid']);
        self::validate_context($context);
        $repository = \repository::get_repository_by_id($params['repositoryid'], $context);
        $repository->check_capability();

        if (!utils::is_poodll_media_url($params['mediaurl'])) {
            return ['ready' => false, 'valid' => false];
        }
        return ['ready' => utils::is_media_ready($params['mediaurl']), 'valid' => true];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'ready' => new external_value(PARAM_BOOL, 'True if the converted recording can be downloaded'),
            'valid' => new external_value(PARAM_BOOL, 'False if the URL is not a Cloud Poodll recording'),
        ]);
    }
}
