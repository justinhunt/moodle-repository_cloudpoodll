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

defined('MOODLE_INTERNAL') || die();


/**
 * Class constants
 *
 * @package    repository_cloudpoodll
 * @copyright  2025 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class constants {

    //component name, db tables, things that define app
    const M_COMPONENT = 'repository_cloudpoodll';

    const M_SHORTNAME = 'cloudpoodll';
    const M_DEFAULT_CLOUDPOODLL = "cloud.poodll.com";

    const CLOUDPOODLL_OPTION = -1;
    const AIPROVIDER_COMPONENT = 'aiplacement_poodll';
    const AIPROVIDER_ACTION = 'generate_wordcards_image';

    /** @var string Where the admin enters the Poodll API credentials (the repository type settings page). */
    const M_PLUGINSETTINGS = '/admin/repository.php?action=edit&repos=cloudpoodll';

    /** @var string Repository instance mode: generate images. Each instance has one mode. */
    const MODE_IMAGE = 'image';
    /** @var string Repository instance mode: record audio. */
    const MODE_AUDIO = 'audio';
    /** @var string Repository instance mode: record video. */
    const MODE_VIDEO = 'video';

    /** @var string Cloud Poodll recorder skin: plain. */
    const SKIN_PLAIN = 'standard';
    /** @var string Cloud Poodll recorder skin: Burnt Rose. */
    const SKIN_BMR = 'bmr';
    /** @var string Cloud Poodll recorder skin: 1-2-3. */
    const SKIN_123 = 'onetwothree';
    /** @var string Cloud Poodll recorder skin: fresh (audio only). */
    const SKIN_FRESH = 'fresh';
    /** @var string Cloud Poodll recorder skin: once. */
    const SKIN_ONCE = 'once';
    /** @var string Cloud Poodll recorder skin: screen recorder (video only). */
    const SKIN_SCREEN = 'screen';

    /** @var int Recordings are only staged in the cloud for conversion, then downloaded, so keep them a day. */
    const RECORDING_EXPIREDAYS = 1;

    /** @var int How long the recorder page waits for a recording to be converted (seconds). */
    const CONVERSION_TIMEOUT = 600;
    /** @var int How often the recorder page checks whether the conversion is finished (seconds). */
    const CONVERSION_POLLINTERVAL = 4;

    /** @var string The DOM id of the recorder container on the recorder page. */
    const ID_RECORDER = 'repository_cloudpoodll_therecorder';

}
