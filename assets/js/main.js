```javascript
    jQuery(document).ready(function($) {
         $(document).on("click", "input[name='update_woocommerce']", function (event) {
            event.preventDefault();
            $(this).prop('disabled', true);
            var data = $(this).closest("form").serialize();
              $.ajax({
                  url: ir_ajax_params.ajax_url,
                type: "POST",
                  data: data + '&action=ir_add_to_queue&_ajax_nonce='+ ir_ajax_params.ajax_nonce,
                  success: function(response) {
                     if (response.success) {
                        performImport();
                        } else {
                            console.log(response);
                            alert("Error Adding to the queue. See logs for more details.");
                        }
                     $("input[name='update_woocommerce']").prop('disabled', false);
                 },
                 error: function(jqXHR, textStatus, errorThrown) {
                     alert("Error Adding to the queue. See logs for more details." + textStatus + " - " + errorThrown);
                 }
               });
         });
        function performImport( ) {
            $.ajax({
               url: ir_ajax_params.ajax_url,
                type: "POST",
                 data: {
                        action: 'ir_process_queue_item',
                          _ajax_nonce: ir_ajax_params.ajax_nonce
                    },
                    success: function(response) {
                      if (response.success) {
                           performImport();
                         } else {
                           console.log(response);
                         }
                      },
                    error: function(jqXHR, textStatus, errorThrown) {
                         console.log("Error while processing the queue. See logs for more details." + textStatus + " - " + errorThrown);
                        }
            });
        }
    });
```