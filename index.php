<?php
/**
 * Main entry point for the Privacy Portal.
 *
 * @package    local_privacy_portal
 * @copyright  2024 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$userid = optional_param('id', $USER->id, PARAM_INT);

require_login();

$context = context_user::instance($userid);
require_capability('moodle/user:editownprofile', $context);

$PAGE->set_url(new moodle_url('/local/privacy_portal/index.php', ['id' => $userid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('portal_title', 'local_privacy_portal'));
$PAGE->set_heading(get_string('portal_title', 'local_privacy_portal'));
$PAGE->set_pagelayout('standard');

// Handle form submission for consent.
if (optional_param('action', '', PARAM_ALPHA) === 'saveconsent' && confirm_sesskey()) {
    $data = [
        'sharing' => optional_param('sharing', 0, PARAM_INT),
        'analytics' => optional_param('analytics', 0, PARAM_INT),
        'marketing' => optional_param('marketing', 0, PARAM_INT),
    ];
    \local_privacy_portal\manager::save_user_consent($userid, $data);
    redirect($PAGE->url, get_string('preferences_saved', 'local_privacy_portal'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

$consent = \local_privacy_portal\manager::get_user_consent($userid);

$template_data = [
    'sesskey' => sesskey(),
    'userid' => $userid,
    'save_url' => $PAGE->url->out(false),
    'export_url' => new moodle_url('/local/privacy_portal/export.php'),
    'consent' => [
        [
            'name' => 'sharing',
            'label' => get_string('purpose_sharing', 'local_privacy_portal'),
            'desc' => get_string('purpose_sharing_desc', 'local_privacy_portal'),
            'checked' => $consent->sharing ? 'checked' : '',
            'active' => $consent->sharing,
            'status_label' => $consent->sharing ? get_string('status_active', 'local_privacy_portal') : get_string('status_withdrawn', 'local_privacy_portal'),
            'badge_class' => $consent->sharing ? 'badge-success' : 'badge-danger',
        ],
        [
            'name' => 'analytics',
            'label' => get_string('purpose_analytics', 'local_privacy_portal'),
            'desc' => get_string('purpose_analytics_desc', 'local_privacy_portal'),
            'checked' => $consent->analytics ? 'checked' : '',
            'active' => $consent->analytics,
            'status_label' => $consent->analytics ? get_string('status_active', 'local_privacy_portal') : get_string('status_withdrawn', 'local_privacy_portal'),
            'badge_class' => $consent->analytics ? 'badge-success' : 'badge-danger',
        ],
        [
            'name' => 'marketing',
            'label' => get_string('purpose_marketing', 'local_privacy_portal'),
            'desc' => get_string('purpose_marketing_desc', 'local_privacy_portal'),
            'checked' => $consent->marketing ? 'checked' : '',
            'active' => $consent->marketing,
            'status_label' => $consent->marketing ? get_string('status_active', 'local_privacy_portal') : get_string('status_withdrawn', 'local_privacy_portal'),
            'badge_class' => $consent->marketing ? 'badge-success' : 'badge-danger',
        ]
    ],
    'has_sharing' => false,
    'sharing_history' => []
];

$sharing = \local_privacy_portal\manager::get_sharing_history($userid);
if ($sharing) {
    $template_data['has_sharing'] = true;
    foreach ($sharing as $s) {
        $template_data['sharing_history'][] = [
            'thirdpartyname' => $s->thirdpartyname,
            'purpose' => $s->purpose,
            'categories' => $s->categories,
            'date' => userdate($s->timeshared)
        ];
    }
}

echo $OUTPUT->render_from_template('local_privacy_portal/portal', $template_data);

echo $OUTPUT->footer();
