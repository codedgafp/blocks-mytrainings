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
 * mytrainings block rendrer
 *
 * public function get_training_sheet_template($defaulttemplate) {
 * $specialization = specialization::get_instance();
 * return $specialization->get_specialization('get_training_sheet_template', $defaulttemplate);
 * }
 *
 * @package    block_mytrainings
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     rcolet <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mytrainings\output;

use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    /**
     * Return the main content for the block mytrainings.
     *
     * @param mytrainings $mytrainings
     * @return string HTML string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function render_mytrainings(mytrainings $mytrainings) {
        $exportfortemplate = $mytrainings->export_for_template($this);

        // Hide the block if no training is being designed.
        if ($exportfortemplate->trainingscount === 0) {
            return null;
        }

        // Set training data as json into a hidden div.
        $trainingsjson = htmlspecialchars(json_encode($exportfortemplate->trainingsheets, JSON_HEX_TAG), ENT_QUOTES, 'UTF-8');
        $template = '<div id="available-trainings" style="display: none">' . $trainingsjson . '</div>';

        // Return the template block with data.
        return $template . $this->render_from_template('block_mytrainings/mytrainings', $exportfortemplate);
    }
}
