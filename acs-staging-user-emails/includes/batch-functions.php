<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class ACS_Scramble_Emails_Batch
 * Adds 'staging_' prefix to non-admin user emails directly in the database.
 */
class ACS_Scramble_Emails_Batch extends WP_Batch {
    public $id = 'scramble_emails';
    public $title = 'Scramble User Emails for Staging';

    public function setup() {
        global $wpdb;
        $users = $wpdb->get_col( "SELECT ID FROM {$wpdb->users} u 
                                  INNER JOIN {$wpdb->usermeta} m 
                                  ON u.ID = m.user_id 
                                  WHERE m.meta_key = '{$wpdb->prefix}capabilities' 
                                  AND m.meta_value NOT LIKE '%administrator%'" );

        foreach ( $users as $user_id ) {
            $this->push( new WP_Batch_Item( $user_id, array( 'user_id' => $user_id ) ) );
        }
    }

    public function process( $item ) {
        global $wpdb;
        $user_id = $item->get_value( 'user_id' );

        // Fetch the current email
        $current_email = $wpdb->get_var( $wpdb->prepare( "SELECT user_email FROM {$wpdb->users} WHERE ID = %d", $user_id ) );

        // Check if the email already has the 'staging_' prefix
        if ( $current_email && strpos( $current_email, 'staging_' ) !== 0 ) {
            $new_email = 'staging_' . $current_email;

            // Update email directly in the database
            $wpdb->update(
                $wpdb->users,
                array( 'user_email' => $new_email ),
                array( 'ID' => $user_id )
            );
        }

        return true;
    }
}

/**
 * Class ACS_Unscramble_Emails_Batch
 * Removes 'staging_' prefix from user emails directly in the database.
 */
class ACS_Unscramble_Emails_Batch extends WP_Batch {
    public $id = 'unscramble_emails';
    public $title = 'Unscramble User Emails';

    public function setup() {
        global $wpdb;
        $users = $wpdb->get_col( "SELECT ID FROM {$wpdb->users} u 
                                  INNER JOIN {$wpdb->usermeta} m 
                                  ON u.ID = m.user_id 
                                  WHERE m.meta_key = '{$wpdb->prefix}capabilities' 
                                  AND m.meta_value NOT LIKE '%administrator%'" );

        foreach ( $users as $user_id ) {
            $this->push( new WP_Batch_Item( $user_id, array( 'user_id' => $user_id ) ) );
        }
    }

    public function process( $item ) {
        global $wpdb;
        $user_id = $item->get_value( 'user_id' );

        // Fetch current email
        $current_email = $wpdb->get_var( $wpdb->prepare( "SELECT user_email FROM {$wpdb->users} WHERE ID = %d", $user_id ) );

        // Check if the email has the 'staging_' prefix
        if ( $current_email && strpos( $current_email, 'staging_' ) === 0 ) {
            $new_email = str_replace( 'staging_', '', $current_email );

            // Update email directly in the database
            $wpdb->update(
                $wpdb->users,
                array( 'user_email' => $new_email ),
                array( 'ID' => $user_id )
            );
        }
        return true;
    }
}

// Initialize the batch process
function wp_batch_processing_init() {
    $batch = new ACS_Scramble_Emails_Batch();
    WP_Batch_Processor::get_instance()->register( $batch );
}
add_action( 'wp_batch_processing_init', 'wp_batch_processing_init', 15 );


function wp_batch_processing_init_2() {
    $batch = new ACS_Unscramble_Emails_Batch();
    WP_Batch_Processor::get_instance()->register( $batch );
}
add_action( 'wp_batch_processing_init', 'wp_batch_processing_init_2', 15 );