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

        // Fetch all non-admin users
        $users = $wpdb->get_results( "
            SELECT ID, user_email FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} m ON u.ID = m.user_id
            WHERE m.meta_key = '{$wpdb->prefix}capabilities' 
            AND m.meta_value NOT LIKE '%administrator%'
        " );

        // Break users into chunks of 40
        $chunks = array_chunk( $users, 40 );

        foreach ( $chunks as $index => $chunk ) {
            // Push each chunk into the batch queue with a unique index
            $this->push( new WP_Batch_Item( $index, array( 'user_chunk' => $chunk ) ) );
        }
    }

    public function process( $item ) {
        global $wpdb;

        // Retrieve the chunk of users from the batch item
        $user_chunk = $item->get_value( 'user_chunk' );

        foreach ( $user_chunk as $user ) {
            $user_id = $user->ID;
            $current_email = $user->user_email;

            // Check if the email already has the 'staging_' prefix
            if ( strpos( $current_email, 'staging_' ) !== 0 ) {
                $new_email = 'staging_' . $current_email;

                // Update email directly in the database
                $wpdb->update(
                    $wpdb->users,
                    array( 'user_email' => $new_email ),
                    array( 'ID' => $user_id )
                );
            }
        }

        return true; // Mark this chunk as processed successfully
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

        // Fetch all non-admin users
        $users = $wpdb->get_results( "
            SELECT ID, user_email FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} m ON u.ID = m.user_id
            WHERE m.meta_key = '{$wpdb->prefix}capabilities' 
            AND m.meta_value NOT LIKE '%administrator%'
        " );

        // Break users into chunks of 40
        $chunks = array_chunk( $users, 40 );

        foreach ( $chunks as $index => $chunk ) {
            // Push each chunk into the batch queue with a unique index
            $this->push( new WP_Batch_Item( $index, array( 'user_chunk' => $chunk ) ) );
        }
    }

    public function process( $item ) {
        global $wpdb;

        // Retrieve the chunk of users from the batch item
        $user_chunk = $item->get_value( 'user_chunk' );

        foreach ( $user_chunk as $user ) {
            $user_id = $user->ID;
            $current_email = $user->user_email;

            // Check if the email has the 'staging_' prefix
            if ( strpos( $current_email, 'staging_' ) === 0 ) {
                $new_email = str_replace( 'staging_', '', $current_email );

                // Update email directly in the database
                $wpdb->update(
                    $wpdb->users,
                    array( 'user_email' => $new_email ),
                    array( 'ID' => $user_id )
                );
            }
        }

        return true; // Mark this chunk as processed successfully
    }
}

// Initialize the batch processes
function wp_batch_processing_init() {
    $batch_scramble = new ACS_Scramble_Emails_Batch();
    WP_Batch_Processor::get_instance()->register( $batch_scramble );

    $batch_unscramble = new ACS_Unscramble_Emails_Batch();
    WP_Batch_Processor::get_instance()->register( $batch_unscramble );
}
add_action( 'wp_batch_processing_init', 'wp_batch_processing_init', 15 );
