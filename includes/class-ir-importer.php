<?php
  class IR_Importer {
    private $api;
    private $log;
    private $queue;
   public function __construct(IR_Api $api, IR_Log $log, IR_Queue $queue) {
       $this->api = $api;
       $this->log = $log;
       $this->queue = $queue;
    }

  public function render_admin_page() {
      $mapping = array(
             'post_title' => 'Name',
             'post_content' => 'Description',
             'sku' => 'CatalogItemId',
             'regular_price' => 'OriginalPrice',
             'sale_price' => 'CurrentPrice',
             'tax:brand' => 'Manufacturer',
             'button_text' => 'dynamic_button_text',
            'images' => 'ImageUrl',
            'meta:_aioseo_title' => 'Name',
            'meta:_aioseo_description' => 'Description',
            'meta:_aioseo_og_article_tags' => 'Labels',
            'tax:product_tag' => 'Labels',
            'meta:attribute_pa_color' => 'Colors',
            'meta:attribute_pa_size' => 'Size',
            'UPC' => 'Gtin',
            'meta:_wcev_external_url' => 'Url',
             'post_status' => 'publish',
            'menu_order' => 0,
            'post_author' => 1,
            'comment_status' => 'closed',
            'stock_status' => 'StockAvailability',
            'backorders' => 'no',
            'sold_individually' => 'no',
            'manage_stock' => 'yes',
             'tax_status' => 'none',
           'downloadable' => 'no',
            'virtual' => 'no',
            'meta:_backorders' => 'no',
            'meta:_downloadable' => 'no',
             'is_parent' => 'IsParent',
             'parent_sku' => 'ParentSku',
             'stock_quantity' => 'Quantity'
         );

       $show_catalog_form = false;
       $catalog_id = null;
       if (isset($_POST['get_merchants'])) {
           $this->list_impact_radius_catalogs();
            $show_catalog_form = true;
        } elseif (isset($_POST['select_catalog'])) {
            $show_catalog_form = true;
            $catalog_id = $_POST['catalog_id'];
        } elseif (isset($_POST['update_woocommerce'])) {
              if (!isset($_POST['impact_radius_nonce_field']) || !wp_verify_nonce($_POST['impact_radius_nonce_field'], 'impact_radius_nonce')) {
                  die('Security check failed.');
              }

            $catalog_id = $_POST['catalog_id'];
             echo '<div id="ir-progress-bar" style="width: 100%; background-color: #f1f1f1; margin-bottom: 20px;">
                     <div id="ir-progress" style="width: 0%; height: 20px; background-color: #4CAF50;"></div>
                </div>';
             echo '<div id="ir-progress-text">Starting Import...</div>';
            if (!$catalog_id) {
                 $this->log->add_message('No catalog selected.', 'error');
                echo '<div class="notice notice-error"><p>Error: No catalog ID found.</p></div>';
            } else {
                  echo '<div id="ir-import-results"></div>';
                   echo '<script type="text/javascript">
                            jQuery(document).ready(function($) {
                                var catalog_id = ' . json_encode($catalog_id) . ';
                                var mapping = ' . json_encode($mapping) . ';


                                function updateProgressBar(percentage, message) {
                                     $("#ir-progress").css("width", percentage + "%");
                                      $("#ir-progress-text").text(message);
                                }

                                function performImport(catalogId, mappingData, offset = 0) {
                                    $.ajax({
                                        url: ir_ajax_params.ajax_url,
                                        type: "POST",
                                        data: {
                                             action: "ir_process_batch",
                                            catalog_id: catalogId,
                                            mapping: mappingData,
                                            offset: offset,
                                            _ajax_nonce: ir_ajax_params.ajax_nonce
                                        },
                                        success: function(response) {
                                             if (response.success) {
                                                  var newOffset = response.data.offset;
                                                 var completed = response.data.completed;
                                                  var percentage = response.data.percentage;
                                                 var message = response.data.message;
                                                  var results = response.data.results;


                                                   updateProgressBar(percentage, message);
                                                    $("#ir-import-results").html(results);


                                                    if (!completed) {
                                                       performImport(catalogId, mappingData, newOffset);
                                                     }
                                               } else {
                                                   updateProgressBar(0, "Import failed: " + response.data);
                                                   $("#ir-import-results").html(response.data);
                                                }
                                           },
                                            error: function(jqXHR, textStatus, errorThrown) {
                                                 updateProgressBar(0, "Import failed with AJAX error: " + textStatus + " - " + errorThrown);
                                                 $("#ir-import-results").html("<p>Import failed with AJAX error: " + textStatus + " - " + errorThrown + "</p>");
                                             }
                                   });
                               }
                               performImport(catalog_id, mapping);
                            });
                         </script>';
                }
            }

            if ($show_catalog_form) {
                 echo '<form method="post">';
                wp_nonce_field('impact_radius_nonce', 'impact_radius_nonce_field');
                 echo '<input type="hidden" name="catalog_id" value="' . esc_attr($catalog_id) . '">';
                 echo '<input type="submit" name="update_woocommerce" value="Update Selected Merchants">';
                 echo '</form>';
             } else {
                echo '<form method="post">';
                 wp_nonce_field('impact_radius_nonce', 'impact_radius_nonce_field');
                echo '<input type="submit" name="get_merchants" value="Get Merchants"></form>';
              }
              $this->log->create_log_download_link();
        }

        public function list_impact_radius_catalogs() {
             $catalogs = $this->api->get_catalogs();
             if (is_wp_error($catalogs)) {
                 $this->log->add_message('Failed to retrieve catalogs: ' . $catalogs->get_error_message(), 'error');
                  echo '<div class="notice notice-error"><p>Failed to retrieve catalogs: ' . $catalogs->get_error_message() . '</p></div>';
                return;
              }

             if (empty($catalogs)) {
                 $this->log->add_message('No catalogs found.', 'warning');
                echo '<div class="notice notice-warning"><p>No catalogs found.</p></div>';
                return;
             }

              echo '<form method="post">';
              wp_nonce_field('impact_radius_nonce', 'impact_radius_nonce_field');
             foreach ($catalogs as $catalog) {
                 echo '<input type="radio" name="catalog_id" value="' . esc_attr($catalog['Id']) . '" required>' . esc_html($catalog['Name']) . ' (' . esc_html($catalog['AdvertiserName']) . ')<br>';
             }
              echo '<input type="submit" name="select_catalog" value="Select Catalog">';
            echo '</form>';
         }

      public function process_batch_callback() {
          if (!check_ajax_referer('ir_ajax_nonce', '_ajax_nonce', false)) {
                wp_send_json_error("Security check failed.");
              return;
         }
            $catalog_id = isset($_POST['catalog_id']) ? sanitize_text_field($_POST['catalog_id']) : null;
           $mapping = isset($_POST['mapping']) ? $_POST['mapping'] : [];
           $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
          if (!$catalog_id || empty($mapping)) {
                wp_send_json_error("Missing catalog ID or mapping data.");
              return;
           }
           $results = $this->process_catalog_items($catalog_id, $mapping, $offset);
           if (is_wp_error($results)) {
               wp_send_json_error($results->get_error_message());
                return;
           }
            wp_send_json_success($results);
       }

    private function process_catalog_items($catalog_id, $mapping, $offset = 0) {
          global $products_added, $products_updated, $products_removed;
           $per_page = 20;
           $data = $this->api->get_catalog_items($catalog_id,  (($offset / $per_page) + 1) , $per_page );
          if (is_wp_error($data)) {
                return $data;
             }
           $items = $data['Items'] ?? [];
           $total_items = isset($data['Total']) ? intval($data['Total']) : 0;
          $completed = ($offset + count($items)) >= $total_items;
         $parent_skus = [];
          foreach($items as $item) {
                if(isset($item['ParentSku']) && !empty($item['ParentSku'])) {
                     if(!in_array($item['ParentSku'], $parent_skus)) {
                          $parent_skus[] = $item['ParentSku'];
                       }
                   }
            }
           // Create parent products first
            foreach($items as $item) {
                if(isset($item['IsParent']) && $item['IsParent'] === 'true') {
                     $this->update_single_wc_product($item, $mapping);
                }
             }
            // Create child products
             foreach($items as $item) {
               if(!isset($item['IsParent']) || $item['IsParent'] !== 'true' && (!isset($item['ParentSku']) || empty($item['ParentSku']) || in_array($item['ParentSku'], $parent_skus) )) {
                   $this->update_single_wc_product($item, $mapping);
                }
            }
          $offset += count($items);
           $percentage = $total_items > 0 ? min(($offset / $total_items) * 100, 100) : 0;
            $message = 'Importing products... ' . round($percentage, 2) . '%';

            $results = '<p>Products Added: ' . $products_added . '</p>';
            $results .= '<p>Products Updated: ' . $products_updated . '</p>';
             $results .= '<p>Products Removed: ' . $products_removed . '</p>';

            return array(
                 'offset' => $offset,
               'completed' => $completed,
               'percentage' => $percentage,
               'message' => $message,
                'results' => $results,
           );
      }
      // Image Cache function
      private function cache_image($image_url) {
          $upload_dir = wp_upload_dir();
            $image_name = basename($image_url);
          $cache_path = $upload_dir['basedir'] . '/ir_cache/' . $image_name;
         $cache_url = $upload_dir['baseurl'] . '/ir_cache/' . $image_name;
            $file_extension = pathinfo($image_name, PATHINFO_EXTENSION);
           if (file_exists($cache_path)) {
               return $cache_url;
           }

          $response = wp_remote_get($image_url);
         if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
               $this->log->add_message("Failed to download or cache image: " . $image_url . ' Error:' . ($response->get_error_message() ?? 'HTTP Error: ' . wp_remote_retrieve_response_code($response)), 'error');
              return false;
           }
          $image_data = wp_remote_retrieve_body($response);
           if (!file_exists($upload_dir['basedir'] . '/ir_cache')) {
              wp_mkdir_p($upload_dir['basedir'] . '/ir_cache');
           }
           if (file_put_contents($cache_path, $image_data) === false) {
                $this->log->add_message("Failed to write cache file " . $cache_path, 'error');
              return false;
          }
           return $cache_url;
      }

      // Image Handling
        private function set_product_images_from_urls($product_id, $image_urls) {
           $attach_ids = [];
          foreach ($image_urls as $image_url) {
              if (empty($image_url) || !filter_var($image_url, FILTER_VALIDATE_URL)) {
                   $this->log->add_message("Invalid image URL: $image_url for product ID: " . $product_id, "error");
                  continue;
                }
               $cached_image_url = $this->cache_image($image_url);
             if (!$cached_image_url) {
                 $this->log->add_message("Failed to get cached image for URL: " . $image_url . " for product ID: " . $product_id, 'error');
                  continue;
               }
              $tmp = wp_tempnam();
              $file = [
                    'name' => basename($cached_image_url),
                    'tmp_name' => $tmp,
                ];
            if(!copy($cached_image_url, $tmp)) {
                    $this->log->add_message("Failed to copy cached file to temporary path " . $cached_image_url . " for product ID: " . $product_id, 'error');
                   continue;
              }
            $attachment_id = media_handle_sideload($file, $product_id);
             wp_delete_file($tmp);
            if (is_wp_error($attachment_id)) {
                    $this->log->add_message("Error sideloading image: " . $attachment_id->get_error_message() . " URL: $image_url" . " for product ID: " . $product_id, "error");
                continue;
             }
            $attach_ids[] = $attachment_id;
            $this->log->add_message("Successfully uploaded image: $image_url. Attachment ID: $attachment_id", "debug");
        }

          if ($attach_ids) {
             set_post_thumbnail($product_id, array_shift($attach_ids));
               update_post_meta($product_id, '_product_image_gallery', implode(',', $attach_ids));
           } else {
                $this->log->add_message("No images assigned to product ID: $product_id", "warning");
           }
        }

        // Product Update Logic
        private function update_single_wc_product($item, $mapping) {
           global $products_added, $products_updated, $products_removed;
            $product_id = wc_get_product_id_by_sku($item['CatalogItemId']);
           $manufacturer = $item['Manufacturer'] ?? '';

           if (!$product_id) {
                $product = new WC_Product();
                 $products_added++;
           } else {
               $product = wc_get_product($product_id);
                 if (!$product) {
                     $this->log->add_message("Corrupted product data for SKU: " . $item['CatalogItemId'], "error");
                     return;
                 }
               $products_updated++;
         }

           // Variation Logic
           if (isset($item['ParentSku']) && !empty($item['ParentSku'])) {
               $parent_id = wc_get_product_id_by_sku($item['ParentSku']);
                if ($parent_id) {
                   $parent_product = wc_get_product($parent_id);
                   if(!$parent_product) {
                       $this->log->add_message("Parent product with SKU " . $item['ParentSku'] . " not found", "error");
                        return;
                     }
                     $product->set_parent_id($parent_id);
                   foreach ($mapping as $wc_field => $ir_field) {
                      if (isset($item[$ir_field])) {
                           switch ($wc_field) {
                                case 'sku':
                                    $product->set_sku($item[$ir_field]);
                                   break;
                               case 'regular_price':
                                   $product->set_regular_price($item[$ir_field]);
                                    break;
                                case 'sale_price':
                                   $product->set_sale_price($item[$ir_field]);
                                    break;
                                  case 'images':
                                     if (isset($item['ImageUrl']) && is_array($item['ImageUrl'])) {
                                          $this->set_product_images_from_urls($product->get_id(), $item['ImageUrl']);
                                       } elseif (isset($item['ImageUrl']) && is_string($item['ImageUrl'])) {
                                           $this->set_product_images_from_urls($product->get_id(), array($item['ImageUrl']));
                                       } else {
                                          $this->log->add_message("No valid ImageUrl found for product SKU: " . $item['CatalogItemId'], 'warning');
                                       }
                                      break;
                                   case 'stock_status':
                                      $stock_status = strtolower($item[$ir_field]) === 'instock' ? 'instock' : 'outofstock';
                                        $product->set_stock_status($stock_status);
                                        break;
                                  case 'stock_quantity':
                                       $product->set_stock_quantity($item[$ir_field]);
                                      break;
                                  default:
                                       if (strpos($wc_field, 'meta:') === 0) {
                                           $meta_key = explode(':', $wc_field)[1];
                                            $product->update_meta_data($meta_key, $item[$ir_field]);
                                       }
                                      break;
                               }
                        }
                    }
                   $product->save();
                    return;
                 } else {
                     $this->log->add_message("Parent product with SKU " . $item['ParentSku'] . " not found for product SKU " . $item['CatalogItemId'], "warning");
                    return;
                }

           }

             foreach ($mapping as $wc_field => $ir_field) {
                 if (isset($item[$ir_field])) {
                     switch ($wc_field) {
                         case 'post_title':
                           $product->set_name($item[$ir_field]);
                           break;
                        case 'post_content':
                            $product->set_description($item[$ir_field]);
                            break;
                        case 'sku':
                            $product->set_sku($item[$ir_field]);
                           break;
                        case 'UPC':
                            $product->update_meta_data('_upc', $item[$ir_field]);
                            break;
                         case 'is_parent':
                            $product->set_virtual($item[$ir_field] !== 'true');
                             $product->set_type($item[$ir_field] === 'true' ? 'variable' : 'simple');
                             break;
                        case 'images':
                             if (isset($item['ImageUrl']) && is_array($item['ImageUrl'])) {
                                  $this->set_product_images_from_urls($product->get_id(), $item['ImageUrl']);
                            } elseif (isset($item['ImageUrl']) && is_string($item['ImageUrl'])) {
                                $this->set_product_images_from_urls($product->get_id(), array($item['ImageUrl']));
                            } else {
                                 $this->log->add_message("No valid ImageUrl found for product SKU: " . $item['CatalogItemId'], 'warning');
                             }
                            break;
                        case 'stock_status':
                            $stock_status = strtolower($item[$ir_field]) === 'instock' ? 'instock' : 'outofstock';
                             $product->set_stock_status($stock_status);
                            break;
                        case 'stock_quantity':
                             $product->set_stock_quantity($item[$ir_field]);
                           break;
                        case 'button_text':
                             $product->update_meta_data('_dynamic_button_text', $item[$ir_field]);
                            break;
                         default:
                            if (strpos($wc_field, 'tax:') === 0) {
                                $taxonomy = explode(':', $wc_field)[1];
                                 $terms = array_filter(array_map('trim', explode(",", $item[$ir_field])));
                                if(!empty($terms)) {
                                       $product->set_props(array(
                                         $taxonomy => $terms
                                        ));
                                    }
                                } elseif (strpos($wc_field, 'meta:') === 0) {
                                    $meta_key = explode(':', $wc_field)[1];
                                    $product->update_meta_data($meta_key, $item[$ir_field]);
                                } else {
                                    $set_method = 'set_' . $wc_field;
                                    if (method_exists($product, $set_method)) {
                                       $product->$set_method($item[$ir_field]);
                                  } else {
                                       $this->log->add_message("Method does not exist for $wc_field", "warning");
                                   }
                               }
                           break;
                    }
                }
             }
             if($product->get_stock_status() == 'instock') {
                 $product->set_status('publish');
             } else {
                 $product->set_status('draft');
            }
           $product->save();
          $existing_product_skus[] = $item['CatalogItemId'];
             $args = array(
                   'post_type' => 'product',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'meta_query' => array(
                       array(
                            'key' => 'tax:brand', // Assuming Manufacturer is stored as a taxonomy
                            'value' => $manufacturer,
                             'compare' => '='
                       )
                   )
             );
             $query = new WP_Query($args);
             if($query->have_posts()) {
                foreach ($query->posts as $product_id) {
                    $sku = get_post_meta($product_id, '_sku', true);
                     if (!in_array($sku, $existing_product_skus)) {
                         wp_delete_post($product_id, true);
                           $products_removed++;
                            $this->log->add_message("Removed product with SKU: " . $sku, 'info');
                        }
                  }
            }
             wp_reset_postdata();
     }
 }