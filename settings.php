<?php
/**
 * Admin settings for local_privacy_portal.
 *
 * @package    local_privacy_portal
 * @copyright  2024 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('reports', new admin_externalpage(
        'local_privacy_portal_admin',
        'PDPA Compliance Dashboard',
        new moodle_url('/local/privacy_portal/admin_dashboard.php'),
        'local/privacy_portal:view' // We should define this capability, but for now siteadmin has it.
    ));
}
