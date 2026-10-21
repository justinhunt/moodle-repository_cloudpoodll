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
 * Cloud Poodll audio and video recording for the repository.
 *
 * Recordings are uploaded to Cloud Poodll and converted to MP3/MP4 there. They are kept in the
 * cloud for one day only: once converted, the file is downloaded into the user's draft area.
 *
 * @package    repository_cloudpoodll
 * @copyright  2025 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recorder {
    /** @var string audio or video */
    protected $mode;

    /** @var string recorder skin */
    protected $skin;

    /** @var int recording time limit in seconds, 0 for none */
    protected $timelimit;

    /**
     * Constructor.
     *
     * @param string $mode constants::MODE_AUDIO or constants::MODE_VIDEO
     * @param string $skin recorder skin
     * @param int $timelimit time limit in seconds
     */
    public function __construct($mode, $skin = '', $timelimit = 0) {
        $this->mode = $mode === constants::MODE_VIDEO ? constants::MODE_VIDEO : constants::MODE_AUDIO;
        $skins = self::get_skin_options($this->mode);
        $this->skin = array_key_exists($skin, $skins) ? $skin : array_key_first($skins);
        $this->timelimit = (int) $timelimit;
    }

    /**
     * The recorder skins that suit a mode.
     *
     * @param string $mode
     * @return array skin => label
     */
    public static function get_skin_options($mode) {
        $skins = [
            constants::SKIN_FRESH => get_string('skinfresh', constants::M_COMPONENT),
            constants::SKIN_PLAIN => get_string('skinplain', constants::M_COMPONENT),
            constants::SKIN_BMR => get_string('skinbmr', constants::M_COMPONENT),
            constants::SKIN_123 => get_string('skin123', constants::M_COMPONENT),
            constants::SKIN_ONCE => get_string('skinonce', constants::M_COMPONENT),
        ];
        if ($mode === constants::MODE_VIDEO) {
            // Fresh is an audio only skin, screen recording is video only.
            unset($skins[constants::SKIN_FRESH]);
            $skins = [constants::SKIN_BMR => $skins[constants::SKIN_BMR]] + $skins;
            $skins[constants::SKIN_SCREEN] = get_string('skinscreen', constants::M_COMPONENT);
        }
        return $skins;
    }

    /**
     * The size of the recorder iframe for the current mode and skin.
     *
     * @return array [width, height]
     */
    public function get_dimensions() {
        if ($this->mode === constants::MODE_AUDIO) {
            switch ($this->skin) {
                case constants::SKIN_FRESH:
                    return [400, 300];
                case constants::SKIN_PLAIN:
                    return [360, 190];
                default:
                    return [360, 240];
            }
        }
        switch ($this->skin) {
            case constants::SKIN_BMR:
                return [360, 450];
            case constants::SKIN_123:
                return [450, 550];
            case constants::SKIN_ONCE:
            case constants::SKIN_SCREEN:
                return [350, 290];
            default:
                return [360, 410];
        }
    }

    /**
     * The template context for the Cloud Poodll recorder container.
     *
     * @param string $token Cloud Poodll token
     * @return array
     */
    public function get_recorder_context($token) {
        global $CFG, $USER;

        [$width, $height] = $this->get_dimensions();
        $hints = base64_encode(json_encode(['encoder' => 'auto']));

        return [
            'recid' => constants::ID_RECORDER,
            'dataid' => 'therecorder',
            'parent' => $CFG->wwwroot,
            'localloader' => '/repository/cloudpoodll/poodlllocalloader.php',
            'cloudpoodllurl' => utils::get_cloud_poodll_server(),
            'media' => $this->mode,
            'appid' => constants::M_COMPONENT,
            'owner' => hash('md5', $USER->username),
            'type' => $this->skin,
            'width' => $width,
            'height' => $height,
            'timelimit' => $this->timelimit,
            'transcode' => 1,
            'transcribe' => 0,
            'subtitle' => 0,
            'language' => 'en-US',
            'expiredays' => constants::RECORDING_EXPIREDAYS,
            'region' => get_config(constants::M_SHORTNAME, 'awsregion') ?: 'useast1',
            'hints' => $hints,
            'token' => $token,
        ];
    }

    /**
     * The file extension of a converted recording.
     *
     * @return string
     */
    public function get_extension() {
        return $this->mode === constants::MODE_VIDEO ? 'mp4' : 'mp3';
    }

    /**
     * Download a converted recording from Cloud Poodll into a new draft area.
     *
     * @param string $mediaurl URL of the converted recording
     * @return array|false draftitemid, filename and drafturl, or false on failure
     */
    public function download_to_draft($mediaurl) {
        global $CFG, $USER;
        require_once($CFG->libdir . '/filelib.php');

        if (!utils::is_poodll_media_url($mediaurl)) {
            return false;
        }

        $extension = $this->get_extension();
        $urlpath = parse_url($mediaurl, PHP_URL_PATH);
        if (strtolower(pathinfo($urlpath, PATHINFO_EXTENSION)) !== $extension) {
            return false;
        }

        $tempdir = make_request_directory();
        $temppath = $tempdir . '/recording.' . $extension;
        $curl = new \curl();
        $result = $curl->download_one($mediaurl, null, ['filepath' => $temppath, 'timeout' => 300]);
        $info = $curl->get_info();
        if (
            $result !== true || empty($info['http_code']) || (int) $info['http_code'] !== 200
                || !file_exists($temppath) || filesize($temppath) === 0
        ) {
            return false;
        }

        $draftitemid = file_get_unused_draft_itemid();
        $filename = $this->mode . '_' . date('Ymd_His') . '.' . $extension;
        $filerecord = [
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $filename,
        ];
        try {
            get_file_storage()->create_file_from_pathname($filerecord, $temppath);
        } catch (\moodle_exception $e) {
            return false;
        }

        return [
            'draftitemid' => $draftitemid,
            'filename' => $filename,
            'drafturl' => \moodle_url::make_draftfile_url($draftitemid, '/', $filename)->out(false),
        ];
    }
}
