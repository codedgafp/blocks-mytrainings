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
 * Plugin library
 *
 * @package    block_mytrainings
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *
 *
 * @param int $userid
 * @param string $searchText
 * @return array
 * @throws dml_exception
 */
function block_mytrainings_get_all_user_trainings($userid,$searchText = null) {
    global $DB;

    $db = \local_mentor_core\database_interface::get_instance();

    // Get all hidden categories with their children.
    $hiddencondition = '';
    if ($allhiddencategoriesids = $db->get_hidden_categories()) {
        $hiddencondition .= 'AND c.category NOT IN (' . $allhiddencategoriesids . ')';
    }

    
    $searchConditions = "";
    if(!is_null($searchText))
    {
        
        $columns = ["t.typicaljob", "t.skills", "t.idsirh", "t.producingorganization", "t.producerorganizationshortname",
        "c.fullname", "c.summary", "t.courseshortname", "t.traininggoal", "t.catchphrase", "name"]; 
       
        $searchConditions .= "  AND ( (";

        $searchTextArray = explode(",",$searchText);
        foreach($columns as $keyColumn => $columnValue)
        {
           
                if ($keyColumn === array_key_last($columns)) {
                    $searchConditions .= " cc.parent IN (SELECT id
                                        FROM {course_categories}
                                        WHERE idnumber IS NOT NULL 
                                         AND ";
                    foreach ($searchTextArray as $keySearchText=>$valueSearchText)
                    { 
                        if( $keySearchText === 0){
                            $searchConditions .= "unaccent(lower(".$columnValue .")) like lower('%".$valueSearchText."%') 
                            OR unaccent(lower(idnumber)) like lower('%".$valueSearchText."%') 
                            ";
                        }else{
                            $searchConditions .= " AND unaccent(lower(".$columnValue .")) like lower('%".$valueSearchText."%') 
                             OR unaccent(lower(idnumber)) like lower('%".$valueSearchText."%') ";
                        } 
                    }
                }else{
                    foreach ($searchTextArray as $keySearchText=>$valueSearchText)
                    { 
                        if( $keySearchText === 0){
                            $searchConditions .= "unaccent(lower(".$columnValue .")) like lower('%".$valueSearchText."%') ";
                        }else{
                            $searchConditions .= " AND unaccent(lower(".$columnValue .")) like lower('%".$valueSearchText."%') ";
                        } 
                    } 
                }
                      
            
            
            if ($keyColumn != array_key_last($columns)) {
                $searchConditions .= " ) OR (";
            }
        }
      
        $searchConditions .= " ))) ";
    
    }
    
    $trainings = $DB->get_records_sql('
            SELECT t.*,
                   c.id as courseid,
                   c.fullname as name,
                   c.format as courseformat,
                   f.id as favouritedesignerdata,
                   ct.id as contextid,
                   c.category,
                   cc.path
            FROM {user_enrolments} ue
            JOIN {enrol} e ON ue.enrolid = e.id
            JOIN {course} c ON c.id = e.courseid
            JOIN {training} t ON t.courseshortname = c.shortname
            JOIN {course_categories} cc ON cc.id = c.category
            JOIN {context} ct ON ct.instanceid = c.id
            LEFT JOIN {favourite} f ON f.contextid = ct.id AND
                f.userid = ue.userid AND
                f.itemid = t.id AND
                f.component = \'local_trainings\' AND
                f.itemtype = :itemtype AND
                f.itemid = t.id
            WHERE ct.contextlevel = :level AND
                ue.userid = :userid AND
                ue.status = 0
                ' . $hiddencondition . '
                ' . $searchConditions . '
         
            GROUP BY t.id, c.id, f.id, ct.id, cc.path 
            ORDER BY c.timecreated DESC
        ', [
        'level' => CONTEXT_COURSE,
        'userid' => $userid,
        'itemtype' => \local_mentor_core\training::FAVOURITE_DESIGNER,
    ]);

    
    if (empty($trainings)) {
        return [];
    }

    $alltrainings = [];
    $trainingsmaincategoryid = [];

    foreach ($trainings as $training) {
        $catgeorypath = explode('/', $training->path);
        $trainingsmaincategoryid[$training->id] = $catgeorypath[1];
    }

    $trainingsmaincategoryname = $DB->get_records_sql('
        SELECT id, idnumber
        FROM {course_categories}
        WHERE id IN (' . implode(',', $trainingsmaincategoryid) . ')
    ');

    // Add entityname to training object => to improve.
    foreach ($trainings as $training) {
        $categoryid = $trainingsmaincategoryid[$training->id];
        $training->entityname = $trainingsmaincategoryname[$categoryid]->idnumber;
        $alltrainings[$training->id] = $training;
    }

    return $alltrainings;

}

/**
 * @param stdClass $trainingcourse
 * @param stdClass $user
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function block_mytrainings_convert_for_template(&$trainingcourse, $user) {
    $db = \local_mentor_core\database_interface::get_instance();

    $course = new stdClass();
    $course->id = $trainingcourse->courseid;
    $course->format = $trainingcourse->courseformat;
    $trainingcourse->courseurl = local_mentor_core_get_course_url($course, false);

    // Check if the user can review the training.
    $trainingcontext = \context_course::instance($trainingcourse->courseid);
    $trainingcourse->isreviewer = false;
    if (!has_capability('local/trainings:update', $trainingcontext, $user)) {
        $trainingcourse->isreviewer = true;
    }

    $trainingcourse->iscertifying = ($trainingcourse->certifying != '0');

    // Set the training thumbnail.
    if ($thumbnail = $db->get_file_from_database($trainingcourse->contextid,
        'local_trainings',
        'thumbnail',
        $trainingcourse->id)) {
        $trainingcourse->thumbnail = \moodle_url::make_pluginfile_url(
            $thumbnail->contextid,
            $thumbnail->component,
            $thumbnail->filearea,
            $thumbnail->itemid,
            $thumbnail->filepath,
            $thumbnail->filename
        )->out();
    } else {
        $trainingcourse->thumbnail = null;
    }

    if ($trainingcourse->favouritedesignerdata) {
        $trainingcourse->favouritedesignerdata = $db->get_training_user_favourite_designer_data($trainingcourse->id,
            $trainingcourse->contextid, $user->id);
    }
}

/**
 * Sort with the user's preferred design training first.
 *
 * @param stdClass[] $trainingcourses
 * @return void
 */
function block_mytrainings_sort_by_user_favorite(&$trainingcourses) {
    usort($trainingcourses, function($a, $b) {
        // Two element not favourite, same place.
        if (!$b->favouritedesignerdata && !$a->favouritedesignerdata) {
            return 0;
        }

        // A element not favourite, B is up.
        if (!$a->favouritedesignerdata) {
            return 1;
        }

        // B element not favourite, A is up.
        if (!$b->favouritedesignerdata) {
            return -1;
        }

        // Check time created to favourite select user.
        return $b->favouritedesignerdata->timecreated <=> $a->favouritedesignerdata->timecreated;
    });
}
