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
 * Block Mytrainings lib tests
 *
 * @package    block_mytrainings
 * @copyright  2022 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

use block_mytrainings\helper\testhelper;

require_once($CFG->dirroot . '/blocks/mytrainings/lib.php');

class block_mytrainings_lib_testcase extends advanced_testcase {
    /**
     * Reset the singletons
     *
     * @throws ReflectionException
     */
    public function reset_singletons() {
        // Reset the mentor core db interface singleton.
        $dbinterface = \local_mentor_core\database_interface::get_instance();
        $reflection = new ReflectionClass($dbinterface);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true); // Now we can modify that :).
        $instance->setValue(null, null); // Instance is gone.
        $instance->setAccessible(false); // Clean up.

        \local_mentor_core\training_api::clear_cache();
        \local_mentor_core\session_api::clear_cache();
    }

    /**
     * Test block_mytrainings_convert_for_template
     *
     */
    public function test_block_mytrainings_convert_for_template_ok() {
        global $USER, $CFG;

        $this->resetAfterTest(true);
        $this->reset_singletons();

        self::setAdminUser();

        $course = self::getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $trainingscourse = new \stdClass();
        $trainingscourse->id = 1064; // False id.
        $trainingscourse->courseid = $course->id;
        $trainingscourse->courseformat = 'topics';
        $trainingscourse->certifying = '0';
        $trainingscourse->contextid = $coursecontext->id;
        $trainingscourse->favouritedesignerdata = null;
        block_mytrainings_convert_for_template($trainingscourse, $USER);

        // No update.
        self::assertEquals($trainingscourse->id, 1064);
        self::assertEquals($trainingscourse->courseid, $course->id);
        self::assertEquals($trainingscourse->courseformat, 'topics');
        self::assertEquals($trainingscourse->certifying, '0');
        self::assertEquals($trainingscourse->contextid, $coursecontext->id);
        self::assertNull($trainingscourse->favouritedesignerdata);

        // New data.
        self::assertEquals(
            $trainingscourse->courseurl,
            $CFG->wwwroot . '/course/view.php?id=' . $course->id
        );
        self::assertFalse($trainingscourse->isreviewer);
        self::assertFalse($trainingscourse->iscertifying);
        self::assertNull($trainingscourse->thumbnail);

        self::resetAllData();
    }

    /**
     * Test block_mytrainings_convert_for_template is reviewer
     *
     * @covers ::block_mytrainings_convert_for_template
     */
    public function test_block_mytrainings_convert_for_template_ok_is_reviewer() {
        global $USER;

        $this->resetAfterTest(true);
        $this->reset_singletons();

        testhelper::create_default_entity($this);
        self::setAdminUser();

        $course = self::getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $trainingscourse = new \stdClass();
        $trainingscourse->id = 1064; // False id.
        $trainingscourse->courseid = $course->id;
        $trainingscourse->courseformat = 'topics';
        $trainingscourse->certifying = '0';
        $trainingscourse->contextid = $coursecontext->id;
        $trainingscourse->favouritedesignerdata = null;

        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        block_mytrainings_convert_for_template($trainingscourse, $USER);

        self::assertTrue($trainingscourse->isreviewer);

        self::resetAllData();
    }

    /**
     * Test block_mytrainings_convert_for_template is certifying
     *
     * @covers ::block_mytrainings_convert_for_template
     */
    public function test_block_mytrainings_convert_for_template_ok_is_certifying() {
        global $USER;

        $this->resetAfterTest(true);
        $this->reset_singletons();

        self::setAdminUser();

        $course = self::getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $trainingscourse = new \stdClass();
        $trainingscourse->id = 1064; // False id.
        $trainingscourse->courseid = $course->id;
        $trainingscourse->courseformat = 'topics';
        $trainingscourse->certifying = '1';
        $trainingscourse->contextid = $coursecontext->id;
        $trainingscourse->favouritedesignerdata = null;
        block_mytrainings_convert_for_template($trainingscourse, $USER);

        self::assertTrue($trainingscourse->iscertifying);

        self::resetAllData();
    }

    /**
     * Test block_mytrainings_convert_for_template has thumbnail
     *
     * @covers ::block_mytrainings_convert_for_template
     */
    public function test_block_mytrainings_convert_for_template_ok_has_thumbnail() {
        global $USER, $CFG;

        $this->resetAfterTest(true);
        $this->reset_singletons();

        self::setAdminUser();

        $course = self::getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $fs = get_file_storage();

        $contextid = $coursecontext->id;
        $component = 'local_trainings';
        $filearea = 'thumbnail';
        $itemid = 1064;

        $filerecord = new stdClass();
        $filerecord->contextid = $contextid;
        $filerecord->component = $component;
        $filerecord->filearea = $filearea;
        $filerecord->itemid = $itemid;
        $filerecord->filepath = '/';
        $filerecord->filename = 'logo.png';

        $filepath = $CFG->dirroot . '/blocks/mytrainings/tests/pix/logo.png';

        // Create file.
        $fs->create_file_from_pathname($filerecord, $filepath);

        $trainingscourse = new \stdClass();
        $trainingscourse->id = 1064; // False id.
        $trainingscourse->courseid = $course->id;
        $trainingscourse->courseformat = 'topics';
        $trainingscourse->certifying = '0';
        $trainingscourse->contextid = $coursecontext->id;
        $trainingscourse->favouritedesignerdata = null;
        block_mytrainings_convert_for_template($trainingscourse, $USER);

        self::assertEquals(
            $trainingscourse->thumbnail,
            \moodle_url::make_pluginfile_url(
                $filerecord->contextid,
                $filerecord->component,
                $filerecord->filearea,
                $filerecord->itemid,
                $filerecord->filepath,
                $filerecord->filename
            )->out()
        );

        self::resetAllData();
    }

    /**
     * Test block_mytrainings_convert_for_template is favorite designer
     *
     * @covers ::block_mytrainings_convert_for_template
     */
    public function test_block_mytrainings_convert_for_template_ok_is_favorite_designer() {
        global $USER, $DB;

        $this->resetAfterTest(true);
        $this->reset_singletons();

        self::setAdminUser();

        $course = self::getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $trainingscourse = new \stdClass();
        $trainingscourse->id = 1064; // False id.
        $trainingscourse->courseid = $course->id;
        $trainingscourse->courseformat = 'topics';
        $trainingscourse->certifying = '0';
        $trainingscourse->contextid = $coursecontext->id;
        $trainingscourse->favouritedesignerdata = true;

        $favourite = new stdClass();
        $favourite->component = 'local_trainings';
        $favourite->itemtype = \local_mentor_core\training::FAVOURITE_DESIGNER;
        $favourite->itemid = 1064;
        $favourite->contextid = $coursecontext->id;
        $favourite->userid = $USER->id;
        $favourite->timecreated = time();
        $favourite->timemodified = time();
        $favouriteid = $DB->insert_record('favourite', $favourite);

        block_mytrainings_convert_for_template($trainingscourse, $USER);

        $trainingfavouritedata = $trainingscourse->favouritedesignerdata;

        self::assertEquals($trainingfavouritedata->id, $favouriteid);
        self::assertEquals($trainingfavouritedata->component, $favourite->component);
        self::assertEquals($trainingfavouritedata->itemtype, $favourite->itemtype);
        self::assertEquals($trainingfavouritedata->itemid, $favourite->itemid);
        self::assertEquals($trainingfavouritedata->contextid, $favourite->contextid);
        self::assertEquals($trainingfavouritedata->userid, $favourite->userid);

        self::resetAllData();
    }

    /**
     * Test block_mytrainings_sort_by_user_favorite
     *
     * @covers ::block_mytrainings_sort_by_user_favorite
     */
    public function test_block_mytrainings_sort_by_user_favorite_ok() {
        $this->resetAfterTest(true);
        $this->reset_singletons();

        // No favourite designer data.
        $trainingcourses = [];
        $trainingcourses[0] = new stdClass();
        $trainingcourses[0]->favouritedesignerdata = false;
        $trainingcourses[0]->id = 0;
        $trainingcourses[1] = new stdClass();
        $trainingcourses[1]->id = 1;
        $trainingcourses[1]->favouritedesignerdata = false;

        block_mytrainings_sort_by_user_favorite($trainingcourses);

        self::assertEquals($trainingcourses[0]->id, 0);
        self::assertEquals($trainingcourses[1]->id, 1);

        // Favourite designer data to first element.
        $trainingcourses = [];
        $trainingcourses[0] = new stdClass();
        $trainingcourses[0]->id = 0;
        $trainingcourses[0]->favouritedesignerdata = new stdClass();
        $trainingcourses[1] = new stdClass();
        $trainingcourses[1]->id = 1;
        $trainingcourses[1]->favouritedesignerdata = false;

        block_mytrainings_sort_by_user_favorite($trainingcourses);

        self::assertEquals($trainingcourses[0]->id, 0);
        self::assertEquals($trainingcourses[1]->id, 1);

        // Favourite designer data to second element.
        $trainingcourses = [];
        $trainingcourses[0] = new stdClass();
        $trainingcourses[0]->id = 0;
        $trainingcourses[0]->favouritedesignerdata = false;
        $trainingcourses[1] = new stdClass();
        $trainingcourses[1]->id = 1;
        $trainingcourses[1]->favouritedesignerdata = new stdClass();

        block_mytrainings_sort_by_user_favorite($trainingcourses);

        self::assertEquals($trainingcourses[0]->id, 1);
        self::assertEquals($trainingcourses[1]->id, 0);

        // Favourite designer data to all element, first create after.
        $trainingcourses = [];
        $trainingcourses[0] = new stdClass();
        $trainingcourses[0]->id = 0;
        $trainingcourses[0]->favouritedesignerdata = new stdClass();
        $trainingcourses[0]->favouritedesignerdata->timecreated = time() + 1000;
        $trainingcourses[1] = new stdClass();
        $trainingcourses[1]->id = 1;
        $trainingcourses[1]->favouritedesignerdata = new stdClass();
        $trainingcourses[1]->favouritedesignerdata->timecreated = time();

        block_mytrainings_sort_by_user_favorite($trainingcourses);

        self::assertEquals($trainingcourses[0]->id, 0);
        self::assertEquals($trainingcourses[1]->id, 1);

        // Favourite designer data to all element, second create after.
        $trainingcourses = [];
        $trainingcourses[0] = new stdClass();
        $trainingcourses[0]->id = 0;
        $trainingcourses[0]->favouritedesignerdata = new stdClass();
        $trainingcourses[0]->favouritedesignerdata->timecreated = time();
        $trainingcourses[1] = new stdClass();
        $trainingcourses[1]->id = 1;
        $trainingcourses[1]->favouritedesignerdata = new stdClass();
        $trainingcourses[1]->favouritedesignerdata->timecreated = time() + 1000;

        block_mytrainings_sort_by_user_favorite($trainingcourses);

        self::assertEquals($trainingcourses[0]->id, 1);
        self::assertEquals($trainingcourses[1]->id, 0);

        self::resetAllData();
    }
}
