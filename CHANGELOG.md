# Changelog

Toutes les évolutions notables de ce projet sont documentées dans ce fichier.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le
projet applique le [versionnage sémantique](https://semver.org/lang/fr/).

## [Non publié]

### Ajouté

- Support du format de payload YouTrust v3 (`metadata` imbriqué), l'ancien
  format à plat restant accepté.
- `YouTrustRequestParser` pour le composant `symfony/webhook` : configuration
  via `framework.webhook.routing`, un secret par abonnement.
- Énumération `YouTrustEvent` (56 événements documentés) et modèles typés
  `SignatureRequest` / `Signer`, tolérants aux champs inconnus.
- `AbstractYouTrustConsumer`, qui route chaque événement vers sa propre méthode.
- Idempotence optionnelle sur `event_id` (`youtrust_webhook.idempotency`).
- Allowlist d'IP optionnelle (`youtrust_webhook.allowed_ips`).
- Exposition du numéro de tentative de livraison (`X-Yousign-Retry`) via
  `getRetryCount()` / `isRetry()`.
- Journalisation PSR-3 des rejets et des échecs de dispatch.
- Fixtures JSON des 56 événements dans `tests/Fixtures/Events/`.

- `BACKLOG.md` décrivant les évolutions prévues pour l'alignement sur l'API
  YouTrust v3.
- Fichier `LICENSE` (MIT), déjà déclaré dans `composer.json`.
- Intégration continue matricielle : PHP 8.2 à 8.4, Symfony 6.4, 7.x et 8.x,
  résolution `--prefer-lowest` incluse.

### Modifié

- Renommage Yousign → YouTrust : package `zeggriim/youtrust-webhook-bundle`,
  namespace `Zeggriim\YouTrustWebhookBundle\`, clé de configuration
  `youtrust_webhook`. Les anciens noms restent disponibles en alias dépréciés
  (voir [UPGRADE.md](UPGRADE.md)).
- Un JSON invalide répond désormais `406` au lieu de `500`.
- La signature est vérifiée de façon plus stricte (préfixe `sha256=` exigé,
  comparaison à temps constant sur la valeur calculée).
- Contraintes de dépendances : PHP 8.2 minimum, Symfony 6.4 LTS, 7.x et 8.x.
  Symfony 5.4 et 6.0 ne sont plus supportés.

### Déprécié

- Le contrôleur et la route fournis par le bundle
  (`youtrust_webhook.legacy_controller`, options `secret`, `endpoint`, `type`) :
  utilisez `framework.webhook.routing`. Suppression prévue en `2.0`.

### Supprimé

- Dépendance `webmozart/assert`.
- Classes `DependencyInjection\Configuration` et `YousignWebhookExtension`,
  remplacées par la configuration portée par le bundle (`AbstractBundle`).

## [0.1.2] - 2025

Première série de versions publiques du bundle.
