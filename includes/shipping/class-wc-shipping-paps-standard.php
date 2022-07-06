<?php

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Adds Paps Shipping functionality
 *
 * Class WC_Shipping_Paps
 */
class WC_Shipping_Paps extends WC_Shipping_Method
{
  /**
   * WC_Shipping_Paps constructor.
   */
  public function __construct($instance_id = 0)
  {
    $this->id = 'paps';
    $this->instance_id = absint($instance_id);

    $method_title = __('Paps Shipping', 'paps-wc');

    $this->method_title = $method_title;
    $this->method_description = __('Paps Shipping Support ', 'paps-wc');
    $this->init();

    // $this->supports = array('shipping-zones');
    $this->supports = array(
      'shipping-zones',
      'settings',
      'instance-settings',
      'instance-settings-modal'
    );
  }

  /**
   * Initialize Plugin settings
   */
  private function init()
  {
    // Load the settings.
    $this->init_form_fields();
    $this->init_settings();

    $this->title = __('Paps Shipping', 'paps-wc');

    $this->api_key = $this->get_option('api_key');
    $this->signature_secret_key = $this->get_option('signature_secret_key');

    $this->pickup_business_name = $this->get_option('pickup_business_name');
    $this->pickup_name = $this->get_option('pickup_name');
    $this->pickup_address = $this->get_option('pickup_address');
    $this->pickup_phone_number = $this->get_option('pickup_phone_number');
    $this->flat_rate = $this->get_option('flat_rate');
    $this->added_flat_rate = $this->get_option("added_flat_rate");
    $this->pickup_notes = $this->get_option('pickup_notes');
    $this->is_packs_enabled = $this->get_option('is_packs_enabled');

    $this->delivery_submission = $this->get_option('delivery_submission');
    $this->delivery_cancellation = $this->get_option('delivery_cancellation');

    $this->enabled = $this->get_option('enabled');
    $this->debug = $this->get_option('debug');
    $this->test = $this->get_option('test');

    $this->logging_enabled = $this->get_option('logging_enabled');

    add_action('woocommerce_update_options_shipping_' . $this->id, [
      $this,
      'process_admin_options'
    ]);
  }

  /**
   * Form Fields
   */
  public function init_form_fields()
  {
    $this->form_fields = include 'data-paps-settings-standard.php';
  }

  /**
   * Main function to calculate shipping based on Paps or Flat price
   *
   * @param array $package
   */

  public function callAPI($method, $url, $data){
    $curl = curl_init();
    switch ($method){
       case "POST":
          curl_setopt($curl, CURLOPT_POST, 1);
          if ($data)
             curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
          break;
       case "PUT":
          curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
          if ($data)
             curl_setopt($curl, CURLOPT_POSTFIELDS, $data);               
          break;
       default:
          if ($data)
             $url = sprintf("%s?%s", $url, http_build_query($data));
    }
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
       'Content-Type: application/json',
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $result = curl_exec($curl);
    if(!$result){die("Connection Failure");}
    curl_close($curl);
    return $result;
 }
  public function calculate_shipping($package = [])
  {
    if (
      isset($this->flat_rate) &&
      is_numeric($this->flat_rate)
    ) {
      $rate = array(
        'id' => $this->id,
        'label' => $this->title,
        'cost' => $this->flat_rate,
        'calc_tax' => 'box_packing'
      );

      $this->add_rate($rate);
    } elseif (
      isset($this->is_packs_enabled) &&
      $this->is_packs_enabled == "yes"
    ) {
      $cost = 0;
      if (
        isset($this->added_flat_rate) &&
        !empty($this->added_flat_rate) &&
        is_numeric($this->added_flat_rate)
      ) {
        $cost = $cost + (int) $this->added_flat_rate;
      }

      $rate = array(
        'id' => $this->id,
        'label' => $this->title,
        'cost' => $cost,
        'calc_tax' => 'box_packing'
      );

      $this->add_rate($rate);
    } else {
      $weight = 0;
      $cost = 0;
      $make_call = null;
      $pickup_adress = $this->get_option('pickup_address');
      $dropoff_address = null;

      if (
        !$package['destination']['city'] ||
        !$package['destination']['country']
      ) {
        return wc_paps()->debug(
          "Il n'y a aucune adresse saisie lors de la commande, veuillez renseigner ce champs afin qu'on puisse calculer le tarif de la livraison de votre colis",
          true
        );
      } else {
        $dropoff_address =
          ($package['destination']['address'] ? $package['destination']['address'] . ',' : '') .
          $package['destination']['city'] . ',' .
          ($package['destination']['state'] ? $package['destination']['state'] . ',' : '') . 
          WC()->countries->countries[$package['destination']['country']];
      }

      foreach ($package['contents'] as $item_id => $values) {
        $_product = $values['data'];

        $quantity = $values['quantity'] ? $values['quantity'] : 1;
        $item_weight = $_product->get_weight() ? $_product->get_weight() : 1;
        $weight += $item_weight * $quantity;
      }

      $quoteRequestParams = array(
        'origin' => $pickup_adress,
        'destination' => $dropoff_address,
        'weight' => $weight
      ); 
      $make_call = $this->callAPI('POST', 'https://api.papslogistics.com/marketplace', json_encode($quoteRequestParams));
      $response = json_decode($make_call, true);
	   
        if (is_wp_error($make_call)) {
      error_log(print_r($make_call, true));
      return;
        }

      $cost = $response['data']['price'];
      
      if (get_option('woocommerce_currency') === "EUR") {
        $cost = $cost / 1;
        $cost = number_format((float) $cost, 2, '.', '');
      }

      if (
        isset($this->added_flat_rate) &&
        !empty($this->added_flat_rate) &&
        is_numeric($this->added_flat_rate)
      ) {
        $cost = $cost + $this->added_flat_rate;
      }

      if (!is_wp_error($make_call)) {
        $rate = array(
          'id' => $this->id,
          'label' => $this->title,
          'cost' => $cost,
          // 'cost' => number_format($quote['fee'] / 100, 2, '.', ' '),
          'calc_tax' => 'box_packing'
        );

        $this->add_rate($rate);
      }
    }
  }

  /**
   * Check if settings are not empty
   */
  public function admin_options()
  {
    // Check users environment supports this method
    $this->environment_check();

    // Show settings
    parent::admin_options();
  }

  /**
   * Show error in case of config missing
   */
  private function environment_check()
  {
    if (
      (!$this->api_key ||
        !$this->pickup_name ||
        !$this->pickup_address ||
        !$this->pickup_phone_number) &&
      $this->enabled == 'yes'
    ) {
      echo '<div class="error">
				<p>' .
        __(
          'Paps a été activé mais un des ces champs obligatoires (code client, clé de sécurité, nom pour le ramassage, adresse du ramassage ou numéro de téléphone du téléphone du ramassage) n\'a pas été renseigné.',
          'paps-wc'
        ) .
        '</p>
			</div>';
    }
  }
}
