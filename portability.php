<?php
/**
 * Independent Data Portability Module.
 *
 * @package    local_privacy_portal
 * @copyright  2024 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$userid = optional_param('id', $USER->id, PARAM_INT);
require_login();

$context = context_user::instance($userid);
require_capability('moodle/user:editownprofile', $context);

$summary = \local_privacy_portal\manager::get_data_summary($userid);

$PAGE->set_url(new moodle_url('/local/privacy_portal/portability.php', ['id' => $userid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('data_portability', 'local_privacy_portal'));
$PAGE->set_heading(get_string('data_portability', 'local_privacy_portal'));

echo $OUTPUT->header();

$history = $DB->get_records('local_privacy_portal_reqs', ['userid' => $userid], 'timecreated DESC');
$formatted_history = [];
foreach ($history as $item) {
    $formatted_history[] = [
        'date' => userdate($item->timecreated),
        'categories' => $item->categories,
        'format' => strtoupper($item->format),
        'status' => ucfirst($item->status),
        'badge_class' => ($item->status === 'completed') ? 'badge-success' : 'badge-warning'
    ];
}

$template_data = [
    'sesskey' => sesskey(),
    'userid' => $userid,
    'export_url' => new moodle_url('/local/privacy_portal/export.php'),
    'has_history' => !empty($formatted_history),
    'history' => $formatted_history,
    'summary' => $summary
];

echo $OUTPUT->render_from_template('local_privacy_portal/portability_page', $template_data);
echo $OUTPUT->footer();
