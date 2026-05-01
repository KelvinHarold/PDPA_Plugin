<?php
/**
 * PDPA Compliance Dashboard for Administrators.
 *
 * @package    local_privacy_portal
 * @copyright  2024 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_privacy_portal_admin');

$PAGE->set_url(new moodle_url('/local/privacy_portal/admin_dashboard.php'));
$PAGE->set_title('PDPA Compliance Dashboard');
$PAGE->set_heading('PDPA Compliance Dashboard');

echo $OUTPUT->header();

$stats = \local_privacy_portal\manager::get_admin_stats();
$alerts = \local_privacy_portal\manager::get_retention_alerts();

$template_data = [
    'total_users' => $stats->total_users,
    'sharing_count' => $stats->consent_sharing,
    'analytics_count' => $stats->consent_analytics,
    'marketing_count' => $stats->consent_marketing,
    'total_requests' => $stats->total_requests,
    'requests' => [],
    'alerts' => [],
    'has_alerts' => !empty($alerts),
    'has_requests' => !empty($stats->recent_requests),
];

foreach ($stats->recent_requests as $r) {
    $user = $DB->get_record('user', ['id' => $r->userid], 'firstname,lastname');
    $template_data['requests'][] = [
        'user' => fullname($user),
        'categories' => $r->categories,
        'format' => strtoupper($r->format),
        'date' => userdate($r->timecreated),
        'status' => ucfirst($r->status)
    ];
}

foreach ($alerts as $a) {
    $template_data['alerts'][] = [
        'user' => fullname($a),
        'lastaccess' => $a->lastaccess ? userdate($a->lastaccess) : 'Never',
        'days' => floor((time() - $a->lastaccess) / (24 * 60 * 60))
    ];
}

echo $OUTPUT->render_from_template('local_privacy_portal/admin_dashboard', $template_data);

echo $OUTPUT->footer();
