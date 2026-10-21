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
 * Main repository class for Cloud Poodll AI image generator.
 *
 * @package    repository_cloudpoodll
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Core repository base lives at /public/repository/lib.php relative to public dirroot.
// In this migrated structure $CFG->dirroot already points to the public root (see public/lib/setup.php).
require_once($CFG->dirroot . '/repository/lib.php');

use repository_cloudpoodll\constants;
use repository_cloudpoodll\utils;
use repository_cloudpoodll\imagegen;
use repository_cloudpoodll\recorder;

/**
 * Repository implementation for generating images from prompts, and recording audio and video.
 *
 * Each repository instance has a mode (image, audio or video), so each shows up separately in the file picker.
 */
class repository_cloudpoodll extends repository
{

    /**
     * @var imagegen|null imagegen instance
     */
    protected $_imagegen = null;

    /**
     * Supported return types.
     * @return int
     */
    public function supported_returntypes()
    {
        return FILE_INTERNAL;
    }

    /**
     * Supported file types: images, audio or video, depending on the mode.
     * @return array
     */
    public function supported_filetypes() {
        switch ($this->get_mode()) {
            case constants::MODE_AUDIO:
                return ['web_audio'];
            case constants::MODE_VIDEO:
                return ['web_video'];
            default:
                return ['web_image'];
        }
    }

    /**
     * What this instance does: generate images, or record audio or video.
     *
     * Instances from before recording was added have no mode, and are image generators.
     *
     * @return string one of the constants::MODE_* values
     */
    public function get_mode() {
        $mode = $this->get_option('mode');
        if (in_array($mode, [constants::MODE_AUDIO, constants::MODE_VIDEO], true)) {
            return $mode;
        }
        return constants::MODE_IMAGE;
    }

    /**
     * Is this an audio or video recording instance?
     *
     * @return bool
     */
    public function is_recorder() {
        return $this->get_mode() !== constants::MODE_IMAGE;
    }

    /**
     * The recorder for an audio or video instance.
     *
     * @return recorder
     */
    public function get_recorder() {
        return new recorder(
            $this->get_mode(),
            (string) $this->get_option('recorderskin'),
            (int) $this->get_option('timelimit')
        );
    }

    /**
     * Enable the global search (used here as a prompt input field).
     * @return bool
     */
    public function global_search()
    {
        return false; // Enables the search box used as prompt input.
    }

    /**
     * Indicates this repository uses Moodle files (draft area).
     * @return bool
     */
    public function has_moodle_files() {
        return true;
    }

    public function get_listing($path = '', $page = 0)
    {

        global $USER, $OUTPUT;

        // Empty List
        $list = [
            'list' => [],
            'manage' => false,
            'dynload' => true,
            'nologin' => true,
            'nosearch' => false,
            'issearchresult' => false,
            'path' => [],
        ];
         return $list;
    }

    public function print_login()
    {
        return $this->get_listing();
    }

    /**
     * Add Plugin settings input to Moodle form.
     *
     * @param MoodleQuickForm $mform Moodle form (passed by reference)
     * @param string $classname repository class name
     */
    public static function type_config_form($mform, $classname = 'repository') {
        parent::type_config_form($mform, $classname);

        $strrequired = get_string('required');

        $actionclass = \core_ai\aiactions\generate_image::class;
        if (class_exists(\core_ai\manager::class)) {
            $manager = \core\di::get(\core_ai\manager::class);
            $allproviders = $manager->get_providers_for_actions([$actionclass], true);
            if (!empty($allproviders[$actionclass])) {
                foreach($allproviders[$actionclass] as $aiprovider) {
                    $aiproviderrecord = $aiprovider->to_record();
                    $options[$aiproviderrecord->id] = $aiproviderrecord->name;
                }
            }
        }

        $options[constants::CLOUDPOODLL_OPTION] = get_string('provider:cloudpoodll', constants::M_COMPONENT);

        // API Provider
        $mform->addElement('select', 'apiprovider', get_string('apiprovider', constants::M_COMPONENT), $options);
        $mform->setType('apiprovider', PARAM_INT);
        $mform->setDefault('apiprovider', constants::CLOUDPOODLL_OPTION);
        $mform->addHelpButton('apiprovider', 'apiprovider', constants::M_COMPONENT);

        // Gemini model used for image editing (Nano Banana).
        $geminimodels = [
            'gemini-2.5-flash-image' => 'Nano Banana (gemini-2.5-flash-image)',
            'gemini-3-pro-image-preview' => 'Nano Banana Pro (gemini-3-pro-image-preview)',
            'gemini-3.1-flash-image-preview' => 'Nano Banana 2 (gemini-3.1-flash-image-preview)',
            'gemini-nano-banana-2.1' => 'Nano Banana 2.1 (gemini-nano-banana-2.1)',
        ];
        $mform->addElement('select', 'geminieditmodel', get_string('geminieditmodel', constants::M_COMPONENT), $geminimodels);
        $mform->setType('geminieditmodel', PARAM_TEXT);
        $mform->setDefault('geminieditmodel', 'gemini-2.5-flash-image');
        $mform->addHelpButton('geminieditmodel', 'geminieditmodel', constants::M_COMPONENT);

        // OpenAI model used for image editing. Only the GPT Image models can edit from a prompt alone.
        $openaimodels = [
            'gpt-image-2.5-flare' => 'GPT Image 2.5 Flare (gpt-image-2.5-flare)',
            'gpt-image-2.5-sunburst' => 'GPT Image 2.5 Sunburst (gpt-image-2.5-sunburst)',
            'gpt-image-1.5' => 'GPT Image 1.5 (gpt-image-1.5)',
        ];
        $mform->addElement('select', 'openaieditmodel', get_string('openaieditmodel', constants::M_COMPONENT), $openaimodels);
        $mform->setType('openaieditmodel', PARAM_TEXT);
        $mform->setDefault('openaieditmodel', 'gpt-image-2.5-flare');
        $mform->addHelpButton('openaieditmodel', 'openaieditmodel', constants::M_COMPONENT);

        // API User.
        $mform->addElement('text', 'apiuser', get_string('apiuser', constants::M_COMPONENT));
        $mform->setType('apiuser', PARAM_TEXT);
        $mform->addHelpButton('apiuser', 'apiuser', constants::M_COMPONENT);
        $mform->hideIf('apiuser', 'apiprovider', 'neq', constants::CLOUDPOODLL_OPTION);

        // API Secret.
        $mform->addElement('text', 'apisecret', get_string('apisecret', constants::M_COMPONENT));
        $mform->setType('apisecret', PARAM_TEXT);
        $mform->addHelpButton('apisecret', 'apisecret', constants::M_COMPONENT);
        $mform->hideIf('apisecret', 'apiprovider', 'neq', constants::CLOUDPOODLL_OPTION);

        // Cloud Poodll Server.
        $mform->addElement('text', 'cloudpoodllserver', get_string('cloudpoodllserver', constants::M_COMPONENT));
        $mform->setType('cloudpoodllserver', PARAM_URL);
        $mform->setDefault('cloudpoodllserver', 'https://cloud.poodll.com');
        $mform->addHelpButton('cloudpoodllserver', 'cloudpoodllserver', constants::M_COMPONENT);
        $mform->hideIf('cloudpoodllserver', 'apiprovider', 'neq', constants::CLOUDPOODLL_OPTION);

        // AWS Region.
        $regions = utils::get_region_options();
        $mform->addElement('select', 'awsregion', get_string('awsregion', constants::M_COMPONENT), $regions);
        $mform->setDefault('awsregion', 'useast1');
        $mform->addHelpButton('awsregion', 'awsregion', constants::M_COMPONENT);
        $mform->hideIf('awsregion', 'apiprovider', 'neq', constants::CLOUDPOODLL_OPTION);
    }

     /**
     * Option names of dropbox plugin.
     *
     * @inheritDocs
     */
    public static function get_type_option_names() {
        return [
                'apiprovider',
                'geminieditmodel',
                'openaieditmodel',
                'cloudpoodllserver',
                'apiuser',
                'apisecret',
                'awsregion',
            ];
    }

    /**
     * Option names of each repository instance.
     *
     * @return array
     */
    public static function get_instance_option_names() {
        return ['mode', 'recorderskin', 'timelimit'];
    }

    /**
     * Instance settings: what the instance does, and how the recorder looks.
     *
     * @param MoodleQuickForm $mform
     */
    public static function instance_config_form($mform) {
        $modes = [
            constants::MODE_IMAGE => get_string('mode_image', constants::M_COMPONENT),
            constants::MODE_AUDIO => get_string('mode_audio', constants::M_COMPONENT),
            constants::MODE_VIDEO => get_string('mode_video', constants::M_COMPONENT),
        ];
        $mform->addElement('select', 'mode', get_string('mode', constants::M_COMPONENT), $modes);
        $mform->setDefault('mode', constants::MODE_IMAGE);
        $mform->addHelpButton('mode', 'mode', constants::M_COMPONENT);

        $skins = recorder::get_skin_options(constants::MODE_AUDIO) + recorder::get_skin_options(constants::MODE_VIDEO);
        $mform->addElement('select', 'recorderskin', get_string('recorderskin', constants::M_COMPONENT), $skins);
        $mform->setDefault('recorderskin', constants::SKIN_FRESH);
        $mform->addHelpButton('recorderskin', 'recorderskin', constants::M_COMPONENT);
        $mform->hideIf('recorderskin', 'mode', 'eq', constants::MODE_IMAGE);

        $mform->addElement(
            'duration',
            'timelimit',
            get_string('timelimit', constants::M_COMPONENT),
            ['optional' => false, 'defaultunit' => MINSECS]
        );
        $mform->setDefault('timelimit', 0);
        $mform->addHelpButton('timelimit', 'timelimit', constants::M_COMPONENT);
        $mform->hideIf('timelimit', 'mode', 'eq', constants::MODE_IMAGE);
    }

    /**
     * Check the recorder skin suits the mode.
     *
     * @param moodleform $mform
     * @param array $data
     * @param array $errors
     * @return array
     */
    public static function instance_form_validation($mform, $data, $errors) {
        $mode = $data['mode'] ?? constants::MODE_IMAGE;
        $skins = recorder::get_skin_options($mode);
        if ($mode !== constants::MODE_IMAGE && !array_key_exists($data['recorderskin'] ?? '', $skins)) {
            $errors['recorderskin'] = get_string('skinnotformode', constants::M_COMPONENT);
        }
        if (isset($data['timelimit']) && $data['timelimit'] < 0) {
            $errors['timelimit'] = get_string('timelimitnegative', constants::M_COMPONENT);
        }
        return $errors;
    }

    /**
     * Render the search (prompt) input box.
     * @return string
     */
    public function print_search()
    {
        global $OUTPUT;

        if ($this->is_recorder()) {
            return $this->print_recorder();
        }

        $currentprompt = optional_param('s', '', PARAM_TEXT);
        $currentstyle = optional_param('imagetype', 'flat vector illustration', PARAM_TEXT);
        $currentimage = optional_param('selectedimage', '', PARAM_RAW);

        $fileslist = $this->fetch_files_list();
        $imagelist = [];
        if ($canedit = $this->can_edit_image()) {
            foreach ($fileslist['list'] as $fileinfo) {
                $imagelist[] = [
                    'title' => $fileinfo['title'] ?? '',
                    'thumbnail' => $fileinfo['thumbnail'] ?? '',
                    'realthumbnail' => $fileinfo['realthumbnail'] ?? '',
                    'selected' => ($currentimage !== '' && ($fileinfo['title'] ?? '') === $currentimage),
                ];
            }
        }

        $styles = [];
        foreach ($this->fetch_image_options() as $option) {
            $styles[] = [
                'value' => $option->value,
                'label' => $option->label,
                'selected' => ($option->value === $currentstyle),
            ];
        }

        $context = [
            'formid' => 'cloudpoodll-imagegen-' . uniqid('', true),
            'prompt' => $currentprompt,
            'imagestyles' => $styles,
            'images' => $imagelist,
            'showclearoption' => !empty($imagelist),
            'nocurrent' => ($currentimage === ''),
            'canedit' => $canedit
        ];

        $out = html_writer::start_div('repository_cloudpoodll_image_prompt_wrapper');
        $out .= $OUTPUT->render_from_template('repository_cloudpoodll/imagegenform', $context);
        $out .= html_writer::end_div();
        return $out;
    }

    /**
     * Render the audio or video recorder.
     *
     * The recorder needs JavaScript, which does not run in HTML added to the file picker, so it lives on
     * its own page in an iframe. When the recording is converted, that page puts its URL in the hidden
     * search field and submits the search form, which calls search().
     *
     * @return string
     */
    protected function print_recorder() {
        global $OUTPUT;

        [$width, $height] = $this->get_recorder()->get_dimensions();
        $url = new moodle_url('/repository/cloudpoodll/recorder.php', [
            'repo_id' => $this->id,
            'ctx_id' => $this->context->id,
        ]);
        $context = [
            'iframeurl' => $url->out(false),
            'width' => $width + 20,
            // Leave room under the recorder for the progress message.
            'height' => $height + 70,
            'title' => get_string('mode_' . $this->get_mode(), constants::M_COMPONENT),
        ];
        return $OUTPUT->render_from_template('repository_cloudpoodll/recorderform', $context);
    }

    /**
     * Download a converted recording into a draft area and present it as a single result.
     *
     * @param array $results empty search results
     * @return array
     */
    protected function search_recording($results) {
        global $OUTPUT, $USER;

        // Re-read the search text as a URL. The file picker cleans it as HTML.
        $mediaurl = optional_param('s', '', PARAM_URL);
        if ($mediaurl === '' || !utils::is_poodll_media_url($mediaurl)) {
            return $results;
        }

        $fileinfo = $this->get_recorder()->download_to_draft($mediaurl);
        if (!$fileinfo) {
            return $results;
        }

        $sourceinfo = [
            'contextid' => context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $fileinfo['draftitemid'],
            'filepath' => '/',
            'filename' => $fileinfo['filename'],
        ];
        $results['list'][] = [
            'title' => $fileinfo['filename'],
            'shorttitle' => $fileinfo['filename'],
            'thumbnail' => $OUTPUT->image_url(file_extension_icon($fileinfo['filename']))->out(false),
            'thumbnail_height' => 64,
            'thumbnail_width' => 64,
            'source' => base64_encode(json_encode($sourceinfo)),
            'url' => $fileinfo['drafturl'],
        ];
        $results['nosearch'] = true;
        return $results;
    }

    /**
     * @return imagegen
     */
    public function get_imagegen() {
        if (!isset($this->_imagegen)) {
            $this->_imagegen = new imagegen($this);
        }
        return $this->_imagegen;
    }

    public function can_edit_image() {
        return $this->get_imagegen()->can_edit_image();
    }

    /**
     * Generate an image from the prompt (search text) and present it as a single result.
     * @param string $searchtext
     * @param int $page
     * @return array
     */
    public function search($searchtext, $page = 0)
    {
        global $USER;

        // Default results structure.
        $results = [
            'list' => [],
            'page' => $page,
            'pages' => 1,
        ];

        if ($this->is_recorder()) {
            return $this->search_recording($results);
        }

        // Get the submitted prompt.
        $prompt = trim($searchtext ?? '');
        $selectedimage = null;

        // No prompt, no results.
        if ($prompt === '') {
            return $results;
        }

        // Get the selected image style.
        $imagetype = optional_param('imagetype', 'flat vector illustration', PARAM_RAW);

        // Get the selected image  (or no image selected to generate new ).
        $selectedimagefilename = optional_param('selectedimage', '', PARAM_RAW);
        if (!empty($selectedimagefilename) && $this->can_edit_image()) {
            $selectedimage  = $this->fetch_file_by_filename($selectedimagefilename);
            // If this fails that is bad, so just return no results
            if (!$selectedimage) {
                return $results;
            }
        }

        $draftid = file_get_unused_draft_itemid();

        // Create or edit image via function.
        if (!empty($selectedimagefilename) && !empty($selectedimage)) {
            $filename = $selectedimagefilename;
            $fileinfo  = $this->get_imagegen()->edit_image(
                $prompt,
                $draftid,
                $selectedimage,
                $selectedimagefilename
            );

        } else {
            $prompt = $prompt . '[NB The image style is: ' . $imagetype . ']';
            $filename = 'imagegen_' . time() . '.png';
            $fileinfo  = $this->get_imagegen()->generate_image(
                $prompt,
                $draftid,
                $filename
            );
        }

        // No fileinfo, no results.
        if (!$fileinfo) {
            return $results;
        }

        // Build the source reference for Moodle file API.
        $sourceinfo = [
            'contextid' => context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $fileinfo['draftitemid'],
            'filepath' => '/',
            'filename' => $fileinfo['filename'],
        ];
        $source = base64_encode(json_encode($sourceinfo));

        $results['list'][] = [
            'title' => $fileinfo['filename'],
            'shorttitle' => $fileinfo['filename'],
            'thumbnail' => $fileinfo['drafturl'],
            'thumbnail_height' => 256,
            'thumbnail_width' => 256,
            'source' => $source,
            'url' => $fileinfo['drafturl'],
        ];

        // This prevents showing the really big search results box. They can press a button to go back.
        $results['nosearch'] = true;

        // Return the results.
        return $results;
    }


    public function fetch_image_options()
    {
        return [
            (object) ['value' => 'flat vector illustration', 'label' => get_string('imagetype_flatvectorillustration', constants::M_COMPONENT)],
            (object) ['value' => 'cartoon', 'label' => get_string('imagetype_cartoon', constants::M_COMPONENT)],
            (object) ['value' => 'photorealistic', 'label' => get_string('imagetype_photorealistic', constants::M_COMPONENT)],
            (object) ['value' => 'digital painting', 'label' => get_string('imagetype_digitalpainting', constants::M_COMPONENT)],
            (object) ['value' => 'line drawing', 'label' => get_string('imagetype_linedrawing', constants::M_COMPONENT)],
            (object) ['value' => '3d render', 'label' => get_string('imagetype_3drender', constants::M_COMPONENT)],
            (object) ['value' => 'infographic', 'label' => get_string('imagetype_infographic', constants::M_COMPONENT)],
        ];
    }

    public function fetch_file_by_filename($filename)
    {
        global $USER, $OUTPUT;

        $itemid = optional_param('itemid', 0, PARAM_INT);
        $context = context_user::instance($USER->id);
        $fs = get_file_storage();
        $file = $fs->get_file(
            $context->id,
            'user',
            'draft',
            $itemid,
            '/',
            $filename
        );
        return $file;
    }

    /**
     *
     * Fetch the list of existing mages from user draft area.
     * @return array
     */
    public function fetch_files_list()
    {
        global $USER, $OUTPUT;

        $result = [
            'list' => [],
        ];

        $itemid = optional_param('itemid', 0, PARAM_INT);
        if (empty($itemid)) {
            return $result;
        }

        $context = context_user::instance($USER->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'user',
            'draft',
            $itemid,
            'timemodified DESC',
            false,
            0,
            0
        );

        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            // continue if the file extension is not png / jpeg / jpg /webp
            $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
            if (!in_array($extension, ['png', 'jpeg', 'jpg', 'webp'])) {
                continue;
            }


            $fileurl = moodle_url::make_draftfile_url($itemid, $file->get_filepath(), $file->get_filename());
            $entry = [
                'title' => $file->get_filename(),
                'thumbnail' => $OUTPUT->image_url(file_file_icon($file))->out(false),
            ];

            if ($imageinfo = $file->get_imageinfo()) {
                $entry['realthumbnail'] = $fileurl->out(false, [
                    'preview' => 'thumb',
                    'oid' => $file->get_timemodified(),
                ]);
            }

            $result['list'][] = $entry;
        }

        return $result;
    }

    /**
     * Create an image, an audio and a video instance when the repository is enabled.
     * @return bool
     */
    public static function plugin_init()
    {
        utils::create_default_instances();
        return true;
    }

    /**
     * No external auth required.
     * @return bool
     */
    public function check_login()
    {
        return true; // False shows print_login form.
    }
}
