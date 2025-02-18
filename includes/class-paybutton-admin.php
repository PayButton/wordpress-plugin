<?php
/**
 * PayButton Admin Class
 *
 * Handles the admin functionality of the plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PayButton_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /**
     * Add the admin menu and submenus to the WordPress Dashboard.
     */
    public function add_admin_menus() {
        add_menu_page(
            'PayButton',
            'PayButton',
            'manage_options',
            'paybutton',
            array( $this, 'dashboard_page' ),
            'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA5MC42NSA0OS4wOCI+PGcgZGF0YS1uYW1lPSJMYXllcl8yIj48cmVjdCB3aWR0aD0iOTAuNjUiIGhlaWdodD0iNDkuMDgiIHJ4PSI5Ljk5IiByeT0iOS45OSIgZmlsbD0ibm9uZSIvPjxwYXRoIGQ9Ik0zMi45NiAyMS42MmMtLjYxIDEuMS0xLjU1IDEuOTktMi44MSAyLjY2LTEuMjYuNjgtMi44MyAxLjAxLTQuNyAxLjAxaC0zLjQ2djguMjNoLTUuNThWMTAuNmg5LjA1YzEuODMgMCAzLjM4LjMyIDQuNjQuOTVzMi4yMSAxLjUgMi44NCAyLjYxLjk1IDIuMzguOTUgMy44MmMwIDEuMzMtLjMxIDIuNTQtLjkyIDMuNjR6bS01LjU1LTEuNTJjLjUyLS41Ljc4LTEuMjEuNzgtMi4xMnMtLjI2LTEuNjItLjc4LTIuMTItMS4zMi0uNzUtMi4zOC0uNzVoLTMuMDR2NS43NWgzLjA0YzEuMDcgMCAxLjg2LS4yNSAyLjM4LS43NVptOS4zMi0uNjVjLjcxLTEuNDIgMS42Ny0yLjUgMi44OS0zLjI3cTEuODMtMS4xNCA0LjA4LTEuMTRjMS4yOCAwIDIuNDEuMjYgMy4zOC43OHMxLjcxIDEuMjEgMi4yNCAyLjA2VjE1LjNoNS41OHYxOC4yMmgtNS41OHYtMi41OGMtLjU0Ljg1LTEuMyAxLjU0LTIuMjcgMi4wNnMtMi4xLjc4LTMuMzguNzhjLTEuNDMuMDEtMi44NC0uMzktNC4wNS0xLjE2LTEuMjItLjc3LTIuMTgtMS44Ny0yLjg5LTMuM3MtMS4wNi0zLjA4LTEuMDYtNC45NS4zNS0zLjUyIDEuMDYtNC45M1ptMTEuNDMgMS42N2MtLjc3LS44LTEuNzEtMS4yMS0yLjgzLTEuMjFzLTIuMDUuNC0yLjgzIDEuMTljLS43Ny43OS0xLjE2IDEuODktMS4xNiAzLjI4cy4zOSAyLjUgMS4xNiAzLjMxYy43Ny44MiAxLjcxIDEuMjIgMi44MyAxLjIyczIuMDUtLjQgMi44My0xLjIxYy43Ny0uODEgMS4xNi0xLjkxIDEuMTYtMy4zcy0uMzktMi40OS0xLjE2LTMuM3ptMjkuNDEtNS44Mkw2Ni4xNCA0Mi4xOGgtNi4wMWw0LjE4LTkuMjgtNy40MS0xNy42aDYuMjRsNC4yMSAxMS40IDQuMTgtMTEuNHoiLz48L2c+PC9zdmc+', //Sets the PayButton SVG (base64 encoded) logo as the icon of the main menu
            100
        );

        add_submenu_page(
            'paybutton',
            'Paywall Settings',
            'Paywall Settings',
            'manage_options',
            'paybutton-paywall',
            array( $this, 'paywall_settings_page' )
        );

        add_submenu_page(
            'paybutton',
            'Customers',
            'Customers',
            'manage_options',
            'paybutton-paywall-customers',
            array( $this, 'customers_page' )
        );

        add_submenu_page(
            'paybutton',
            'Content',
            'Content',
            'manage_options',
            'paybutton-paywall-content',
            array( $this, 'content_page' )
        );
    }

    /**
     * This function is hooked into the admin_enqueue_scripts action. It receives a
     * parameter ($hook_suffix) that identifies the current admin page. The function
     * checks if the current admin page is the "Paywall Settings" page (identified by 
     * 'toplevel_page_paybutton-paywall'). If it is, it enqueues the WordPress color
     * picker stylesheet and script, which are used on that settings page to provide 
     * color selection functionality.
    */
    public function enqueue_admin_scripts( $hook_suffix ) {
        if ( $hook_suffix === 'toplevel_page_paybutton-paywall' ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );
        }
    }

    /**
     * Loads an admin template file and passes variables to it.
     *
     * This helper function accepts a template name (without path or extension) and an
     * optional associative array of variables. The extract($args) call creates individual
     * variables from the array keys, making them available in the template. It then includes
     * the corresponding template file from the 'templates/admin/' directory.
     *
     * @param string $template_name The name of the template file (e.g., "dashboard").
     * @param array  $args          Optional associative array of data to be extracted for use in the template.
    */
    
    private function load_admin_template( $template_name, $args = array() ) {
        extract( $args );
        include PAYBUTTON_PLUGIN_DIR . 'templates/admin/' . $template_name . '.php';
    }

    /**
     * Output the Dashboard page.
     */
    public function dashboard_page() {
        $args = array(
            'generate_button_url'  => 'https://paybutton.org/#button-generator',
            'paywall_settings_url' => esc_url( admin_url( 'admin.php?page=paybutton-paywall' ) )
        );
        $this->load_admin_template( 'dashboard', $args );
    }

    /**
     * Output the Paywall Settings page.
     */
    public function paywall_settings_page() {
        if ( isset( $_POST['paybutton_paywall_save_settings'] ) ) {
            $this->save_settings();
            $settings_saved = true;
        } else {
            $settings_saved = false;
        }

        $args = array(
            'settings_saved'          => $settings_saved,
            'ecash_address'           => get_option( 'paybutton_paywall_ecash_address', '' ),
            'default_price'           => get_option( 'paybutton_paywall_default_price', 10 ),
            'current_unit'            => get_option( 'paybutton_paywall_unit', 'XEC' ),
            'btn_text'                => get_option( 'paybutton_text', 'Pay to Unlock' ),
            'hvr_text'                => get_option( 'paybutton_hover_text', 'Send Payment' ),
            'clr_primary'             => get_option( 'paybutton_color_primary', '#0074c2' ),
            'clr_secondary'           => get_option( 'paybutton_color_secondary', '#fefbf8' ),
            'clr_tertiary'            => get_option( 'paybutton_color_tertiary', '#000000' ),
            'hide_comments_checked'   => ( '1' === get_option( 'paybutton_hide_comments_until_unlocked', '1' ) ),
            // Sticky header settings
            'sticky_header_bg_color'    => get_option( 'paybutton_sticky_header_bg_color', '#007bff' ),
            'sticky_header_text_color'  => get_option( 'paybutton_sticky_header_text_color', '#FFFFFF' ),
            'profile_button_bg_color'   => get_option( 'paybutton_profile_button_bg_color', '#ffc107' ),
            'profile_button_text_color' => get_option( 'paybutton_profile_button_text_color', '#000000' ),
            'logout_button_bg_color'    => get_option( 'paybutton_logout_button_bg_color', '#d9534f' ),
            'logout_button_text_color'  => get_option( 'paybutton_logout_button_text_color', '#FFFFFF' ),
            // blacklist
            'blacklist'                 => get_option( 'paybutton_blacklist', array() ),
            //Public key
            'paybutton_public_key'      => get_option( 'paybutton_public_key', '' ),
        );
        $this->load_admin_template( 'paywall-settings', $args );
    }

    /**
     * Save settings submitted via the Paywall Settings page.
     */
    private function save_settings() {
        $address         = sanitize_text_field( $_POST['ecash_address'] );
        $unit            = sanitize_text_field( $_POST['unit'] );
        $raw_price       = floatval( $_POST['default_price'] );
        $button_text     = sanitize_text_field( $_POST['paybutton_text'] );
        $hover_text      = sanitize_text_field( $_POST['paybutton_hover_text'] );
        $color_primary   = sanitize_hex_color( $_POST['paybutton_color_primary'] );
        $color_secondary = sanitize_hex_color( $_POST['paybutton_color_secondary'] );
        $color_tertiary  = sanitize_hex_color( $_POST['paybutton_color_tertiary'] );
        $hide_comments   = isset( $_POST['paybutton_hide_comments_until_unlocked'] ) ? '1' : '0';

        if ( $unit === 'XEC' && $raw_price < 5.5 ) {
            $raw_price = 5.5;
        }

        update_option( 'paybutton_paywall_ecash_address', $address );
        update_option( 'paybutton_paywall_unit', $unit );
        update_option( 'paybutton_paywall_default_price', $raw_price );
        update_option( 'paybutton_text', $button_text );
        update_option( 'paybutton_hover_text', $hover_text );
        update_option( 'paybutton_color_primary', $color_primary ?: '#0074c2' );
        update_option( 'paybutton_color_secondary', $color_secondary ?: '#fefbf8' );
        update_option( 'paybutton_color_tertiary', $color_tertiary ?: '#000000' );
        update_option( 'paybutton_hide_comments_until_unlocked', $hide_comments );

        update_option( 'paybutton_sticky_header_bg_color', sanitize_hex_color( $_POST['sticky_header_bg_color'] ) ?: '#007bff' );
        update_option( 'paybutton_sticky_header_text_color', sanitize_hex_color( $_POST['sticky_header_text_color'] ) ?: '#FFFFFF' );
        update_option( 'paybutton_profile_button_bg_color', sanitize_hex_color( $_POST['profile_button_bg_color'] ) ?: '#ffc107' );
        update_option( 'paybutton_profile_button_text_color', sanitize_hex_color( $_POST['profile_button_text_color'] ) ?: '#000' );
        update_option( 'paybutton_logout_button_bg_color', sanitize_hex_color( $_POST['logout_button_bg_color'] ) ?: '#d9534f' );
        update_option( 'paybutton_logout_button_text_color', sanitize_hex_color( $_POST['logout_button_text_color'] ) ?: '#FFFFFF' );
        // New unlocked content indicator option:
        update_option( 'paybutton_scroll_to_unlocked', isset( $_POST['paybutton_scroll_to_unlocked'] ) ? '1' : '0' );

        // Save the blacklist
        if ( isset( $_POST['paybutton_blacklist'] ) ) {
            $raw_blacklist = sanitize_text_field( $_POST['paybutton_blacklist'] );
            $blacklist = array_map( 'trim', explode( ',', $raw_blacklist ) );
            update_option( 'paybutton_blacklist', $blacklist );
        }
        //Adding the new public key option
        if ( isset( $_POST['paybutton_public_key'] ) ) {
            $public_key = sanitize_text_field( $_POST['paybutton_public_key'] );
            update_option( 'paybutton_public_key', $public_key );
        }    
    }

    /**
     * Output the Customers page.
     */
    public function customers_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paybutton_paywall_unlocked';

        if ( ! empty( $_GET['address'] ) ) {
            $user_address = sanitize_text_field( $_GET['address'] );
            $args = array(
                'user_address' => $user_address,
                'rows' => $wpdb->get_results( $wpdb->prepare(
                    "SELECT /*DISTINCT*/ post_id, tx_amount, tx_hash, tx_timestamp, is_logged_in
                     FROM $table_name
                     WHERE ecash_address = %s
                     ORDER BY id DESC",
                    $user_address
                ) )
            );
            $this->load_admin_template( 'customers', $args );
            return;
        }

        $allowed_cols = array('ecash_address','unlocked_count','total_paid','last_unlock_ts');
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'unlocked_count';
        if ( ! in_array( $orderby, $allowed_cols ) ) {
            $orderby = 'unlocked_count';
        }
        $order = isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( $_GET['order'] ) ) : 'DESC';
        if ( ! in_array( $order, array('ASC','DESC') ) ) {
            $order = 'DESC';
        }

        // Modified to also count how many unlocks happened with is_logged_in=1
        $results = $wpdb->get_results("
            SELECT ecash_address,
                   COUNT(post_id) AS unlocked_count,
                   SUM(tx_amount) AS total_paid,
                   SUM(CASE WHEN is_logged_in = 1 THEN 1 ELSE 0 END) AS unlocked_logged_in_count,
                   MAX(tx_timestamp) AS last_unlock_ts
            FROM $table_name
            GROUP BY ecash_address
        ");

        $customers = array();
        if ( ! empty( $results ) ) {
            foreach ( $results as $r ) {
                $customers[] = array(
                    'ecash_address'            => $r->ecash_address,
                    'unlocked_count'           => (int) $r->unlocked_count,
                    'unlocked_logged_in_count' => (int) $r->unlocked_logged_in_count,
                    'total_paid'               => (float) $r->total_paid,
                    'last_unlock_ts'           => $r->last_unlock_ts
                );
            }
        }

        usort( $customers, function( $a, $b ) use ( $orderby, $order ) {
            if ( $a[$orderby] === $b[$orderby] ) {
                return 0;
            }
            if ( $order === 'ASC' ) {
                return ( $a[$orderby] < $b[$orderby] ) ? -1 : 1;
            } else {
                return ( $a[$orderby] > $b[$orderby] ) ? -1 : 1;
            }
        } );

        $args = array(
            'customers'      => $customers,
            'total_customers'=> count( $customers ),
            'grand_total_xec'=> array_reduce($customers, function($carry, $item) {
                return $carry + $item['total_paid'];
            }, 0),
            'base_url'       => remove_query_arg( array( 'orderby', 'order' ) ),
            'orderby'        => $orderby,
            'order'          => $order,
        );
        $this->load_admin_template( 'customers', $args );
    }

    /**
     * Output the Content page.
     */
    public function content_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paybutton_paywall_unlocked';

        $posts = get_posts( array(
            'post_type'   => array( 'post', 'page' ),
            'post_status' => 'publish',
            'numberposts' => -1
        ) );

        $unlock_counts = $wpdb->get_results( "
            SELECT post_id, COUNT(*) AS unlock_count
            FROM $table_name
            GROUP BY post_id
        " );

        $unlock_sums = $wpdb->get_results( "
            SELECT post_id, SUM(tx_amount) AS total_earned
            FROM $table_name
            GROUP BY post_id
        " );

        // NEW: Count how many unlocks had is_logged_in = 1
        $unlock_logged_in_counts = $wpdb->get_results( "
            SELECT post_id, COUNT(*) AS unlock_logged_in_count
            FROM $table_name
            WHERE is_logged_in = 1
            GROUP BY post_id
        " );

        $counts_map       = array();
        $sums_map         = array();
        $logged_in_map    = array();
        $total_unlocks    = 0;

        if ( ! empty( $unlock_counts ) ) {
            foreach ( $unlock_counts as $row ) {
                $counts_map[ $row->post_id ] = (int) $row->unlock_count;
                $total_unlocks += (int) $row->unlock_count;
            }
        }
        if ( ! empty( $unlock_sums ) ) {
            foreach ( $unlock_sums as $row ) {
                $sums_map[ $row->post_id ] = (float) $row->total_earned;
            }
        }
        if ( ! empty( $unlock_logged_in_counts ) ) {
            foreach ( $unlock_logged_in_counts as $row ) {
                $logged_in_map[ $row->post_id ] = (int) $row->unlock_logged_in_count;
            }
        }

        $contentData = array();
        foreach ( $posts as $p ) {
            $pid = $p->ID;
            $contentData[] = array(
                'post_id'               => $pid,
                'title'                 => get_the_title( $pid ),
                'unlock_count'          => isset( $counts_map[ $pid ] ) ? $counts_map[ $pid ] : 0,
                'unlock_logged_in_count'=> isset( $logged_in_map[ $pid ] ) ? $logged_in_map[ $pid ] : 0,
                'total_earned'          => isset( $sums_map[ $pid ] ) ? $sums_map[ $pid ] : 0.0
            );
        }

        $allowed_cols = array('title','unlock_count','total_earned');
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'unlock_count';
        if ( ! in_array( $orderby, $allowed_cols ) ) {
            $orderby = 'unlock_count';
        }
        $order = isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( $_GET['order'] ) ) : 'DESC';
        if ( ! in_array( $order, array('ASC','DESC') ) ) {
            $order = 'DESC';
        }

        usort( $contentData, function( $a, $b ) use ( $orderby, $order ) {
            if ( $a[$orderby] == $b[$orderby] ) {
                return 0;
            }
            if ( $order === 'ASC' ) {
                return ( $a[$orderby] < $b[$orderby] ) ? -1 : 1;
            } else {
                return ( $a[$orderby] > $b[$orderby] ) ? -1 : 1;
            }
        } );

        // Calculate the grand total of XEC earned across all content
        $grand_total_earned = 0;
            foreach ( $contentData as $row ) {
            $grand_total_earned += $row['total_earned'];
        }

        $args = array(
            'contentData'   => $contentData,
            'total_unlocks' => $total_unlocks,
            'grand_total_earned'=> $grand_total_earned, // NEW
            'base_url'      => remove_query_arg( array( 'orderby', 'order' ) ),
            'orderby'       => $orderby,
            'order'         => $order,
        );
        $this->load_admin_template( 'content', $args );
    }
}
