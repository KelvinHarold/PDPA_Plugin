<?php
/**
 * Data export handler for local_privacy_portal.
 *
 * @package    local_privacy_portal
 * @copyright  2024 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$userid = optional_param('id', $USER->id, PARAM_INT);
$categories = optional_param_array('categories', [], PARAM_ALPHA);
$format = optional_param('format', 'json', PARAM_ALPHA);

require_login();
confirm_sesskey();

$context = context_user::instance($userid);
require_capability('moodle/user:editownprofile', $context);

if (empty($categories)) {
    throw new moodle_exception('error_no_selection', 'local_privacy_portal');
}

// Log the request.
\local_privacy_portal\manager::log_request($userid, $categories, $format);

$data = \local_privacy_portal\manager::gather_export_data($userid, $categories);

$filename = 'moodle_data_export_' . $userid . '_' . date('Ymd');

if ($format === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
} else if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    foreach ($data as $cat => $rows) {
        fputcsv($output, ['--- ' . strtoupper($cat) . ' ---']);
        if (!empty($rows)) {
            // Get headers from first record.
            $first = reset($rows);
            fputcsv($output, array_keys((array)$first));
            foreach ($rows as $row) {
                fputcsv($output, (array)$row);
            }
        }
        fputcsv($output, []); // Empty line between sections.
    }
    fclose($output);
    exit;
}
