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
 * This ensures it appears in the user profile navigation.
 *
 * @param navigation_node $nav
 * @param stdClass $user
 * @param context_user $context
 */
function local_privacy_portal_extend_navigation_user(navigation_node $nav, $user, $context) {
    global $USER;

    // Only show if it's the user themselves or an admin.
    if ($USER->id != $user->id && !is_siteadmin()) {
        return;
    }

    $url = new moodle_url('/local/privacy_portal/index.php', ['id' => $user->id]);
    
    $nav->add(
        get_string('pluginname', 'local_privacy_portal'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'privacy_portal',
        new pix_icon('i/permissions', '')
    );
}

/**
 * Extend settings navigation to include the Privacy Portal in the Settings tab.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_privacy_portal_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    global $USER;

    if ($context->contextlevel == CONTEXT_USER) {
        $userid = $context->instanceid;
        if ($USER->id == $userid || is_siteadmin()) {
            $url = new moodle_url('/local/privacy_portal/index.php', ['id' => $userid]);
            
            $node = navigation_node::create(
                get_string('pluginname', 'local_privacy_portal'),
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'privacy_portal',
                new pix_icon('i/permissions', '')
            );

            // Add to user settings if found.
            if ($usersettings = $settingsnav->find('usersettings', navigation_node::TYPE_CONTAINER)) {
                $usersettings->add_node($node);
            } else {
                // Otherwise add to root of settings.
                $settingsnav->add_node($node);
            }
        }
    }
}

/**
 * Add Privacy Portal to the user's profile page as a card/link.
 *
 * @param \core_user\output\myprofile\tree $tree
 * @param stdClass $user
 * @param bool $iscurrentuser
 * @param stdClass $course
 */
function local_privacy_portal_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $USER;

    if (!$iscurrentuser && !is_siteadmin()) {
        return;
    }

    $url = new moodle_url('/local/privacy_portal/index.php', ['id' => $user->id]);
    
    $node = new \core_user\output\myprofile\node(
        'administration',
        'privacy_portal',
        get_string('pluginname', 'local_privacy_portal'),
        null,
        $url,
        null,
        new pix_icon('i/permissions', '')
    );

    $tree->add_node($node);
}

/**
 * Add Privacy Portal to the top-right user menu.
 *
 * @param user_menu $menu
 * @return array
 */
function local_privacy_portal_user_menu_after_links(user_menu $menu) {
    global $USER;
    
    $url = new moodle_url('/local/privacy_portal/index.php', ['id' => $USER->id]);
    $item = new \core\output\user_menu_named_link(
        get_string('pluginname', 'local_privacy_portal'),
        $url,
        new pix_icon('i/permissions', '')
    );
    
    return [$item];
}
