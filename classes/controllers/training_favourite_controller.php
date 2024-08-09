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
 * Training favourite controller
 *
 * @package    block_mytrainings
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mytrainings;

use local_mentor_core\controller_base;

require_once(__DIR__ . '/../../../../config.php');

require_login();

require_once($CFG->dirroot . '/local/mentor_core/api/training.php');
require_once($CFG->dirroot . '/local/mentor_core/classes/controllers/controller_base.php');

defined('MOODLE_INTERNAL') || die();

class training_favourite_controller extends controller_base {

    /**
     * Execute action
     *
     * @return array
     */
    public function execute() {

        try {
            $action = $this->get_param('action');

            switch ($action) {

                case 'add_favourite' :
                    $trainingid = $this->get_param('trainingid', PARAM_INT);
                    return $this->success($this->add_favourite($trainingid));

                case 'remove_favourite' :
                    $trainingid = $this->get_param('trainingid', PARAM_INT);
                    return $this->success($this->remove_favourite($trainingid));

                default:
                    $trainingid = $this->get_param('trainingid', PARAM_INT);
                    return $this->success($this->$action($trainingid));
            }

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

    }

    /**
     * Add training to user's favourite
     *
     * @param int $trainingid
     * @return int|bool
     * @throws \dml_exception
     */
    public static function add_favourite($trainingid) {
        return \local_mentor_core\training_api::add_trainings_user_designer_favourite($trainingid);
    }

    /**
     * Remove training to user's favourite
     *
     * @param int $trainingid
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function remove_favourite($trainingid) {
        return \local_mentor_core\training_api::remove_trainings_user_designer_favourite($trainingid);
    }

}
