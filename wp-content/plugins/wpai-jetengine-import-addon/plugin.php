<?php
/*
Plugin Name: WP All Import - JetEngine Add-On
Plugin URI: http://www.wpallimport.com/
Description: Import to JetEngine Meta Fields. Requires WP All Import & JetEngine.
Text Domain: wp_all_import_jetengine_add_on
Version: 1.0.1-beta-1.2
Requires PHP: 7.4
Author: Soflyy
*/

namespace Wpai\JetEngine;

const PMJI_VERSION = '1.0.1-beta-1.2';

define('PMJI_ROOT_DIR', str_replace('\\', '/', dirname(__FILE__)));
define('PMJI_ROOT_URL', rtrim(plugin_dir_url(__FILE__), '/'));

add_action('plugins_loaded', 'Wpai\JetEngine\pmji_load_plugin');
add_action('init', 'Wpai\JetEngine\pmji_load_plugin_textdomain', 10);
//add_action('after_plugin_row_'.plugin_basename(__FILE__), 'Wpai\JetEngine\pmji_plugins_page_notice', 10, 3);

function pmji_load_plugin() {
    if (!class_exists('PMXI_Plugin')) {
        add_action('admin_notices', '\Wpai\JetEngine\pmji_display_missing_dependency_notice');
        return;
    }

    if (!class_exists('\Wpai\AddonAPI\PMXI_Addon_Base') || version_compare(PMXI_VERSION, '4.9.1-beta-1.3', '<')) {
        add_action('admin_notices', '\Wpai\JetEngine\pmji_display_outdated_dependency_notice');
        return;
    }

    // Load dependencies
    require PMJI_ROOT_DIR . '/classes/autoload.php';

    // Register the addon
    new PMJI_JetEngine_Addon();
}

function pmji_load_plugin_textdomain() {
    load_plugin_textdomain('wp_all_import_jetengine_add_on', false, dirname(plugin_basename(__FILE__)) . '/i18n/languages');
}

function pmji_display_missing_dependency_notice() {
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_name = $plugin_data['Name'];
?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                // translators: %s: plugin name
                __('<b>%s</b>: WP All Import Pro must be installed: <a href="http://www.wpallimport.com/">http://www.wpallimport.com/</a>', 'wp_all_import_jetengine_add_on'),
                $plugin_name
            );
            ?>
        </p>
    </div>
<?php
}

function pmji_display_outdated_dependency_notice() {
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_name = $plugin_data['Name'];
?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                // translators: %s: plugin name
                __('<b>%s</b>: WP All Import must be updated to version 4.9.1 or higher.', 'wp_all_import_jetengine_add_on'),
                $plugin_name
            );
            ?>
        </p>
    </div>
<?php
}

function pmji_plugins_page_notice($plugin_file, $plugin_data, $status) {
	$message = "This add-on is currently in beta. We are working on adding support for a few of the lesser used fields. Future updates may break existing imports and exports that use the add-on, but we will try to avoid it.";
	echo '<tr class="plugin-update-tr active"><td colspan="4" class="plugin-update colspanchange"><div class="notice inline notice-warning notice-alt"><p>'. $message.'</p></div></td></tr>';
}
