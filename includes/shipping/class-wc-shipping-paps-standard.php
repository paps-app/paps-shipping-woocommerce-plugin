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

    $method_title = __('Paps (Standard)', 'paps-wc');

    $this->method_title = $method_title;
    $this->method_description = __('Paps Shipping Support', 'paps-wc');
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

    $this->title = __('Livraison Standard (Paps)', 'paps-wc');

    $this->api_key = $this->get_option('api_key');
    $this->signature_secret_key = $this->get_option('signature_secret_key');

    $this->pickup_business_name = $this->get_option('pickup_business_name');
    $this->pickup_name = $this->get_option('pickup_name');
    $this->pickup_address = $this->get_option('pickup_address');
    $this->pickup_phone_number = $this->get_option('pickup_phone_number');
    $this->flat_rate = $this->get_option('flat_rate');
    $this->pickup_notes = $this->get_option('pickup_notes');

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
  public function calculate_shipping($package = [])
  {
    if (
      isset($this->flat_rate) &&
      !empty($this->flat_rate) &&
      is_numeric($this->flat_rate)
    ) {
      $rate = array(
        'id' => $this->id,
        'label' => $this->title,
        'cost' => $this->flat_rate,
        'calc_tax' => 'box_packing'
      );

      $this->add_rate($rate);
    } else {
      $weight = 0;
      $cost = 0;
      $quote = null;
      $pickup_adress = $this->get_option('pickup_address');
      $dropoff_address = null;

      foreach ($package['contents'] as $item_id => $values) {
        $_product = $values['data'];
        $weight = $_product->get_weight() * $values['quantity'];
        // $product_id = $_product->get_id();
        // $weight = $weight + $_product->get_weight() * $values['quantity'];

        if (
          !$package['destination']['city'] ||
          !$package['destination']['state']
        ) {
          return wc_paps()->debug(
            "Il n'y a aucune adresse saisie lors de la commande, veuillez renseigner ce champs afin qu'on puisse calculer le tarif de la livraison de votre colis",
            true
          );
        }
        //  elseif (empty($package['destination']['address'])) {
        //   # code...
        // }
        else {
          $package_size = $this->get_package_size($weight);
          $dropoff_address =
            $package['destination']['address'] .
            ',' .
            $package['destination']['city'] .
            ',' .
            $package['destination']['state'];

          if (
            !contains("Sénégal", $dropoff_address) &&
            $package['destination']['country'] == "SN"
          ) {
            $dropoff_address = $dropoff_address . ", Senegal";
          }
        }
      }

      $quoteRequestParams = array(
        'origin' => $pickup_adress,
        'destination' => $dropoff_address,
        'packageSize' => $package_size
      );

      if ($this->is_express == "yes") {
        $quoteRequestParams['deliveryType'] = "express";
      }

      $quote = wc_paps()
        ->api()
        ->getQuote($quoteRequestParams);

      $cost = $quote['data']['quote'];

      if (!is_wp_error($quote)) {
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

  public function get_package_size($weight)
  {
    $package_size = null;
    if ($weight > 5 && $weight < 30) {
      $package_size = "medium";
    } elseif ($weight > 30 && $weight < 60) {
      $package_size = "large";
    } elseif ($weight > 60 && $weight < 100) {
      $package_size = "xLarge";
    } else {
      $package_size = "small";
    }
    return $package_size;
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

    // if (!$this->signature_secret_key && $this->enabled == 'yes') {
    //     echo '<div class="error">
    // 		<p>' . __('Paps a été activé, mais la singature n\'a pas été renseigné, Les webhooks ne fonctionneront pas si ce champs n\'est pas renseigné', 'wf-shipping-dhl') . '</p>
    // 	</div>';
    // }
  }
}
