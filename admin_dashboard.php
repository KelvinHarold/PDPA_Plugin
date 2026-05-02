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

// 1. Consent Distribution Chart.
$consent_chart = new \core\chart_pie();
$consent_series = new \core\chart_series('Consent Opt-ins', [
    $stats->consent_sharing,
    $stats->consent_analytics,
    $stats->consent_marketing
]);
$consent_series->set_colors(['#17996b', '#3b82f6', '#f59e0b']);
$consent_chart->add_series($consent_series);
$consent_chart->set_labels(['Sharing', 'Analytics', 'Marketing']);

// 2. Retention Status Chart.
$retention_chart = new \core\chart_pie();
$retention_chart->set_doughnut(true);
$retention_series = new \core\chart_series('User Status', [
    $stats->active_users,
    $stats->stale_users
]);
$retention_series->set_colors(['#17996b', '#ef4444']);
$retention_chart->add_series($retention_series);
$retention_chart->set_labels(['Active', 'Stale (180d+)']);

// 3. Requests Bar Chart.
$requests_chart = new \core\chart_bar();
$requests_series = new \core\chart_series('Export Requests', array_values($stats->request_breakdown));
$requests_chart->add_series($requests_series);
$requests_chart->set_labels(array_map('ucfirst', array_keys($stats->request_breakdown)));

// Calculate some percentages for the UI.
$consent_sharing_pct = $stats->total_users > 0 ? round(($stats->consent_sharing / $stats->total_users) * 100) : 0;

$formatted_alerts = [];
foreach ($alerts as $a) {
    $formatted_alerts[] = [
        'firstname' => $a->firstname,
        'lastname' => $a->lastname,
        'lastaccess_date' => $a->lastaccess ? userdate($a->lastaccess) : 'Never',
    ];
}

$template_data = [
    'total_users' => $stats->total_users,
    'consent_sharing_pct' => $consent_sharing_pct,
    'total_requests' => $stats->total_requests,
    'total_alerts' => count($formatted_alerts),
    'has_alerts' => !empty($formatted_alerts),
    'retention_alerts' => $formatted_alerts,
    'audit_logs' => array_values((array)$stats->audit_logs),
    'policy_acceptance_pct' => $stats->policy_acceptance_pct,
    'compliance_score' => $stats->compliance_score,
    // Raw chart data for custom JS.
    'raw_consent_sharing' => $stats->consent_sharing,
    'raw_consent_analytics' => $stats->consent_analytics,
    'raw_consent_marketing' => $stats->consent_marketing,
    'raw_active_users' => $stats->active_users,
    'raw_stale_users' => $stats->stale_users,
    'raw_request_counts' => json_encode(array_values($stats->request_breakdown)),
    'raw_request_labels' => json_encode(array_map('ucfirst', array_keys($stats->request_breakdown))),
    // Export chart data for the template.
    'consent_chart' => $OUTPUT->render($consent_chart),
    'retention_chart' => $OUTPUT->render($retention_chart),
    'requests_chart' => $OUTPUT->render($requests_chart),
];

echo $OUTPUT->render_from_template('local_privacy_portal/admin_dashboard', $template_data);

echo $OUTPUT->footer();
