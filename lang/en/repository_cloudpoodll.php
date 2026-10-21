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
 * Strings for repository_cloudpoodll.
 *
 * @package    repository_cloudpoodll
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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

$string['apiprovider'] = 'Api provider';
$string['apiprovider_help'] = 'Choose api provider to use in image generation';
$string['configplugin'] = 'Cloud Poodll configuration';
$string['pluginname'] = 'Cloud Poodll';
$string['pluginname_help'] = 'Generate images from text prompts, or record audio and video, and insert them directly via the file picker.';
$string['privacy:metadata:cloudpoodll'] = 'Image prompts, images and recordings are sent to Cloud Poodll for processing. Recordings are deleted from Cloud Poodll after one day.';
$string['privacy:metadata:cloudpoodll:media'] = 'Images to edit, and audio and video recordings to convert.';
$string['privacy:metadata:cloudpoodll:owner'] = 'A hash of the username, used to identify who made the request.';
$string['privacy:metadata:cloudpoodll:prompt'] = 'The text prompt used to generate or edit an image.';
$string['configapikey'] = 'Cloud Poodll API key';
$string['configapikey_desc'] = 'Optional: If set, used when calling the external service. Not required for the built-in demo generator.';
$string['enterprompt'] = 'Enter a prompt...';
$string['promptplaceholdercanedit'] = 'Enter an image description (generate) or describe changes to the selected image (modify).';
$string['promptplaceholder'] = 'Enter an image description.';
$string['cap:view'] = 'Use the Cloud Poodll repository';
$string['cloudpoodll:view'] = 'Use the Cloud Poodll repository';
$string['makeimage'] = 'Make image';
$string['imagetype'] = 'Image type';
$string['imagetype_flatvectorillustration'] = 'Flat vector illustration';
$string['imagetype_cartoon'] = 'Cartoon';
$string['imagetype_photorealistic'] = 'Photorealistic';
$string['imagetype_digitalpainting'] = 'Digital painting';
$string['imagetype_linedrawing'] = 'Line drawing';
$string['imagetype_3drender'] = '3D render';
$string['imagetype_infographic'] = 'Infographic';
$string['noimagesmessage'] = 'No generated images available. Please generate an image first.';
$string['noimagelabel'] = 'Do not use an existing image';
$string['provider:cloudpoodll'] = 'Cloud poodll';
$string['selectimage'] = 'Select image';
$string['imagepreview'] = 'Image preview';
$string['submitbutton'] = 'Make Image';

// Admin settings.
$string['apiuser'] = 'Poodll API User';
$string['apiuser_help'] = 'The Poodll account username that authorises Poodll on this site.';
$string['apisecret'] = 'Poodll API Secret';
$string['apisecret_help'] = 'The Poodll API secret. See <a href="https://support.poodll.com/support/solutions/articles/19000083076-cloud-poodll-api-secret">here</a> for more details.';
$string['cloudpoodllserver'] = 'Cloud Poodll Server';
$string['cloudpoodllserver_help'] = 'The server to use for Cloud Poodll. Only change this if Poodll has provided a different one.';
$string['awsregion'] = 'AWS Region';
$string['awsregion_help'] = 'The AWS region where your Poodll account is configured.';

// Region options.
$string['useast1'] = 'US East';
$string['tokyo'] = 'Tokyo, Japan';
$string['sydney'] = 'Sydney, Australia';
$string['dublin'] = 'Dublin, Ireland';
$string['ottawa'] = 'Ottawa, Canada';
$string['frankfurt'] = 'Frankfurt, Germany';
$string['london'] = 'London, U.K';
$string['saopaulo'] = 'Sao Paulo, Brazil';
$string['mumbai'] = 'Mumbai, India';
$string['singapore'] = 'Singapore';
$string['bahrain'] = 'Bahrain';
$string['capetown'] = 'Capetown, South Africa';
$string['ningxia'] = 'Ningxia, China';

$string['expiredays'] = 'Days to keep file';
$string['displaysubs'] = '{$a->subscriptionname} : expires {$a->expiredate}';
$string['noapiuser'] = "No API user entered. CloudPoodll repository  will not work correctly.";
$string['noapisecret'] = "No API secret entered. CloudPoodll repository  will not work correctly.";
$string['credentialsinvalid'] = "The API user and secret entered could not be used to get access. Please check them.";
$string['appauthorised'] = "CloudPoodll repository is authorised for this site.";
$string['appnotauthorised'] = "CloudPoodll repository is NOT authorised for this site.";
$string['refreshtoken'] = "Refresh license information";
$string['notokenincache'] = "Refresh to see license information. Contact Poodll support if there is a problem.";
// these errors are displayed on activity page
$string['nocredentials'] = 'API user and secret not entered. Please enter them on <a href="{$a}">the settings page.</a> You can get them from <a href="https://poodll.com/member">Poodll.com.</a>';
$string['novalidcredentials'] = 'API user and secret were rejected and could not gain access. Please check them on <a href="{$a}">the settings page.</a> You can get them from <a href="https://poodll.com/member">Poodll.com.</a>';
$string['nosubscriptions'] = "There is no current subscription for this site/plugin.";

// Repository instances.
$string['instancename_audio'] = 'Cloud Poodll Audio';
$string['instancename_image'] = 'Cloud Poodll Image';
$string['instancename_video'] = 'Cloud Poodll Video';
$string['mode'] = 'Mode';
$string['mode_audio'] = 'Record audio';
$string['mode_help'] = 'What this repository does: generate and edit images from text prompts, or record audio (MP3) or video (MP4).';
$string['mode_image'] = 'Generate images';
$string['mode_video'] = 'Record video';
$string['recorderskin'] = 'Recorder skin';
$string['recorderskin_help'] = 'The look of the recorder. Fresh is for audio only, and Screen (screen recording) is for video only.';
$string['skin123'] = '1-2-3';
$string['skinbmr'] = 'Burnt Rose';
$string['skinfresh'] = 'Fresh';
$string['skinnotformode'] = 'This recorder skin cannot be used for this mode.';
$string['skinonce'] = 'Once';
$string['skinplain'] = 'Plain';
$string['skinscreen'] = 'Screen recorder';
$string['timelimit'] = 'Recording time limit';
$string['timelimit_help'] = 'The longest recording allowed. Set to 0 for no limit.';
$string['timelimitnegative'] = 'The time limit cannot be negative.';

// Recorder progress.
$string['recordingconverting'] = 'Converting your recording. This can take a minute or two...';
$string['recordingfailed'] = 'Sorry, there was a problem with the recording. Please try again.';
$string['recordingnofilepicker'] = 'The recording is ready, but the file picker could not be found. Please close it and try again.';
$string['recordingready'] = 'Your recording is ready. Fetching it...';
$string['recordingstarted'] = 'Recording...';
$string['recordingtimeout'] = 'The recording is taking too long to convert. Please try again.';
$string['geminieditmodel'] = 'Gemini image editing model';
$string['geminieditmodel_help'] = 'The Gemini (Nano Banana) model used when editing images with the Gemini AI provider.';
$string['openaieditmodel'] = 'OpenAI image editing model';
$string['openaieditmodel_help'] = 'The GPT Image model used when editing images with the OpenAI AI provider. Editing from a text prompt needs GPT Image 2 or later, so choosing GPT Image 1.5 turns image editing off. Image generation still uses the model set on the OpenAI provider.';
