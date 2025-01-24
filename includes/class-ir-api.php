<?php
class IR_Api {
    private $settings;
    public function __construct() {
           $this->settings = new IR_Settings();
    }
    public function get_catalogs() {
         $credentials = $this->settings->get_credentials();
        $account_sid = isset($credentials['account_sid']) ? $credentials['account_sid'] : '';
        $auth_token = isset($credentials['auth_token']) ? $credentials['auth_token'] : '';

       if(empty($account_sid) || empty($auth_token)) {
            return new WP_Error('missing_credentials', 'Missing Impact Radius API credentials.');
         }
        $url = "https://api.impact.com/Mediapartners/$account_sid/Catalogs";
       $headers = array(
             'Authorization' => 'Basic ' . base64_encode($account_sid . ':' . $auth_token),
           'Accept' => 'application/json',
        );
       $response = wp_remote_get($url, array('headers' => $headers, 'timeout' => 30));
        if (is_wp_error($response)) {
            return $response;
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Invalid JSON response from API');
        }
         return isset($body['Catalogs']) ? $body['Catalogs'] : [];
    }
    public function get_catalog_items($catalog_id, $page = 1, $per_page = 20) {
         $credentials = $this->settings->get_credentials();
         $account_sid = isset($credentials['account_sid']) ? $credentials['account_sid'] : '';
         $auth_token = isset($credentials['auth_token']) ? $credentials['auth_token'] : '';

          if(empty($account_sid) || empty($auth_token)) {
               return new WP_Error('missing_credentials', 'Missing Impact Radius API credentials.');
           }
         $url = "https://api.impact.com/Mediapartners/$account_sid/Catalogs/$catalog_id/Items";
          $current_url = $url . '?Page=' . $page . '&PageSize=' . $per_page;
          $headers = array(
             'Authorization' => 'Basic ' . base64_encode($account_sid . ':' . $auth_token),
               'Accept' => 'application/json',
           );
         $response = wp_remote_get($current_url, array('headers' => $headers, 'timeout' => 30));

         if (is_wp_error($response)) {
             return $response;
          }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

         if (json_last_error() !== JSON_ERROR_NONE) {
             return new WP_Error('json_error', 'Invalid JSON response from API');
         }

        return $data;

     }
}