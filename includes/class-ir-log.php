<?php
class IR_Log {
    private $log_file;
    private $initialized = false;


    public function __construct() {
         $this->log_file = ABSPATH . 'wp-content/plugins/impact-radius-log.txt';
    }

    public function add_message($message, $level = 'info') {
        if (!$this->initialized) {
            file_put_contents($this->log_file, "");
            $this->initialized = true;
        }

         $log_message = date('Y-m-d H:i:s') . " - [" . strtoupper($level) . "] - " . $message . "\n";
        if (false === file_put_contents($this->log_file, $log_message, FILE_APPEND)) {
            error_log("Failed to write to Impact Radius log file.");
        }
     }
     public function create_log_download_link() {
        $log_file_url = content_url('plugins/impact-radius-log.txt');
        echo '<div class="notice notice-info"><p><a href="' . esc_url($log_file_url) . '" download>Download Log File</a></p></div>';
    }
}