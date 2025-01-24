<?php
class IR_Settings {
    private $settings_group = 'ir_settings';
    private $settings_page = 'impact_radius_catalogs';

    public function init() {
        add_action('admin_init', array($this, 'register_settings'));
    }

   public function add_admin_menu() {
      add_menu_page(
            'Impact Radius Catalogs',
            'Impact Radius',
            'manage_options',
            'impact_radius_catalogs',
            array($this, 'render_settings_page'),
            'dashicons-store',
             25
        );
   }


    public function register_settings() {
        register_setting(
            $this->settings_group,
            'ir_api_credentials',
            array($this, 'sanitize_credentials')
        );

        add_settings_section(
            'ir_api_section',
            'Impact Radius API Credentials',
            array($this, 'api_section_callback'),
             $this->settings_page
        );

        add_settings_field(
            'ir_account_sid',
            'Account SID',
            array($this, 'account_sid_callback'),
            $this->settings_page,
            'ir_api_section'
        );

        add_settings_field(
            'ir_auth_token',
            'Auth Token',
            array($this, 'auth_token_callback'),
            $this->settings_page,
            'ir_api_section'
        );

    }

    public function render_settings_page() {
         ?>
        <div class="wrap">
            <h2>Impact Radius Settings</h2>
            <form method="post" action="options.php">
                <?php
               settings_fields( 'ir_settings' );
                do_settings_sections( 'impact_radius_catalogs' );
                submit_button();
                ?>
            </form>
             <?php
              $importer = new IR_Importer(new IR_Api(), new IR_Log(), new IR_Queue());
              $importer->render_admin_page();
            ?>
        </div>
        <?php
    }

     public function api_section_callback() {
       echo 'Enter your Impact Radius API credentials.';
    }


    public function account_sid_callback() {
        $options = get_option('ir_api_credentials');
        $account_sid = isset($options['account_sid']) ? esc_attr($options['account_sid']) : '';
        echo '<input type="text" name="ir_api_credentials[account_sid]" value="' . $account_sid . '" />';
    }

    public function auth_token_callback() {
        $options = get_option('ir_api_credentials');
        $auth_token = isset($options['auth_token']) ? esc_attr($options['auth_token']) : '';
        echo '<input type="text" name="ir_api_credentials[auth_token]" value="' . $auth_token . '" />';
    }


    public function sanitize_credentials($input) {
        $sanitized_input = array();
        if (isset($input['account_sid'])) {
            $sanitized_input['account_sid'] = sanitize_text_field($input['account_sid']);
         }
         if (isset($input['auth_token'])) {
            $sanitized_input['auth_token'] = sanitize_text_field($input['auth_token']);
         }
        return $sanitized_input;
    }

       public function get_credentials() {
        $credentials = get_option('ir_api_credentials');
        return $credentials;
     }
}
