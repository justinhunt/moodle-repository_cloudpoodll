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

/**
 * Runs the Cloud Poodll recorder on the recorder page and hands the converted recording to the file picker.
 *
 * The recorder uploads the recording to Cloud Poodll, which converts it to MP3/MP4. Once the converted
 * file exists, its URL is put in the file picker's search field and the search form is submitted. The
 * repository's search() then downloads the file into the draft area.
 *
 * @module     repository_cloudpoodll/recorder
 * @copyright  2025 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import CloudPoodll from 'repository_cloudpoodll/cloudpoodll';
import Ajax from 'core/ajax';
import Log from 'core/log';
import {getString} from 'core/str';

const SELECTORS = {
    status: '.repo-cp-recorder-status',
    spinner: '.repo-cp-recorder-spinner',
    message: '.repo-cp-recorder-message',
};

/**
 * Show a progress or error message under the recorder.
 *
 * @param {string} stringid lang string id
 * @param {boolean} busy show the spinner
 */
const showStatus = async(stringid, busy) => {
    const status = document.querySelector(SELECTORS.status);
    if (!status) {
        return;
    }
    status.querySelector(SELECTORS.message).textContent = await getString(stringid, 'repository_cloudpoodll');
    status.querySelector(SELECTORS.spinner).hidden = !busy;
    status.hidden = false;
};

/**
 * Put the recording URL in the file picker's search field and submit the search form.
 *
 * The recorder page is in an iframe inside that form, and on the same site, so it can reach it directly.
 *
 * @param {string} mediaurl
 * @returns {boolean} false if the file picker could not be found
 */
const submitToFilePicker = (mediaurl) => {
    const frame = window.frameElement;
    const form = frame ? frame.closest('form') : null;
    const input = form ? form.querySelector('input[name="s"]') : null;
    if (!input) {
        return false;
    }
    input.value = mediaurl;
    // The file picker listens for the submit event and sends the search itself.
    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
    } else {
        form.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
    }
    return true;
};

/**
 * Ask the server, repeatedly, whether the converted recording exists yet.
 *
 * @param {object} opts page options
 * @param {string} mediaurl
 */
const waitForConversion = (opts, mediaurl) => {
    const giveupat = Date.now() + opts.timeout * 1000;

    const check = async() => {
        let response;
        try {
            response = await Ajax.call([{
                methodname: 'repository_cloudpoodll_check_media_ready',
                args: {repositoryid: opts.repositoryid, contextid: opts.contextid, mediaurl: mediaurl},
            }])[0];
        } catch (error) {
            // A failed check is not fatal, the next one may work.
            Log.debug(error);
            response = {ready: false, valid: true};
        }

        if (!response.valid) {
            showStatus('recordingfailed', false);
        } else if (response.ready) {
            showStatus('recordingready', true);
            if (!submitToFilePicker(mediaurl)) {
                showStatus('recordingnofilepicker', false);
            }
        } else if (Date.now() > giveupat) {
            showStatus('recordingtimeout', false);
        } else {
            setTimeout(check, opts.pollinterval * 1000);
        }
    };
    check();
};

/**
 * Load the recorder and listen for its events.
 *
 * @param {object} opts recorderid, repositoryid, contextid, timeout and pollinterval
 */
export const init = (opts) => {
    let converting = false;

    CloudPoodll.theCallback = (evt) => {
        switch (evt.type) {
            case 'recording':
                if (evt.action === 'started') {
                    showStatus('recordingstarted', false);
                }
                break;
            case 'awaitingprocessing':
                // The upload is finished and the conversion has been queued.
                if (!converting && evt.mediaurl) {
                    converting = true;
                    showStatus('recordingconverting', true);
                    waitForConversion(opts, evt.mediaurl);
                }
                break;
            case 'error':
                Log.debug(evt.message);
                showStatus('recordingfailed', false);
                break;
        }
    };
    CloudPoodll.createRecorder(opts.recorderid);
    CloudPoodll.initEvents();
};
