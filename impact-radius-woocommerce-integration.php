<?php
/*
Plugin Name: Impact Radius WooCommerce Integration
Description: A plugin to integrate Impact Radius product feeds into WooCommerce.
Version: 1.0.18
*/

// Check if WooCommerce is active
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    // Define plugin paths
    define('IR_PLUGIN_DIR', plugin_dir_path(__FILE__));
    define('IR_PLUGIN_URL', plugin_dir_url(__FILE__));

    // Include necessary files
    require_once IR_PLUGIN_DIR . 'includes/class-ir-settings.php';
    require_once IR_PLUGIN_DIR . 'includes/class-ir-api.php';
    require_once IR_PLUGIN_DIR . 'includes/class-ir-importer.php';
    require_once IR_PLUGIN_DIR . 'includes/class-ir-log.php';
    require_once IR_PLUGIN_DIR . 'includes/class-ir-queue.php';

    // Initialize Settings
    $ir_settings = new IR_Settings();
    $ir_settings->init();

    // Initialize Logging
     $ir_log = new IR_Log();

    // Initialize the queue
    $ir_queue = new IR_Queue();
    $ir_queue->init();

    // Initialize Importer
     $ir_importer = new IR_Importer(new IR_Api(), $ir_log, $ir_queue);

       // Initialize the main plugin
       add_action('admin_menu', array($ir_settings, 'add_admin_menu'));

    function ir_render_catalogs_page() {
        $ir_importer = new IR_Importer(new IR_Api(), new IR_Log(), new IR_Queue());
        $ir_importer->render_admin_page();
    }

     add_action( 'admin_enqueue_scripts', 'ir_enqueue_admin_scripts' );
     function ir_enqueue_admin_scripts($hook) {
         if ( 'toplevel_page_impact_radius_catalogs' != $hook ) {
             return;
        }
          wp_enqueue_style( 'ir-admin-style',  IR_PLUGIN_URL . 'assets/css/style.css', array(), '1.0.0', 'all' );
         wp_enqueue_script( 'ir-admin-script',  IR_PLUGIN_URL . 'assets/js/main.js', array('jquery'), '1.0.0', true );
         wp_localize_script('ir-admin-script', 'ir_ajax_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
             'ajax_nonce' => wp_create_nonce('ir_ajax_nonce')
         ));
     }


} else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('The Impact Radius WooCommerce Integration plugin requires WooCommerce to be installed and active.', 'impact-radius');
            echo '</p></div>';
        });
        return;
}
