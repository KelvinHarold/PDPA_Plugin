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
     * Log a compliance audit event.
     */
    public static function log_audit($userid, $action, $details) {
        global $DB, $USER;
        
        // Safety check: ensure table exists before logging.
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('local_privacy_portal_audt')) {
            return;
        }

        $record = new \stdClass();
        $record->userid = $userid;
        $record->adminid = ($USER->id != $userid) ? $USER->id : 0;
        $record->action = $action;
        $record->details = $details;
        $record->ipaddress = getremoteaddr();
        $record->timecreated = time();
        $DB->insert_record('local_privacy_portal_audt', $record);
    }

    /**
     * Get consent preferences for a user.
     *
     * @param int $userid
     * @return \stdClass
     */
    public static function get_user_consent($userid) {
        global $DB;

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('local_privacy_portal_cons')) {
            return (object)['sharing' => 0, 'analytics' => 0, 'marketing' => 0, 'timemodified' => 0];
        }

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

        self::log_audit($userid, 'consent_updated', 'User updated granular consent preferences.');
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

        if (in_array('forum', $categories)) {
            $sql = "SELECT id, subject, message, timecreated 
                    FROM {forum_posts} 
                    WHERE userid = :userid 
                    ORDER BY timecreated DESC";
            $export['forum'] = $DB->get_records_sql($sql, ['userid' => $userid]);
        }

        return $export;
    }

    /**
     * Log a data portability request.
     */
    public static function log_request($userid, $categories, $format) {
        global $DB;

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('local_privacy_portal_reqs')) {
            return;
        }

        $record = new \stdClass();
        $record->userid = $userid;
        $record->categories = implode(', ', $categories);
        $record->format = $format;
        $record->status = 'completed';
        $record->timecreated = time();
        $DB->insert_record('local_privacy_portal_reqs', $record);

        self::log_audit($userid, 'data_exported', "User requested data export ($format) for categories: " . $record->categories);
    }

    /**
     * Get sharing history for a user.
     */
    public static function get_sharing_history($userid) {
        global $DB;
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('local_privacy_portal_shar')) {
            return [];
        }
        return $DB->get_records('local_privacy_portal_shar', ['userid' => $userid], 'timeshared DESC');
    }

    /**
     * Get admin dashboard statistics.
     */
    public static function get_admin_stats() {
        global $DB;
        
        $stats = new \stdClass();
        $dbman = $DB->get_manager();
        
        $stats->total_users = $DB->count_records('user', ['deleted' => 0]);
        
        // Safety checks for all custom tables.
        $stats->consent_sharing = $dbman->table_exists('local_privacy_portal_cons') ? $DB->count_records('local_privacy_portal_cons', ['sharing' => 1]) : 0;
        $stats->consent_analytics = $dbman->table_exists('local_privacy_portal_cons') ? $DB->count_records('local_privacy_portal_cons', ['analytics' => 1]) : 0;
        $stats->consent_marketing = $dbman->table_exists('local_privacy_portal_cons') ? $DB->count_records('local_privacy_portal_cons', ['marketing' => 1]) : 0;
        
        $stats->total_requests = $dbman->table_exists('local_privacy_portal_reqs') ? $DB->count_records('local_privacy_portal_reqs') : 0;
        $stats->recent_requests = $dbman->table_exists('local_privacy_portal_reqs') ? $DB->get_records('local_privacy_portal_reqs', null, 'timecreated DESC', '*', 0, 5) : [];

        // Request categories breakdown for charts.
        $stats->request_breakdown = [
            'profile' => 0,
            'grades' => 0,
            'activity' => 0,
            'forum' => 0
        ];
        if ($dbman->table_exists('local_privacy_portal_reqs')) {
            $reqs = $DB->get_records('local_privacy_portal_reqs', null, '', 'categories');
            foreach ($reqs as $r) {
                $cats = explode(', ', $r->categories);
                foreach ($cats as $cat) {
                    $cat = trim($cat);
                    if (isset($stats->request_breakdown[$cat])) {
                        $stats->request_breakdown[$cat]++;
                    }
                }
            }
        }

        // Retention stats.
        $threshold = time() - (180 * 24 * 60 * 60);
        $stats->stale_users = $DB->count_records_select('user', "lastaccess < :threshold AND lastaccess > 0 AND deleted = 0", ['threshold' => $threshold]);
        $stats->active_users = $stats->total_users - $stats->stale_users;

        // Real Policy Acceptance (users who have interacted with consent portal).
        $users_with_consent = $dbman->table_exists('local_privacy_portal_cons') ? $DB->count_records('local_privacy_portal_cons') : 0;
        $stats->policy_acceptance_pct = $stats->total_users > 0 ? round(($users_with_consent / $stats->total_users) * 100) : 0;

        // Real Compliance Score (Weighted logic).
        // 40% Consent interaction, 40% Retention health, 20% Request fulfilment (all completed in our mock for now).
        $retention_health = $stats->total_users > 0 ? (($stats->total_users - $stats->stale_users) / $stats->total_users) * 100 : 100;
        $stats->compliance_score = round(($stats->policy_acceptance_pct * 0.4) + ($retention_health * 0.4) + 20);

        // Fetch audit logs for the dashboard with safety check.
        $stats->audit_logs = [];
        if ($dbman->table_exists('local_privacy_portal_audt')) {
            try {
                $logs = $DB->get_records('local_privacy_portal_audt', null, 'timecreated DESC', '*', 0, 10);
                if ($logs) {
                    foreach ($logs as $log) {
                        $user = $DB->get_record('user', ['id' => $log->userid], 'firstname, lastname');
                        $log->username = $user ? fullname($user) : 'System';
                        $log->date = userdate($log->timecreated);
                        $stats->audit_logs[] = $log;
                    }
                }
            } catch (\Exception $e) {
                $stats->audit_logs = [];
            }
        }

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

    /**
     * Get a summary of actual data held in Moodle for this user.
     */
    public static function get_data_summary($userid) {
        global $DB;
        
        $summary = new \stdClass();
        
        // Count actual grades.
        $summary->grades_count = $DB->count_records('grade_grades', ['userid' => $userid]);
        
        // Count forum posts.
        $summary->forum_posts_count = $DB->count_records('forum_posts', ['userid' => $userid]);
        
        // Count active enrolments.
        $summary->enrolments_count = $DB->count_records('user_enrolments', ['userid' => $userid]);

        // Get last login.
        $user = $DB->get_record('user', ['id' => $userid], 'lastlogin, lastip');
        $summary->last_login = $user->lastlogin ? userdate($user->lastlogin) : 'Never';
        $summary->last_ip = $user->lastip ?: 'Unknown';

        return $summary;
    }

    /**
     * Get actual LTI tools configured in Moodle.
     */
    public static function get_lti_tools() {
        global $DB;
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('lti_types')) {
            return [];
        }
        // Use lti_types which is the standard table for configured external tools.
        return $DB->get_records('lti_types', ['state' => 1], '', 'id, name, baseurl as toolurl');
    }
}
