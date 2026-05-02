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
 * Extend user navigation to include independent privacy modules.
 */
function local_privacy_portal_extend_navigation_user(navigation_node $nav, $user, $context) {
    global $USER;

    if ($USER->id != $user->id && !is_siteadmin()) {
        return;
    }

    // Add Consent Management.
    $nav->add(
        get_string('consent_management', 'local_privacy_portal'),
        new moodle_url('/local/privacy_portal/consent.php', ['id' => $user->id]),
        navigation_node::TYPE_CUSTOM, null, 'privacy_consent', new pix_icon('i/permissions', '')
    );

    // Add Data Portability.
    $nav->add(
        get_string('data_portability', 'local_privacy_portal'),
        new moodle_url('/local/privacy_portal/portability.php', ['id' => $user->id]),
        navigation_node::TYPE_CUSTOM, null, 'privacy_portability', new pix_icon('i/export', '')
    );

    // Add Sharing Notifications.
    $nav->add(
        get_string('sharing_notifications', 'local_privacy_portal'),
        new moodle_url('/local/privacy_portal/sharing.php', ['id' => $user->id]),
        navigation_node::TYPE_CUSTOM, null, 'privacy_sharing', new pix_icon('i/item', '')
    );
}

/**
 * Extend settings navigation.
 */
function local_privacy_portal_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    global $USER;

    if ($context->contextlevel == CONTEXT_USER) {
        $userid = $context->instanceid;
        if ($USER->id == $userid || is_siteadmin()) {
            if ($usersettings = $settingsnav->find('usersettings', navigation_node::TYPE_CONTAINER)) {
                $usersettings->add(
                    get_string('consent_management', 'local_privacy_portal'),
                    new moodle_url('/local/privacy_portal/consent.php', ['id' => $userid]),
                    navigation_node::TYPE_SETTING
                );
            }
        }
    }
}

/**
 * Add modules to the user's profile page as independent cards.
 */
function local_privacy_portal_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    if (!$iscurrentuser && !is_siteadmin()) {
        return;
    }

    // Module 1: Consent.
    $tree->add_node(new \core_user\output\myprofile\node(
        'administration', 'privacy_consent',
        get_string('consent_management', 'local_privacy_portal'),
        null, new moodle_url('/local/privacy_portal/consent.php', ['id' => $user->id]),
        null, new pix_icon('i/permissions', '')
    ));

    // Module 2: Portability.
    $tree->add_node(new \core_user\output\myprofile\node(
        'administration', 'privacy_portability',
        get_string('data_portability', 'local_privacy_portal'),
        null, new moodle_url('/local/privacy_portal/portability.php', ['id' => $user->id]),
        null, new pix_icon('i/export', '')
    ));

    // Module 3: Sharing.
    $tree->add_node(new \core_user\output\myprofile\node(
        'administration', 'privacy_sharing',
        get_string('sharing_notifications', 'local_privacy_portal'),
        null, new moodle_url('/local/privacy_portal/sharing.php', ['id' => $user->id]),
        null, new pix_icon('i/item', '')
    ));
}

/**
 * Add Privacy modules to the top-right user menu.
 */
function local_privacy_portal_user_menu_after_links(user_menu $menu) {
    global $USER;
    
    $items = [];
    $items[] = new \core\output\user_menu_named_link(
        get_string('consent_management', 'local_privacy_portal'),
        new moodle_url('/local/privacy_portal/consent.php', ['id' => $USER->id]),
        new pix_icon('i/permissions', '')
    );
    $items[] = new \core\output\user_menu_named_link(
        get_string('data_portability', 'local_privacy_portal'),
        new moodle_url('/local/privacy_portal/portability.php', ['id' => $USER->id]),
        new pix_icon('i/export', '')
    );
    
    return $items;
}
