<?php
class IR_Queue {

    private $queue_table_name;

    public function init() {
        global $wpdb;
        $this->queue_table_name = $wpdb->prefix . 'ir_import_queue';

         add_action('init', array($this, 'check_queue_status'));
        add_action('wp_ajax_ir_process_queue_item', array($this, 'process_queue_item'));
         add_action( 'wp_ajax_nopriv_ir_process_queue_item', array($this, 'process_queue_item') ); // For non-logged-in users (if needed)
         register_activation_hook(IR_PLUGIN_DIR . 'impact-radius-woocommerce-integration.php', array($this, 'create_queue_table'));
        register_deactivation_hook(IR_PLUGIN_DIR . 'impact-radius-woocommerce-integration.php', array($this, 'drop_queue_table'));
   }

    function create_queue_table() {
       global $wpdb;
       $charset_collate = $wpdb->get_charset_collate();
         $sql = "CREATE TABLE {$this->queue_table_name} (
             id INT AUTO_INCREMENT PRIMARY KEY,
             catalog_id VARCHAR(255) NOT NULL,
             mapping TEXT NOT NULL,
             status VARCHAR(20) NOT NULL DEFAULT 'pending',
             created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
         ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
         dbDelta( $sql );
    }

   function drop_queue_table() {
       global $wpdb;
        $sql = "DROP TABLE IF EXISTS {$this->queue_table_name}";
        $wpdb->query($sql);
    }

    function add_item($catalog_id, $mapping) {
       global $wpdb;
         $wpdb->insert(
            $this->queue_table_name,
            array(
                'catalog_id' => $catalog_id,
                 'mapping' => wp_json_encode($mapping),
            )
       );
    }

   function get_pending_items() {
         global $wpdb;
         return $wpdb->get_results( "SELECT * FROM {$this->queue_table_name} WHERE status = 'pending' LIMIT 1", ARRAY_A );
    }


    function set_item_status($item_id, $status) {
        global $wpdb;
         $wpdb->update(
            $this->queue_table_name,
             array( 'status' => $status ),
            array( 'id' => $item_id )
         );
    }

     function check_queue_status() {
         if (defined('DOING_CRON') && DOING_CRON) {
           $this->process_queue();
       }
    }


    function process_queue() {
       $item = $this->get_pending_items();
       if(!$item) {
             return;
         }
        $item = $item[0];
        $catalog_id = $item['catalog_id'];
        $mapping = json_decode($item['mapping'], true);

         $importer = new IR_Importer(new IR_Api(), new IR_Log(), $this);
         $results = $importer->process_catalog_items($catalog_id, $mapping, 0);
         if(is_wp_error($results)) {
                $this->set_item_status($item['id'], 'error');
             return;
        }
         $this->set_item_status($item['id'], 'completed');

    }
    function process_queue_item() {
        if (!check_ajax_referer('ir_ajax_nonce', '_ajax_nonce', false)) {
           wp_send_json_error("Security check failed.");
           return;
        }
          $this->process_queue();
          wp_send_json_success();

     }
  }