<?php
/**
 * Library functions for local_privacy_portal.
 *
 * @package    local_privacy_portal
 * @copyright  2024 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend user navigation to include the Privacy Portal.
 *
 * @param navigation_node $nav
 * @param stdClass $user
 * @param context_user $context
 */
function local_privacy_portal_extend_navigation_user(navigation_node $nav, $user, $context) {
    global $USER, $PAGE;

    // Only show if it's the user themselves or an admin.
    if ($USER->id != $user->id && !is_siteadmin()) {
        return;
    }

    $url = new moodle_url('/local/privacy_portal/index.php', ['id' => $user->id]);
    
    $nav->add(
        get_string('pluginname', 'local_privacy_portal'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'privacy_portal',
        new pix_icon('i/permissions', '')
    );
}
