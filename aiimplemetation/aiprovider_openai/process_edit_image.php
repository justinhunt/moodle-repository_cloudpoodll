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

namespace aiprovider_openai;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class process_edit_image
 *
 * Edits an image from a text prompt using the OpenAI images/edits endpoint (GPT Image models).
 *
 * @package    repository_cloudpoodll
 * @copyright  2026 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_edit_image extends process_generate_image {
    /**
     * Create the request object to send to the API.
     *
     * @param string $userid The user id making the request.
     * @return RequestInterface
     */
    #[\Override]
    protected function create_request_object(string $userid): RequestInterface {
        /** @var \stored_file $storedfile */
        $storedfile = $this->action->get_storedfile();

        $elements = [
            ['name' => 'model', 'contents' => $this->get_model()],
            ['name' => 'prompt', 'contents' => $this->action->get_configuration('prompttext')],
            ['name' => 'user', 'contents' => $userid],
            [
                'name' => 'image',
                'contents' => $storedfile->get_content(),
                'filename' => $storedfile->get_filename(),
                'headers' => ['Content-Type' => $storedfile->get_mimetype()],
            ],
        ];

        $boundary = uniqid('cloudpoodll', true);
        return new Request(
            method: 'POST',
            uri: '',
            headers: ['Content-Type' => 'multipart/form-data; boundary=' . $boundary],
            body: new MultipartStream($elements, $boundary),
        );
    }

    /**
     * Get the endpoint URI.
     *
     * The provider stores its settings under generate_image, not edit_image, and the configured
     * endpoint is the generations one, so swap it for edits.
     *
     * @return UriInterface
     */
    #[\Override]
    protected function get_endpoint(): UriInterface {
        $settings = $this->provider->actionconfig[\core_ai\aiactions\generate_image::class]['settings'] ?? [];
        $endpoint = $settings['endpoint'] ?? 'https://api.openai.com/v1/images/generations';
        return new Uri(str_replace('/images/generations', '/images/edits', $endpoint));
    }

    /**
     * Get the name of the model to use. Only the GPT Image models can edit from a prompt alone.
     *
     * @return string
     */
    #[\Override]
    protected function get_model(): string {
        $model = get_config(\repository_cloudpoodll\constants::M_SHORTNAME, 'openaieditmodel');
        return empty($model) ? 'gpt-image-2.5-flare' : $model;
    }
}
