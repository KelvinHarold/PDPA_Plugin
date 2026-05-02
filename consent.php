<?php
/**
 * Independent Consent Management Module.
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
$consent = \local_privacy_portal\manager::get_user_consent($userid);

$PAGE->set_url(new moodle_url('/local/privacy_portal/consent.php', ['id' => $userid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('consent_management', 'local_privacy_portal'));
$PAGE->set_heading(get_string('consent_management', 'local_privacy_portal'));

// Handle form submission.
if (optional_param('action', '', PARAM_ALPHA) === 'save' && confirm_sesskey()) {
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
    'save_url' => $PAGE->url->out(false),
    'consent' => [
        [
            'name' => 'academic',
            'label' => 'Enrollment & Academic Records',
            'desc' => "Verified student record with {$summary->enrolments_count} active course registrations.",
            'checked' => 'checked',
            'is_required' => true,
            'status_label' => 'Required',
            'badge_class' => 'badge-success',
            'icon' => 'fa-graduation-cap'
        ],
        [
            'name' => 'security',
            'label' => 'Essential Account Notifications',
            'desc' => 'Security alerts, password resets, and core system notices.',
            'checked' => 'checked',
            'is_required' => true,
            'status_label' => 'Required',
            'badge_class' => 'badge-success',
            'icon' => 'fa-shield-alt'
        ],
        [
            'name' => 'sharing',
            'label' => get_string('purpose_sharing', 'local_privacy_portal'),
            'desc' => get_string('purpose_sharing_desc', 'local_privacy_portal'),
            'checked' => $consent->sharing ? 'checked' : '',
            'status_label' => $consent->sharing ? get_string('status_active', 'local_privacy_portal') : get_string('status_withdrawn', 'local_privacy_portal'),
            'badge_class' => $consent->sharing ? 'badge-success' : 'badge-danger',
            'icon' => 'fa-puzzle-piece'
        ],
        [
            'name' => 'analytics',
            'label' => get_string('purpose_analytics', 'local_privacy_portal'),
            'desc' => get_string('purpose_analytics_desc', 'local_privacy_portal'),
            'checked' => $consent->analytics ? 'checked' : '',
            'status_label' => $consent->analytics ? get_string('status_active', 'local_privacy_portal') : get_string('status_withdrawn', 'local_privacy_portal'),
            'badge_class' => $consent->analytics ? 'badge-success' : 'badge-danger',
            'icon' => 'fa-chart-line'
        ],
        [
            'name' => 'marketing',
            'label' => get_string('purpose_marketing', 'local_privacy_portal'),
            'desc' => get_string('purpose_marketing_desc', 'local_privacy_portal'),
            'checked' => $consent->marketing ? 'checked' : '',
            'status_label' => $consent->marketing ? get_string('status_active', 'local_privacy_portal') : get_string('status_withdrawn', 'local_privacy_portal'),
            'badge_class' => $consent->marketing ? 'badge-success' : 'badge-danger',
            'icon' => 'fa-bullhorn'
        ]
    ]
];

echo $OUTPUT->render_from_template('local_privacy_portal/consent_page', $template_data);
echo $OUTPUT->footer();
