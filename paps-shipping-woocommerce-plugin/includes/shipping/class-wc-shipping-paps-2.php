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

    // $method_title =
    //   $this->get_option('is_express') == "yes"
    //     ? __('Paps (Express)', 'paps-wc')
    //     : __('Paps (Standard)', 'paps-wc');

    // $this->method_title = $this->get_option('is_packs_enabled')
    //   ? "Livraison Standard Paps (Packs)"
    //   : "Livraison Standard Paps";
    $this->method_title = __('Paps (Standard)', 'paps-wc');

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

    $method_title =
      $this->get_option('is_express') == "yes"
        ? __('Paps (Express)', 'paps-wc')
        : __('Paps (Standard)', 'paps-wc');

    $title = $this->get_option('is_packs_enabled')
      ? "Livraison Standard (Paps)"
      : "Livraison Standard (Paps)";

    $this->title = __('Livraison Standard (Paps)', 'paps-wc');
    $this->api_key = $this->get_option('api_key');
    // $this->signature_secret_key = $this->get_option('signature_secret_key');

    $this->pickup_business_name = $this->get_option('pickup_business_name');
    $this->pickup_name = $this->get_option('pickup_name');
    $this->pickup_address = $this->get_option('pickup_address');
    $this->pickup_phone_number = $this->get_option('pickup_phone_number');
    $this->flat_rate = $this->get_option('flat_rate');
    $this->is_packs_enabled = $this->get_option('is_packs_enabled');
    $this->pickup_notes = $this->get_option('pickup_notes');

    $this->delivery_submission = $this->get_option('delivery_submission');
    $this->delivery_cancellation = $this->get_option('delivery_cancellation');

    $this->enabled = $this->get_option('enabled');
    $this->debug = $this->get_option('debug');
    $this->test = $this->get_option('test');

    $this->logging_enabled = $this->get_option('logging_enabled');

    $this->is_express = $this->get_option('is_express');

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
    $this->form_fields = include 'data-paps-settings.php';
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
    } elseif (isset($this->is_packs_enabled) && $this->is_packs_enabled) {
      $rate = array(
        'id' => $this->id,
        'label' => $this->title,
        'cost' => 1250,
        'calc_tax' => 'box_packing'
      );

      wc_paps()->debug('Packs enabled: ' . print_r($this->is_packs_enabled));

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
          empty($package['destination']['address']) ||
          empty($package['destination']['city'])
        ) {
          return wc_paps()->debug(
            "Il n'y a aucune adresse saisie lors de la commande, veuillez renseigner ce champs afin qu'on  puisse tarifer la livraison de votre colis",
            true
          );
        } else {
          $package_size = $this->get_package_size($weight);
          $dropoff_address =
            $package['destination']['address'] .
            ',' .
            $package['destination']['city'] .
            ',' .
            $package['destination']['country'];
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

      wc_paps()->debug(
        'Requested a quote and here is the response: ' .
          print_r($quoteRequestParams . $quote)
      );

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

class WC_Shipping_Paps_Express extends WC_Shipping_Paps
{
  public function __construct($instance_id = 2)
  {
    $this->id = 'paps_express';
    $this->instance_id = absint($instance_id);

    // $this->method_title = $this->get_option('is_packs_enabled')
    //   ? "Livraison Express Paps (Packs)"
    //   : "Livraison Express";
    $this->method_title = __('Paps (Express)', 'paps-wc');

    $this->method_description = __('Paps Shipping Support', 'paps-wc');

    self::init();

    $this->supports = array(
      'shipping-zones',
      'settings',
      'instance-settings',
      'instance-settings-modal'
    );
  }

  private function init()
  {
    $this->init_form_fields();
    $this->init_settings();

    $this->title = __('Livraison Express (Paps)', 'paps-wc');

    $this->api_key = $this::get_option('api_key');
    // $this->signature_secret_key = $this->get_option('signature_secret_key');

    $this->pickup_business_name = $this->get_option('pickup_business_name');
    $this->pickup_name = $this->get_option('pickup_name');
    $this->pickup_address = $this->get_option('pickup_address');
    $this->pickup_phone_number = $this->get_option('pickup_phone_number');
    $this->flat_rate = $this->get_option('flat_rate');
    $this->is_packs_enabled = $this->get_option('is_packs_enabled');
    $this->pickup_notes = $this->get_option('pickup_notes');

    $this->delivery_submission = $this->get_option('delivery_submission');
    $this->delivery_cancellation = $this->get_option('delivery_cancellation');

    $this->enabled = $this->get_option('enabled');
    $this->debug = $this->get_option('debug');
    $this->test = $this->get_option('test');

    $this->logging_enabled = $this->get_option('logging_enabled');

    $this->is_express = $this->get_option('is_express');

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
    // $this->form_fields = include 'data-paps-settings.php';

    $this->instance_form_fields = array(
      'enabled' => array(
        'title' => __('Expédition avec Paps', 'paps-wc'),
        'type' => 'checkbox',
        'label' => __('Activé', 'paps-wc'),
        'default' => false
      ),
      'test' => array(
        'title' => __('Mode Test', 'paps-wc'),
        'label' => __('Activer le mode Test', 'paps-wc'),
        'type' => 'checkbox',
        'default' => 'no',
        'desc_tip' => true,
        'description' => __(
          'Activer le mode test pour voir si l\'envoi de courses à Paps se passe sans problème. Notez que la course sera créée mais la prise en charge ne sera pas effectuée',
          'paps-wc'
        )
      ),
      'api_key' => array(
        'title' => __('Clé API', 'paps-wc'),
        'type' => 'text',
        'description' => __(
          'Le clé API vous a été envoyée dans l\'email de confirmation',
          'paps-wc'
        ),
        'default' => ''
      ),
      'pickup_business_name' => array(
        'title' => __('Nom de l\'entreprise', 'paps-wc'),
        'type' => 'text',
        'description' => __(
          'Le nom de votre entreprise, où effectuer les ramassages',
          'paps-wc'
        ),
        'default' => ''
      ),
      'pickup_name' => array(
        'title' => __('Chargé des expéditions', 'paps-wc'),
        'type' => 'text',
        'description' => __('Nom de la personne en charge des expéditions', 'paps-wc'),
        'default' => ''
      ),
      'pickup_address' => array(
        'title' => __('Adresse de Ramassage ou Pickup', 'paps-wc'),
        'type' => 'text',
        'description' => __(
          'Adresse de votre entreprise où on effectuera les ramassages des colis à livrer.',
          'paps-wc'
        ),
        'default' => ''
      ),
      'pickup_phone_number' => array(
        'title' => __('Numéro de téléphone du ramassage', 'paps-wc'),
        'type' => 'text',
        'description' => __(
          'Peut être Le numéro de téléphone de votre entreprise',
          'paps-wc'
        ),
        'default' => ''
      ),
      'is_packs_enabled' => array(
        'title' => __('Courses avec Packs achetés', 'paps-wc'),
        'type' => 'checkbox',
        'label' => __('Activé', 'paps-wc'),
        'description' => __(
          'Lorsque activée, cette option permet aux client de pouvoir choisir lui-même le mode de livraison Express ou Programmé (Standard) avec une tarification fixe. Note: vous devez forcément acheter un pack auprès du service commercial.',
          'paps-wc'
        ),
        'default' => 'no'
      ),
      'flat_rate' => array(
        'title' => __('Montant forfait pour toutes les courses', 'paps-wc'),
        'type' => 'number',
        'description' => __(
          'Montant fixe des frais de livraison sur toute la plateforme. Important: en choisissant ce mode vous supportez vous-même tous les frais de livraison s\'ajoutant au tarif normal de la course.',
          'paps-wc'
        ),
        'default' => ''
      ),
      'pickup_notes' => array(
        'title' => __('Notes sur le ramassage', 'paps-wc'),
        'type' => 'text',
        'description' => __(
          'Notes par défaut à fournir pour le coursier qui effectue le ramassage',
          'paps-wc'
        ),
        'default' => ''
      ),
      'delivery_submission' => array(
        'title' => __(
          'Envoyer la requête à Paps quand la commande à l\'état suivant:',
          'paps-wc'
        ),
        'type' => 'select',
        'description' => __(
          'Quand la commande est mise dans cet état, la requête est envoyée immédiatement à Paps',
          'paps-wc'
        ),
        'default' => '',
        'options' => array(
          'pending' => _x('Payement en attente', 'paps-wc'),
          'processing' => _x('En cours', 'paps-wc'),
          'on-hold' => _x('En pause', 'paps-wc'),
          'completed' => _x('Terminé', 'paps-wc')
        ),
        'desc_tip' => true
      ),
      'delivery_cancellation' => array(
        'title' => __(
          'Annuler la requête à Paps quand la commande est à l\'état suivant:',
          'paps-wc'
        ),
        'type' => 'select',
        'description' => __(
          'Quand la commande est mise dans cet état, la requête est annulée immédiatement à Paps',
          'paps-wc'
        ),
        'default' => '',
        'options' => array(
          'cancelled' => _x('Annulé', 'paps-wc'),
          'failed' => _x('Echec', 'paps-wc')
        ),
        'desc_tip' => true
      ),
      'notify_admin_on_failure' => array(
        'title' => __('Envoyer un email à l\'admin lorqu\'il y a un erreur', 'paps-wc'),
        'label' => __('Activé', 'paps-wc'),
        'type' => 'checkbox',
        'default' => 'no',
        'description' => __(
          'Envoyer un email à l\'admin du site lorqu\'il y a un erreur de traitement',
          'paps-wc'
        )
      ),
      'logging_enabled' => array(
        'title' => __('Activer le logging', 'paps-wc'),
        'type' => 'checkbox',
        'default' => 'no',
        'desc_tip' => true,
        'description' => __(
          'Activer le logging pour loger les actions de de lintégration de Paps dans le dossier wc-logs',
          'paps-wc'
        )
      )
    );
  }

  public function calculate_shipping($package = [])
  {
    // wc_paps()->debug('Packs enabled express: ' . print_r($this->is_packs_enabled));

    if (isset($this->is_packs_enabled) && $this->is_packs_enabled) {
      $rate = array(
        'id' => $this->id,
        'label' => $this->title,
        'cost' => 2500,
        'calc_tax' => 'box_packing'
      );

      $this->add_rate($rate);
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
  public function environment_check()
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
