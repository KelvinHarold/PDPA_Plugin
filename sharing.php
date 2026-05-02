<?php
/**
 * Independent Sharing Notification Module.
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

$PAGE->set_url(new moodle_url('/local/privacy_portal/sharing.php', ['id' => $userid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('sharing_notifications', 'local_privacy_portal'));
$PAGE->set_heading(get_string('sharing_notifications', 'local_privacy_portal'));

echo $OUTPUT->header();

$history = \local_privacy_portal\manager::get_sharing_history($userid);
$formatted_history = [];
$tools_count = [];

foreach ($history as $item) {
    $formatted_history[] = [
        'thirdpartyname' => $item->thirdpartyname,
        'purpose' => $item->purpose,
        'categories' => $item->categories,
        'date' => userdate($item->timeshared),
        'location' => 'Cloud (External)', // Default or derived.
    ];
    $tools_count[$item->thirdpartyname] = true;
}

$lti_tools = \local_privacy_portal\manager::get_lti_tools();
$formatted_lti = [];
foreach ($lti_tools as $tool) {
    $formatted_lti[] = [
        'name' => $tool->name,
        'url' => $tool->toolurl
    ];
}

$template_data = [
    'has_history' => !empty($formatted_history),
    'history' => $formatted_history,
    'has_lti' => !empty($formatted_lti),
    'lti_tools' => $formatted_lti,
    'stats' => [
        'tools' => count($tools_count) + count($formatted_lti),
        'total' => count($formatted_history),
        'notifications' => count($formatted_history)
    ]
];

echo $OUTPUT->render_from_template('local_privacy_portal/sharing_page', $template_data);
echo $OUTPUT->footer();
