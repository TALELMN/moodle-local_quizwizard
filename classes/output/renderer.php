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

/**
 * Renderer for Quiz Wizard.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin renderer.
 */
class renderer extends \plugin_renderer_base {

    /**
     * Render dashboard page.
     *
     * @param dashboard_page $page
     * @return string
     */
    public function render_dashboard_page(dashboard_page $page): string {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_quizwizard/dashboard', $data);
    }

    /**
     * Render wizard page.
     *
     * @param wizard_page $page
     * @return string
     */
    public function render_wizard_page(wizard_page $page): string {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_quizwizard/wizard', $data);
    }

    /**
     * Render preview page.
     *
     * @param preview_page $page
     * @return string
     */
    public function render_preview_page(preview_page $page): string {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_quizwizard/preview', $data);
    }
}
