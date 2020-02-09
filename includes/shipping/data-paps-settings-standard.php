<?php

if (!defined('ABSPATH')) {
  exit();
}

/*
 * WooCommerce Order Statuses to be used in settings
 */
$delivery_submission_statuses = array_filter(
  wc_get_order_statuses(),
  function ($el) {
    if (in_array($el, ['wc-cancelled', 'wc-refunded', 'wc-failed'])) {
      return false;
    }

    return $el;
  },
  ARRAY_FILTER_USE_KEY
);

$delivery_cancellation_statuses = array_filter(
  wc_get_order_statuses(),
  function ($el) {
    if (in_array($el, ['wc-cancelled', 'wc-refunded', 'wc-failed'])) {
      return $el;
    }

    return false;
  },
  ARRAY_FILTER_USE_KEY
);

/**
 * Array of settings
 */
return array(
  'enabled' => array(
    'title' => __('Expédition avec Paps Standard', 'paps-wc'),
    'type' => 'checkbox',
    'label' => __('Activé', 'paps-wc'),
    'default' => 'no'
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
  // 'title' => array(
  //   'title' => __('Titre de la méthode', 'paps-wc'),
  //   'type' => 'text',
  //   'description' => __(
  //     'Ceci contôle le titre qui s\'affiche durant le check-out',
  //     'paps-wc'
  //   ),
  //   'default' => __('Forfait', 'paps-wc'),
  //   'desc_tip' => true
  // ),
  'api_key' => array(
    'title' => __('Clé API', 'paps-wc'),
    'type' => 'text',
    'description' => __(
      'Le clé API vous a été envoyée dans l\'email de confirmation après l\'avoir obtenue sur https://developers.paps.sn',
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
    'description' => __(
      'Nom de la personne en charge des expéditions',
      'paps-wc'
    ),
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
  'email_monespace_account' => array(
    'title' => __(
      'Adresse email enregistré sur votre compte Monespace',
      'paps-wc'
    ),
    'type' => 'text',
    'description' => __(
      'Optionnel, Renseignez ce champs si vous souhaitez suivre les commandes votre compte sur votre espace client, Monespace',
      'paps-wc'
    ),
    'default' => ''
  ),
  // 'is_packs_enabled' => array(
  //   'title' => __('Courses avec Packs achetés', 'paps-wc'),
  //   'type' => 'checkbox',
  //   'label' => __('Activé', ' '),
  //   'desc_tip' => true,
  //   'description' => __(
  //     'Lorsque activée, cette option permet aux client de pouvoir choisir lui-même le mode de livraison Express ou Programmé (Standard) avec une tarification fixe. Note: vous devez forcément acheter un pack auprès du service commercial.',
  //     'paps-wc'
  //   ),
  //   'default' => 'no'
  // ),
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
  'added_flat_rate' => array(
    'title' => __('Frais en supplément', 'paps-wc'),
    'type' => 'number',
    'description' => __(
      'Montant fixe s\'ajoutant aux frais de livraison calculés par Paps.',
      'paps-wc'
    ),
    'default' => ''
  ),
  'flat_rate' => array(
    'title' => __('Montant forfait pour toutes les courses', 'paps-wc'),
    'type' => 'number',
    'desc_tip' => true,
    'description' => __(
      'Montant fixe des frais de livraison sur toute la plateforme. Important: en choisissant ce mode vous supportez vous-même tous les frais de livraison s\'ajoutant au tarif normal de la course.',
      'paps-wc'
    ),
    'default' => ''
  ),
  'signature_secret_key' => array(
    'title' => __('Clé de Signature Secrète', 'paps-wc'),
    'type' => 'text',
    'description' => __(
      'Optionnel, Utilisé pour valider les requêtes Webhook',
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
  'debug' => array(
    'title' => __('Mode Debug', 'paps-wc'),
    'label' => __('Activer le mode debug', 'paps-wc'),
    'type' => 'checkbox',
    'default' => 'no',
    'desc_tip' => true,
    'description' => __(
      'Activer le mode debug pour montrer les informations de debugging sur la carte checkout.',
      'paps-wc'
    )
  ),
  'notify_admin_on_failure' => array(
    'title' => __(
      'Envoyer un email à l\'admin lorqu\'il y a un erreur',
      'paps-wc'
    ),
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
