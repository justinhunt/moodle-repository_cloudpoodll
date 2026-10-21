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
 * Upgrade steps for repository_cloudpoodll.
 *
 * @package    repository_cloudpoodll
 * @copyright  2025 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade repository_cloudpoodll.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_repository_cloudpoodll_upgrade($oldversion) {
    if ($oldversion < 2026101600) {
        // Existing instances become image instances, and audio and video recording instances are added.
        \repository_cloudpoodll\utils::create_default_instances();
        upgrade_plugin_savepoint(true, 2026101600, 'repository', 'cloudpoodll');
    }

    return true;
}
