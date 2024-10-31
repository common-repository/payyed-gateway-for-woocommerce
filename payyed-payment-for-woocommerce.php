<?php

/**
 * Plugin Name: Payyed Gateway for WooCommerce
 * Plugin URI: https://payyed.org/
 * Author Name: Justus Ochieng
 * Author URI: https://opskill.com/
 * Description: Accept payments from MPESA, Credit Cards, Debit Cards via Payyed.org, we host all payment gateways making it flexible to shift between any at will. 
 * Version: 1.3.5
 * License: GPL-2.0-or-later
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: payyed-payment-for-woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (
    !in_array(
        "woocommerce/woocommerce.php",
        apply_filters("active_plugins", get_option("active_plugins"))
    )
) {
    return;
}

add_action("plugins_loaded", "payyed_payment_init", 11);
 
 
add_filter('woocommerce_checkout_fields', 'custom_override_checkout_fields');

function custom_override_checkout_fields($fields) {
    
 
                $id = "payyed_payment";
             $configs = get_option( "woocommerce_" . $id . "_settings" );

 
 
                if(isset($configs["billing_postcode"]) AND $configs["billing_postcode"] == 'no'){
                 unset($fields['billing']['billing_country']);
                 unset($fields['billing']['shipping_country']);

                }

 
                if(isset($configs["billing_postcode"]) AND $configs["billing_postcode"] == 'no'){
                  unset($fields['billing']['billing_postcode']);
                  unset($fields['billing']['shipping_postcode']);

                }


                if(isset($configs["billing_company"]) AND $configs["billing_company"] == 'no'){
                 
                    unset($fields['billing']['billing_company']);
                    unset($fields['billing']['shipping_company']);
                }


                if(isset($configs["billing_address_1"]) AND $configs["billing_address_1"] == 'no'){
                 
                   unset($fields['billing']['billing_address_1']);
                   unset($fields['billing']['shipping_address_1']);

                }

                if(isset($configs["billing_address_2"]) AND $configs["billing_address_2"] == 'no'){
                     unset($fields['billing']['billing_address_2']);
                     unset($fields['billing']['shipping_address_2']);

                }


                if(isset($configs["shipping_city"]) AND $configs["shipping_city"] == 'no'){
              unset($fields['billing']['shipping_city']);
              unset($fields['billing']['billing_city']);

                }


                if(isset($configs["shipping_state"]) AND $configs["shipping_state"] == 'no'){
                      unset($fields['billing']['shipping_state']);
                      unset($fields['billing']['billing_state']);

                }
 
    return $fields;

}
  


function payyed_payment_init()
{
 
    if (class_exists("WC_Payment_Gateway")) {
        
  
        class Payyed_Payment_Gateway extends WC_Payment_Gateway
        {

            public static $myArray;
 
            public function __construct()
            {
 

 
                $this->id = "payyed_payment";
                $this->icon = apply_filters(
                    "payyed-gateway-for-woocommerce",
                    plugins_url("assets/icon.png", __FILE__)
                );
                $this->has_fields = false;
                $this->method_title = __("Payyed gateway", "payyed-gateway-for-woocommerce");
                $this->method_description = __(
                    "Payyed local content payment systems.",
                    "payyed-gateway-for-woocommerce"
                );

                $this->title = $this->get_option("title");
                $this->description = $this->get_option("description");
                $this->instructions = $this->get_option("instructions",  $this->description
                );

                $this->init_form_fields();
                $this->init_settings();
                
 
                add_action("woocommerce_update_options_payment_gateways_" . $this->id, [$this, "process_admin_options"]);
                 
                add_action('woocommerce_api_wc_' . $this->id, [$this, 'check_response']);
             
             

            }
            

            public function init_form_fields()
            {
                $this->form_fields = apply_filters("payyed-gateway-for-woocommerce", [
                    "enabled" => [
                        "title" => __("Enable/Disable", "payyed-gateway-for-woocommerce"),
                        "type" => "checkbox",
                        "label" => __(
                            "Enable or Disable Payyed Payments",
                            "payyed-payment-for-woocommerce"
                        ),
                        "default" => "no",
                    ],

                    "description" => [
                        "title" => __(
                            "Payments Gateway Description",
                            "payyed-gateway-for-woocommerce"
                        ),
                        "type" => "textarea",
                        "default" => __(
                            "Accept MPESA, Intasend, PayPal, Authorize.net, PayStack plus 10+ more",
                            "payyed-gateway-for-woocommerce"
                        ),
                        "desc_tip" => true,
                        "description" => __(
                            "Add a new title for the Payyed Payments Gateway that customers will see when they are on the checkout page.",
                            "payyed-gateway-for-woocommerce"
                        ),
                    ],

                    "publishable_key" => [
                        "title" => "Payyed Key",
                        "type" => "text",
                        "default" => "",
                    ],

                    "private_key" => [
                        "title" => "Payyed Secret",
                        "type" => "text",
                        "default" => "",
                    ],

                  
                    "billing_country" => [ 
                        "title" => "Display Country?",
                        "type" => "checkbox",
                        "default" => "yes",

                    ],

                    "billing_postcode" => [ 
                        "title" => "Display Postalcode?",
                        "type" => "checkbox",
                        "default" => "yes",

                    ],

                    "billing_company" => [ 
                        "title" => "Display Company Name?",
                        "type" => "checkbox",
                        "default" => "yes",

                    ],


                    "billing_address_1" => [ 
                        "title" => "Display Billing Address 1?",
                        "type" => "checkbox",
                        "default" => "yes",

                    ],

                    "billing_address_2" => [ 
                        "title" => "Display Billing Address 2?",
                        "type" => "checkbox",
                        "default" => "yes",

                    ],    

                    "shipping_city" => [ 
                        "title" => "Display City?",
                        "type" => "checkbox",
                        "default" => "yes",

                    ],
                                    
                    "shipping_state" => [ 
                        "title" => "Display State / County ?",
                        "type" => "checkbox",
                        "default" => "yes",

                    ],
                ]);
            }
         
            public function process_payment($order_id)
            {
              
                $order = wc_get_order($order_id);

                $items_data = [];

                // Check if the order object exists
                if ($order) {
                    // Retrieve items data
                    $items = $order->get_items();

                    // Loop through each item
                    foreach ($items as $item_id => $item) {
                        // Get product data for the item
                        $product = $item->get_product();

                        // Build item data
                        $item_data = [
                            "name" => $product->get_name(), // Product name
                            "quantity" => $item->get_quantity(), // Quantity
                            "subtotal" => $item->get_subtotal(), // Subtotal
                            "total" => $item->get_total(), // Total (including taxes and discounts)
                        ];

                        // Add item data to the items array
                        $items_data[] = $item_data;

                    }

                }

                // Convert items data to JSON format
                $items_json = wp_json_encode($items_data);

                // Get the payment gateway settings
                $payment_gateway_settings = get_option(
                    "woocommerce_" . $this->id . "_settings"
                );

                // Check if settings exist and if the specific keys exist
                if (
                    $payment_gateway_settings &&
                    isset($payment_gateway_settings["publishable_key"]) &&
                    isset($payment_gateway_settings["private_key"])
                ) {
                    // Retrieve the values of the keys and secrets
                    $publishable_key =
                        $payment_gateway_settings["publishable_key"];
                    $private_key = $payment_gateway_settings["private_key"];

                    // Use the retrieved keys and secrets as needed
                } else {
                    // Keys and secrets not found or settings not available

                    $publishable_key = "";
                    $private_key = "";
                }

                $nonce = wp_create_nonce('payyed-payment');   

                $payment_data = [
                    "order_id" => $order_id,
                    "amount" => $order->get_total(),
                    "payyed_nonce" => esc_attr( $nonce ),
                    "currency" => get_woocommerce_currency(),
                    "payyed_key" => $publishable_key,
                    "payyed_secret" => $private_key,
                    "email" => $order->get_billing_email(),
                    "phone" => $order->get_billing_phone(),
                    "customer" =>  $order->get_billing_first_name() .  " " . $order->get_billing_last_name(),
                    "country" => $order->get_billing_country(),
                    "items" => $items_json,
                    "website" => home_url() . "/",
                    "ipn_url" => home_url() . "/wc-api/wc_".$this->id,
                    "success_url" => wc_get_account_endpoint_url('view-order') . $order_id,
                    "checkout_url" => wc_get_checkout_url(),
            
                    // Add other required data for Payyed API
                ];

 
                      // Prepare the request arguments
 
                    $args = [
                        'body' => json_encode($payment_data),
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                    ];

                        print_r($args);
                // URL to which the POST request will be sent
                $url = "https://payyed.org/wooc/";

                // Send the POST request
                $response = wp_remote_post($url, $args);
                
                WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order_id );
               // $this->email_processing( $order_id );

                // Check if the request was successful
                if (
                    !is_wp_error($response) &&
                    wp_remote_retrieve_response_code($response) === 200
                ) {
                    // Process the response if needed
                    $body = wp_remote_retrieve_body($response);
                    // Return the result
                    return [
                        "result" => "success",
                        "redirect" =>
                            "https://payyed.org/woo/order/" .
                            $order_id .
                            "?key=" .
                            $publishable_key, // No redirection needed
                    ];
                } else {
                    // If the request failed, handle the error
                    $error_message = is_wp_error($response)
                        ? $response->get_error_message()
                        : "Unknown error";
                    // You can log the error message or display it to the user
                    wc_add_notice(
                        "Payment processing failed: " . $error_message,
                        "error"
                    );
                }
            }
            
  
public function check_response() {
     
 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
 
    
                    // Get the payment gateway settings
            $configs = get_option(
                    "woocommerce_" . $this->id . "_settings"
                );
    $publishable_key = $configs["publishable_key"];
    
   
    // Sanitize the data
    $order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '';
    $payyed_key = isset($_POST['payyed_key']) ? sanitize_text_field($_POST['payyed_key']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
 
    // Check if the necessary data is present
    if (!empty($order_id) && $status === 'paid' && $publishable_key == $payyed_key) {
        $order = wc_get_order($order_id);
        if ($order) {
            // Update the order status to processing
            $order->payment_complete();
            error_log('Order updated to processing');
            echo 'Order updated to processing';
              $order->add_order_note(
                        __("Payment done via Payyed", "payyed-gateway-for-woocommerce")
                    );

             WC()->cart->empty_cart();
        } else {
            error_log('Order not found');
            echo 'Order not found';
        }
    } else {
        error_log('Invalid data received');
        echo 'Invalid data received';
    }

    // Always exit to prevent further execution
    exit;
}

            

            public function thank_you_page()
            {
                // silent as it goes to orders page
            }
        }
        
        
    }
    
    if (isset($_POST['order_id']) AND isset($_POST['payyed_key'])) {
      //  Payyed_Payment_Gateway::ipn_handler();
    }
}

add_filter("woocommerce_payment_gateways", "ppfw_payyed_payment_gateway");

function ppfw_payyed_payment_gateway($gateways)
{
    $gateways[] = "Payyed_Payment_Gateway";
    return $gateways;
}
