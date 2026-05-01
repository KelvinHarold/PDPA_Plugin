<?php
/**
 * Logic manager for local_privacy_portal.
 *
 * @package    local_privacy_portal
 * @copyright  2024 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_privacy_portal;

defined('MOODLE_INTERNAL') || die();

class manager {

    /**
     * Get consent preferences for a user.
     *
     * @param int $userid
     * @return \stdClass
     */
    public static function get_user_consent($userid) {
        global $DB;

        $record = $DB->get_record('local_privacy_portal_cons', ['userid' => $userid]);
        
        if (!$record) {
            // Return defaults.
            return (object)[
                'sharing' => 0,
                'analytics' => 0,
                'marketing' => 0,
                'timemodified' => 0
            ];
        }

        return $record;
    }

    /**
     * Save consent preferences.
     *
     * @param int $userid
     * @param array $data
     */
    public static function save_user_consent($userid, $data) {
        global $DB;

        $record = $DB->get_record('local_privacy_portal_cons', ['userid' => $userid]);

        $newrecord = new \stdClass();
        $newrecord->userid = $userid;
        $newrecord->sharing = !empty($data['sharing']) ? 1 : 0;
        $newrecord->analytics = !empty($data['analytics']) ? 1 : 0;
        $newrecord->marketing = !empty($data['marketing']) ? 1 : 0;
        $newrecord->timemodified = time();

        if ($record) {
            $newrecord->id = $record->id;
            $DB->update_record('local_privacy_portal_cons', $newrecord);
        } else {
            $DB->insert_record('local_privacy_portal_cons', $newrecord);
        }
    }

    /**
     * Gather data for export.
     *
     * @param int $userid
     * @param array $categories
     * @return array
     */
    public static function gather_export_data($userid, $categories) {
        global $DB;

        $export = [];

        if (in_array('profile', $categories)) {
            $user = $DB->get_record('user', ['id' => $userid], 'id,username,firstname,lastname,email,city,country');
            $export['profile'] = (array)$user;
        }

        if (in_array('grades', $categories)) {
            $sql = "SELECT g.id, c.fullname as course, g.finalgrade, g.rawgrade, g.rawgrademax 
                    FROM {grade_grades} g
                    JOIN {grade_items} i ON g.itemid = i.id
                    JOIN {course} c ON i.courseid = c.id
                    WHERE g.userid = :userid AND i.itemtype = 'course'";
            $export['grades'] = $DB->get_records_sql($sql, ['userid' => $userid]);
        }

        if (in_array('activity', $categories)) {
            $sql = "SELECT id, eventname, component, action, target, timecreated 
                    FROM {logstore_standard_log} 
                    WHERE userid = :userid 
                    ORDER BY timecreated DESC 
                    LIMIT 100";
            $export['activity'] = $DB->get_records_sql($sql, ['userid' => $userid]);
        }

        return $export;
    }

    /**
     * Log a data portability request.
     */
    public static function log_request($userid, $categories, $format) {
        global $DB;
        $record = new \stdClass();
        $record->userid = $userid;
        $record->categories = implode(', ', $categories);
        $record->format = $format;
        $record->status = 'completed';
        $record->timecreated = time();
        $DB->insert_record('local_privacy_portal_reqs', $record);
    }

    /**
     * Get sharing history for a user.
     */
    public static function get_sharing_history($userid) {
        global $DB;
        return $DB->get_records('local_privacy_portal_shar', ['userid' => $userid], 'timeshared DESC');
    }

    /**
     * Get admin dashboard statistics.
     */
    public static function get_admin_stats() {
        global $DB;
        
        $stats = new \stdClass();
        $stats->total_users = $DB->count_records('user', ['deleted' => 0]);
        $stats->consent_sharing = $DB->count_records('local_privacy_portal_cons', ['sharing' => 1]);
        $stats->consent_analytics = $DB->count_records('local_privacy_portal_cons', ['analytics' => 1]);
        $stats->consent_marketing = $DB->count_records('local_privacy_portal_cons', ['marketing' => 1]);
        
        $stats->total_requests = $DB->count_records('local_privacy_portal_reqs');
        $stats->recent_requests = $DB->get_records('local_privacy_portal_reqs', null, 'timecreated DESC', '*', 0, 5);

        return $stats;
    }

    /**
     * Get retention alerts (users inactive for > 180 days).
     */
    public static function get_retention_alerts() {
        global $DB;
        $threshold = time() - (180 * 24 * 60 * 60);
        $sql = "SELECT id, username, firstname, lastname, lastaccess 
                FROM {user} 
                WHERE lastaccess < :threshold AND lastaccess > 0 AND deleted = 0
                ORDER BY lastaccess ASC LIMIT 10";
        return $DB->get_records_sql($sql, ['threshold' => $threshold]);
    }
}
