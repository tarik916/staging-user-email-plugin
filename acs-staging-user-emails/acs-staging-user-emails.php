<?php
/**
 * Plugin Name: ACS Staging User Emails
 * Description: A plugin that uses WP Batch Processing to scramble and unscramble non-admin user emails for staging environments.
 * Version: 1.0.3
 * Author: ACS Digital Media
 * License: GPL-2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * 
 * Requires the WP Batch Processing plugin. Download from GitHub: https://github.com/gdarko/wp-batch-processing
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Dependency check after plugins are loaded
add_action( 'plugins_loaded', 'acs_check_dependencies', 10 );
function acs_check_dependencies() {
    // Check if WP Batch Processing plugin is active
    if ( ! class_exists( 'WP_Batch_Processor' ) ) {
        add_action( 'admin_notices', 'acs_batch_dependency_notice' );
        return;
    }

    // Include batch processing functions if dependency is met
    require_once plugin_dir_path( __FILE__ ) . 'includes/batch-functions.php';
}

// Show admin notice if dependency is missing
function acs_batch_dependency_notice() {
    echo '<div class="notice notice-error"><p>ACS Staging User Emails requires the <a href="https://github.com/gdarko/wp-batch-processing" target="_blank">WP Batch Processing</a> plugin. Please install and activate it to use this plugin.</p></div>';
}

// Add submenu under 'Users'
add_action( 'admin_menu', 'acs_staging_user_emails_menu' );
function acs_staging_user_emails_menu() {
    add_submenu_page(
        'users.php',                 // Parent slug (places it under "Users")
        'Staging User Emails',       // Page title
        'Staging User Emails',       // Menu title
        'manage_options',            // Capability required to access the page
        'acs-staging-emails',        // Menu slug
        'acs_staging_user_emails_page' // Callback function to render the page
    );
}

// Admin page content
function acs_staging_user_emails_page() {
    ?>
    <div class="wrap">
        <h1>ACS Staging User Emails</h1>
        <p>This plugin uses the WP Batch Processing plugin to scramble or unscramble user emails for staging environments.</p>
        <p><a href="<?php echo admin_url('admin.php?page=dg-batches'); ?>" class="button">Go to Batch Processing Interface</a></p>
        <hr>
        <h2>Scramble Emails for Staging</h2>
        <div><p><a href="<?php echo admin_url( 'admin.php?page=dg-batches&action=view&id=scramble_emails' ); ?>" class="button">Scramble Emails</a></p></div>
        <div><p><a href="<?php echo admin_url( 'admin.php?page=dg-batches&action=view&id=unscramble_emails' ); ?>" class="button">Unscramble Emails</a></p></div>
        
    </div>
    <?php

    // Handle form submissions
    if ( isset( $_POST['scramble_emails'] ) ) {
        acs_batch_processing_scramble_init();
    } elseif ( isset( $_POST['unscramble_emails'] ) ) {
        acs_batch_processing_unscramble_init();
    }
}
