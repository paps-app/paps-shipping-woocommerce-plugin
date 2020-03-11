=== Paps Integration for WooCommerce ===
Contributors: madiodio 
Tags: paps, paps-api, livraison, woocommerce, woocommerce shipping, paps shipping
Requires at least: 4.0
Tested up to: 5.2.3
Stable tag: 1.4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

L'intégration de Paps pour WooCommerce vous permet de gérer efficacement et sans effort votre demandes de livraison de colis directement à travers votre admin Wordpress.

== Description ==

Important, veuillez mettre à jour le plugin si vous utilisez toujours la version 1.0.0. Beaucoup d'améliorations ont été introduites et vous permettent de bénéficier des dernières mises à jour pour de notre API.

En utilisant cette extension, vous vous offrez la possibilité de pouvoir recevoir et supporter les demandes de livraison sur votre plateforme ou boutique en ligne. En intégrant notre extension, vous réduisez non seulement tout le stress lié à la livraison de vos colis (état d'avancement de chaque course) et les coûts que cela peut occasionner en ayant un tarif unique pour toutes les courses dans les localités situées dans la même ville.

Plugin Features:

- Le statut de chaque livraison dans la page des commandes.
- Notes personnalisées pour le coursier qui s'occupe du ramassage.
- Statut de la livraison directement chez l'utilisateur avec un lien de suivi.
- Possibilité pour l'admin de prise en charge express des courses.
- Calcul automatique du tarif de la livraison.
- Possibilité de fixer un montant forfaitaire pour toutes les livraisons.
- Envoi d'email à l'admin quand la demande de livraison échoue.
- Etc.

Bientôt disponible:

- Possibilité pour l'utilisateur de choisir lui-même une livraison Express ou Standard (Programmé).
- plus


== Installation ==

Requis:

- Une clé de sécurité de l'API que vous pouvez obtenir en allant visiter sur https://developers.paps.sn et cliquer le bouton "Obtenir une clé" dans la page d'accueil.

- Adresse de votre entrepôt/warehouse où se trouvent vos colis (Peut être l'adresse de votre entreprise). Vous pouvez mettre l'adresse notre entrepôt Paps si vous avez choisi de stocker vos produits chez nous.

Optionnel:
- Une signature secrète (seulement si vous souhaitez utiliser les Webhooks) 

== Support ==

Si vous avez une question, veuillez envoyer un email à dev@paps-app.com

== Frequently Asked Questions ==

Visitez la documentation officielle sur https://developers.paps.sn

== Screenshots ==
1. Admin status.
2. Settings.
3. Settings.

== Installation ==

= Minimum Requirements =

* WooCommerce 2.2 or later
* WordPress 4.0 or later

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of WooCommerce, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “WooCommerce Paps Integration” and click Search Plugins. Once you’ve found the plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading the plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

== Changelog ==

= 1.3.5 =
Ajout des détails de la livraison dans le corps de l'email. A noter que cela ne prend les liens de tracking que lorque vous déclenchez l'envoi d'email de la facture pour le moment.

= 1.3.1 =

Régler le problème du loader qui se bloque sur la page de checkout de la commande lorsque l'utilisateur change un champ qui déclenche le ajax update. (Argh, ça a duré celui-là .)

= 1.2.1 =

Mise à jour des dépendances qui affichent des erreurs sur l'inteface utilisateur

= 1.2.0 =
- Prise en charge automatique des courses Express ou Standard (programmé)- Possibilité de fixer un montant forfaitaire pour toutes les courses,  - Méthode de livraison supporté automatiquement avec Paps Express ou Paps Standard
- Calcul automatique des tarifs de livraison, mise à jour en temps réél d'une course sur l'espace Admin et utilisateur et plus.

= 1.0.0 =
Release of the first version

== Upgrade Notice ==

Prière de mettre à jour le plugin au minimum sur la version 1.2.0 pour profiter des dernières améliorations: prise en charge automatique des courses Express ou Standard (programmé), possibilité de fixer un montant forfaitaire pour toutes les courses, méthode de livraison supporté automatiquement avec Paps Express ou Paps Standard, calcul automatique des tarifs de livraison, mise à jour en temps réél d'une course sur l'espace Admin et utilisateur et plus.

1. Activer ou désactiver les courses express dans les réglages (Woocommerce > Réglages > Expédition > Paps)
2. Allez dans les configs des méthodes de livraison et choisir soit Paps Express ou Paps Standard
3. Optionnellement si vous le souhaitez, fixez un montant forfaitaire pour toutes les courses. Notez que dans ce cas, les frais de livraison normaux déduits de la course vous seront facturés à vous-mêmes.
4. Enjoy 😎
