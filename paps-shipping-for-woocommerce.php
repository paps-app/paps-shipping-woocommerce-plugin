<?php
/*
	Plugin Name: Paps Shipping for WooCommerce
	Description: Paps Shipping & Delivery Tracking Integration for WooCommerce
	Version: 3.0.0
	Author: Paps
	Author URI: www.paps.sn
*/

class WC_Paps
{
  /**
   * Class Instance
   *
   * @var null
   */
  private static $instance = null;

  /**
   * Plugin Settings
   *
   * @var
   */
  protected $settings;

  /**
   * Paps API Instance
   *
   * @var null
   */
  private $api = null;

  /**
   * WC_Logger instance
   *
   * @var null
   */
  private $logger = null;

  /**
   * WC_Paps constructor.
   */
  private function __construct()
  {
    $this->init();
    $this->hooks();
  }

  /**
   * Init function
   */
  public function init()
  {
    $this->settings = get_option('woocommerce_paps_settings');
  }

  /**
   * Hooks
   */
  private function hooks()
  {
    add_action('woocommerce_shipping_init', [
      $this,
      'paps_woocommerce_shipping_init'
    ]);

    add_filter('woocommerce_shipping_methods', [
      $this,
      'paps_woocommerce_shipping_methods_express'
    ]);
    add_filter('woocommerce_shipping_methods', [
      $this,
      'paps_woocommerce_shipping_methods_standard'
    ]);

    add_filter(
      'woocommerce_shipping_calculator_enable_postcode',
      '__return_false'
    );

    add_action('woocommerce_thankyou', [$this, 'handle_order_status_change']);
    add_action('woocommerce_order_status_changed', [
      $this,
      'handle_order_status_change'
    ]);

    add_filter('manage_edit-shop_order_columns', [
      $this,
      'add_paps_delivery_column'
    ]);
    add_action(
      'manage_shop_order_posts_custom_column',
      array($this, 'delivery_status_on_backend'),
      10,
      2
    );

    add_action(
      'woocommerce_order_details_after_order_table',
      array($this, 'show_delivery_details_on_order'),
      20
    );

    // add_action(
    //   'woocommerce_checkout_update_order_review',
    //   array($this, 'action_woocommerce_checkout_update_order_review'),
    //   10,
    //   2
    // );
  }

  /**
   * Get singleton instance
   */
  public static function get()
  {
    if (self::$instance == null) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  /**
   * WC_Shipping_Paps
   */
  public function paps_woocommerce_shipping_init()
  {
    require_once 'includes/shipping/class-wc-shipping-paps-standard.php';
    require_once 'includes/shipping/class-wc-shipping-paps-express.php';
  }

  public function action_woocommerce_checkout_update_order_review($array, $int)
  {
    WC()->cart->calculate_shipping();
    return;
  }

  /**
   * Add Paps as a Shippin method
   *
   * @param $methods
   * @return array
   */
  public function paps_woocommerce_shipping_methods_standard($methods)
  {
    $methods['paps'] = 'WC_Shipping_Paps';
    return $methods;
  }

  public function paps_woocommerce_shipping_methods_express($methods)
  {
    $methods['paps_relais'] = 'WC_Shipping_Paps_Relais';
    return $methods;
  }

  /**
   * Order Status Handle to created or delete Paps delivery
   *
   * @param $order_id
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

 public function callTASK($data){
  $curl = curl_init();
  $acces_token = $this->settings['api_key'];
  curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.papslogistics.com/tasks',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => json_encode($data),
  CURLOPT_HTTPHEADER => array(
      'Authorization: Bearer '.$acces_token,
      'Content-Type: application/json'
  ),
  ));


  $response = curl_exec($curl);

  curl_close($curl);
  return $response;
}
public function callTaskProd($data){
  $curl = curl_init();
  $acces_token = $this->settings['api_key'];
  curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://paps-api.papslogistics.com/tasks',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => json_encode($data),
  CURLOPT_HTTPHEADER => array(
      'Authorization: Bearer '.$acces_token,
      'Content-Type: application/json'
  ),
  ));


  $response = curl_exec($curl);

  curl_close($curl);
  return $response;
}
  
  public function handle_order_status_change($order_id)
  {
    $order = new WC_Order($order_id);
    // $product = new WC_Product();

    $items = $order->get_items();

    if ($order->status == $this->settings['delivery_submission']) {
      $task_status = get_post_meta($order_id, 'paps_task_status', true);
      $pickup_id = get_post_meta($order_id, 'paps_pickup_id', true);
      $delivery_id = get_post_meta($order_id, 'paps_delivery_id', true);

      if (!$task_status) {
        $dropoff_address = $order->shipping_address_1;
        $dropoff_address2 = $order->shipping_address_2;
        $datetime = new DateTime();
        $datetime->modify("+30 minutes");
        $product_object = $order->item->get_product();
        $product_name = $product_object->get_name();
        $product_price = $product_object->get_price();
        $product_weight = $product_object->get_weight() * $order->item["quantity"];
        $package_size = $this->get_package_size($product_weight);

        $receiverData = [
            "firstname"=> $order->shipping_first_name,
            "lastname"=> $order->shipping_last_name,
            "phoneNumber"=> $order->billing_phone,
            "email"=> $order->shipping_email,
            "entreprise"=> $order->shipping_company,
            "address"=> $dropoff_address,
            "specificationAddress"=> $dropoff_address2
        ];

        $parcelsData = [
            "packageSize"=> "S",
            "description"=> $order->get_product_from_item .' venant du site de' .
                ' ' .
               $this->settings['pickup_business_name'] .
                ' -- Tarif déterminé de la course ' .
                ' Le mode de payement choisi est ' .
                $order->payment_method . '.' .''. 'note du client:'. $order->get_customer_note,
            "price"=> $order->shipping_total,
            "amountCollect"=> $order->get_prices_include_tax
        ];

        $paramsPaps = [
          "type"=> "PICKUP",
          "datePickup"=> $datetime->format("Y-m-d"),
          "timePickup"=> $datetime->format("G:i:s"),
          "vehicleType"=> "SCOOTER",
          "address"=> $this->settings['pickup_address'],
          "receiver" => $receiverData,
          "parcels" => $parcelsData
        ];
       

        //   if (!empty($order->customer_note)) {
        //     $paramsPaps['parcels']['description'] =
        //       $paramsPaps['parcels']['description'] .
        //       '. Notes du client à livrer: ' .
        //       $order->customer_note;
        //   }

        if (!empty($order->shipping_city)) {
          $paramsPaps['receiver']['address'] =
            $paramsPaps['receiver']['address'] . ', ' . $order->shipping_city;
        }

        if (!empty($order->shipping_country)) {
          $paramsPaps['receiver']['address']  =
            $paramsPaps['receiver']['address']  . ', ' . $order->shipping_country;
        }

        foreach ($items as $item) {
          $product_object = $item->get_product();

          $product_name = $product_object->get_name();
          $product_price = $product_object->get_price();
          $product_weight = $product_object->get_weight() * $item["quantity"];

          $package_size = $this->get_package_size($product_weight);

          $cost = 0;
          if (
            isset($this->settings['flat_rate']) &&
            !empty($this->settings['flat_rate']) &&
            is_numeric($this->settings['flat_rate'])
          ) {
            $cost = $this->settings['flat_rate'];
          } else {
            
            $make_call = $this->callAPI('POST', 'https://api.papslogistics.com/marketplace', json_encode(array(
              'origin' => $this->settings['pickup_address'],
              'destination' => $dropoff_address,
              'weight' => $product_weight
            )));
            $response = json_decode($make_call, true);
            $cost  = $response['data']['price'];
          }

          $paramsPaps['parcels']['packageSize'] = $package_size;
          $paramsPaps['parcels']['description'] =
            'Commande venant du site de' .
            ' ' .
            $this->settings['pickup_business_name'] .
            " --- Nom du produit: " .
            $product_name .
            ' --- ' .
            $item['quantity'] .
            ' colis à livrer pour un prix de ' .
            $product_price .
            'F l\'unité' .
            ' --- Tarif déterminé de la course : ' .
            $cost .
            'F --- Le mode de payement choisi est ' .
            $order->payment_method;

          if ($order->payment_method == "cod") {
            $paramsPaps['parcels']['amountCollect'] =
              (int) $cost + (int) $product_price * (int) $item["quantity"];
          } else {
            $paramsPaps['parcels']['amountCollect'] = 0;
          }

          if ($this->settings['is_relay']) {
            $paramsPaps['parcels']['description'] =
              $paramsPaps['parcels']['description'] .
              ' --- La livraison se fera en Point Relais';
          }
          if ($this->settings['test'] == 'yes') {
            $make_call = $this->callTASK($paramsPaps);
            $apiResult = json_decode($make_call, true);
            $delivery = $apiResult['data']['job']['job_orders']['_id'];
          }
            $make_call = $this->callTaskProd($paramsPaps);
            $apiResult = json_decode($make_call, true);

          if (!is_wp_error($delivery)) {
            wp_mail($order->shipping_email,
             'La livraison est prévue pour le:',
             $apiResult['data']['job']['job_orders']['order_parcels']['parcel_delivery_date'],
             print_r($delivery, true));
            update_post_meta(
              $order_id,
              'paps_pickup_id',
              $delivery = $apiResult['data']['job']['job_orders']['_id']
            );
            update_post_meta(
              $order_id,
              'paps_delivery_id',
              $delivery = $apiResult['data']['job']['job_orders']['order_parcels']['_id']
            );
            update_post_meta(
              $order_id,
              'paps_pickup_tracking_link',
              $delivery['data']['pickup_tracking_link']
            );
            update_post_meta(
              $order_id,
              'paps_delivery_tracking_link',
              $delivery = $apiResult['data']['job']['job_orders']['order_parcels']['parcel_delivery_date']
            );
            update_post_meta(
              $order_id,
              'paps_task_status',
              'sent_but_not_started'
            );
          } else {
            wp_mail(
              get_option('admin_email'),
              'La livraison programmé avec Paps a échoué',
              print_r($delivery, true)
            );
          }

          wc_paps()->debug(
            'Delivery submitted with this parameters: ' . $paramsPaps
          );

          wc_paps()->debug('Paps response: ' . $delivery);
        }
      }
    }

    if ($order->status == $this->settings['delivery_cancellation']) {
      $pickup_id = get_post_meta($order_id, 'paps_pickup_id', true);
      $delivery_id = get_post_meta($order_id, 'paps_delivery_id', true);

      if ($pickup_id && $delivery_id) {
        if (wc_paps()->is_task_already_intransit($pickup_id, $delivery_id)) {
          echo '<div class="error">
            <p>' .
            __(
              'Une tâche déjà en cours ne peut pas être annulée, contactez support@paps-app.com pour toutes réclamations.',
              'paps-wc'
            ) .
            '</p>
          </div>';

          wp_mail(
            get_option('admin_email'),
            'Une tâche déjà en cours a tenté d\'être annulée:',
            $delivery
          );
        } else {
          $task_cancellation = wc_paps()
            ->api()
            ->cancelDelivery($pickup_id);

          $task_cancellation = wc_paps()
            ->api()
            ->cancelDelivery($delivery_id);

          wc_paps()->debug(
            'Canceling Delivery with ID: ' . $pickup_id . ' ' . $delivery_id
          );
          wc_paps()->debug(
            'Delivery cancellation response: ' . $task_cancellation
          );
        }

        if (!is_wp_error($task_cancellation)) {
          update_post_meta($order_id, 'paps_task_status', "cancelled");
        } else {
          wp_mail(
            get_option('admin_email'),
            'Lannulation de la commande avec Paps a échoué',
            print_r($delivery, true)
          );
        }
      }
    }
  }

  /**
   * @return null|Paps_API
   */
  public function api()
  {
    if (is_object($this->api)) {
      return $this->api;
    }

    $apiOption = [
      'api_key' => $this->settings['api_key']
    ];

    if ($this->settings['test'] == 'yes') {
      $apiOption['mode'] = "test";
    }

    $this->api = new Paps_API($apiOption);

    return $this->api;
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

  public function is_task_already_intransit($pickup_id, $delivery_id)
  {
    $isAlreadyIntransit = false;

    $pickupTask = wc_paps()
      ->api()
      ->getDelivery($pickup_id);
    $deliveryTask = wc_paps()
      ->api()
      ->getDelivery($delivery_id);

    $pickupStatus = $pickupTask['data'][0]['job_status'];
    $dropoffStatus = $deliveryTask['data'][0]['job_status'];

    wc_paps()->debug(
      'Is already in transit: ' . $pickupStatus . ' ' . $dropoffStatus
    );

    if (
      $pickupStatus == 1 ||
      $pickupStatus == 4 ||
      $pickupStatus == 7 ||
      $dropoffStatus == 1 ||
      $dropoffStatus == 4 ||
      $dropoffStatus == 7
    ) {
      $isAlreadyIntransit = true;
    }

    return $isAlreadyIntransit;
  }

  public function get_task_delivery_status_code($post_id)
  {
    $pickup_id = get_post_meta($post_id, 'paps_pickup_id', true);
    $delivery_id = get_post_meta($post_id, 'paps_delivery_id', true);
    $task_status = get_post_meta($post_id, 'paps_task_status', true);

    $deliveryStatusCode = null;

    // if ($task_status) {
    //   $deliveryStatusCode = $task_status;
    // } else

    if ($pickup_id && $delivery_id) {
      $pickupTask = wc_paps()
        ->api()
        ->getDelivery($pickup_id);
      $deliveryTask = wc_paps()
        ->api()
        ->getDelivery($delivery_id);

      $pickupStatus = $pickupTask['data'][0]['job_status'];
      $dropoffStatus = $deliveryTask['data'][0]['job_status'];

      if ($pickupStatus || $dropoffStatus) {
        if ($pickupStatus == 0 || $pickupStatus == 1 || $pickupStatus == 4) {
          $deliveryStatusCode = "pickup_started";
        } elseif (
          $pickupStatus == 2 &&
          $dropoffStatus != 0 &&
          $dropoffStatus != 1 &&
          $dropoffStatus != 2
        ) {
          $deliveryStatusCode = "pickup_completed";
        } elseif (
          $dropoffStatus == 0 ||
          $dropoffStatus == 1 ||
          $dropoffStatus == 4
        ) {
          $deliveryStatusCode = "dropoff_started";
        } elseif ($dropoffStatus == 2) {
          $deliveryStatusCode = "dropoff_completed";
        } else {
          $deliveryStatusCode = "sent_but_not_started";
        }
      }
    }

    wc_paps()->debug('Delivery status code: ' . $deliveryStatusCode);

    return $deliveryStatusCode;
  }

  /**
   * Debug Function to log messages or shown on frontend
   *
   * @param $message
   * @param string $type
   */
  public function debug($message, $type = 'notice')
  {
    if ($this->settings['debug'] == 'yes' && !is_admin()) {
      wc_add_notice($message, $type);
    }

    if (!is_object($this->logger)) {
      $this->logger = new WC_Logger();
    }

    if ($this->settings['logging_enabled'] == 'yes') {
      $this->logger->add('paps', $message);
    }
  }

  /**
   * Show shipping information on order view
   *
   * @param $order
   */
  public function show_delivery_details_on_order($order)
  {
    $shipping_method = @array_shift($order->get_shipping_methods());
    $shipping_method_id = $shipping_method['method_id'];

    // if ($shipping_method_id !== 'paps_relais') {
    //   wc_paps()->debug('shipping_method_id: ' . print_r($shipping_method));
    //   return;
    // }

    if (
      !($shipping_method_id == 'paps') &&
      !($shipping_method_id == 'paps_relais')
    ) {
      /* ?> <?php echo '<pre>', print_r($shipping_method_id, 1), '</pre>'; ?> <?php */
      return;
    }

    $delivery_status = wc_paps()->get_task_delivery_status_code($order->id);

    $text_status = wc_paps()
      ->api()
      ->getDeliveryStatus($delivery_status);

    if (!$text_status) {
      $text_status =
        'La commande est bien transmise. La livraison ne devrait pas tarder à commencer.';
    } else {
      update_post_meta($order->id, 'paps_task_status', $delivery_status);
    }

    $pickup_tracking_link = get_post_meta(
      $order->id,
      'paps_pickup_tracking_link',
      true
    );
    $delivery_tracking_link = get_post_meta(
      $order->id,
      'paps_delivery_tracking_link',
      true
    );
    $tracking_link = null;

    if ($delivery_status) {
      if (
        $delivery_status == "sent_but_not_started" ||
        $delivery_status == "pickup_started"
      ) {
        $tracking_link = $pickup_tracking_link;
      } else {
        $tracking_link = $delivery_tracking_link;
      }
    }

    # code...
    ?>

    <h2>Expédition</h2>

    <table class="shop_table paps_delivery">
      <tbody>
        <tr>
          <th>Livraison par:</th>
          <td><?php echo $shipping_method['name']; ?></td>
        </tr>

        <tr>
          <th>Statut de la livraison:</th>
          <td><?php echo $text_status; ?>
            <?php if ($tracking_link) { ?>
              <a target="_blank" href="<?php echo $tracking_link; ?>"> Cliquez ici</a> pour suivre la course.

            <?php } ?>
          </td>
        </tr>

      </tbody>
    </table>

  <?php
  }

  /**
   * Add Paps Column on Backend
   *
   * @param $columns
   * @return mixed
   */
  function add_paps_delivery_column($columns)
  {
    $columns['paps_delivery'] = 'Paps';
    return $columns;
  }

  /**
   * Show Paps Delivery Status
   *
   * @param $col
   * @param $post_id
   */
  function delivery_status_on_backend($col, $post_id)
  {
    if ($col == 'paps_delivery') {
      $delivery_status = wc_paps()->get_task_delivery_status_code($post_id);

      $text_status = wc_paps()
        ->api()
        ->getDeliveryStatus($delivery_status, 'admin');

      wc_paps()->debug('Status: ' . $text_status);

      if ($text_status) {
        echo $text_status;
      } else {
        echo 'Pas encore reçue';
      }
    }
  }
}

/**
 * @return null|WC_Paps
 */
function wc_paps()
{
  return WC_Paps::get();
}

/**
 * Load Libraries and load main class
 */
require_once 'vendor/autoload.php';
wc_paps();
