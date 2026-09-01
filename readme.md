# YousignWebhookBundle

Un bridge Symfony permettant de recevoir les événements Webhook de Yousign via le composant [`RemoteEvent`](https://symfony.com/doc/current/components/remote_event.html).

## 📦 Installation

Ajoutez ce bundle à votre projet Symfony via Composer :

```bash
composer require zeggriim/yousign-webhook-bundle
```

## ⚙️ Activer le bundle

Symfony Flex activera automatiquement le bundle si vous l’avez installé comme un package distant. Sinon, ajoutez-le manuellement dans config/bundles.php :
```php
return [
    Zeggriim\YousignWebhookBundle\YousignWebhookBundle::class => ['all' => true],
];
```

## 🔀 Configuration

Branchez le parser du bundle sur le composant [Webhook](https://symfony.com/doc/current/webhook.html)
de Symfony, avec la clé secrète fournie par Yousign dans l'admin :

```yaml
# config/packages/framework.yaml
framework:
    webhook:
        routing:
            yousign:
                service: Zeggriim\YousignWebhookBundle\Webhook\YousignRequestParser
                secret: '%env(YOUSIGN_WEBHOOK_SECRET)%'
```

Puis exposez la route du composant :

```yaml
# config/routes/webhook.yaml
webhook:
    resource: '@FrameworkBundle/Resources/config/routing/webhook.php'
    prefix: /webhook
```

L'endpoint à déclarer côté Yousign est alors :

```bash
POST /webhook/yousign
```

Une entrée `routing` par abonnement Yousign : chacune a son propre secret et son
propre nom de consumer.

Enfin, désactivez le contrôleur historique du bundle, déprécié depuis la 0.3 :

```yaml
# config/packages/yousign_webhook.yaml
yousign_webhook:
    legacy_controller: false
```

> Les versions antérieures exposaient un contrôleur et une route maison
> configurés via `yousign_webhook.secret` / `endpoint` / `type`. Voir
> [UPGRADE.md](UPGRADE.md) pour la migration.

## Exemple cas d'utilisation

Une fois terminé, ajoutez un consumer avec le RemoteEvent en utilisant le name 'yousign'.
Cela te permettra de réagir avec le webhook entrants.

```php
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer('yousign')]
final class YousignWebhookConsumer implements ConsumerInterface
{
    public function consume(RemoteEvent $event): void
    {
        // Implement your own logic here
    }
}
```
## ⏱️ Répondre en moins d'une seconde

Yousign coupe la connexion au bout d'**1 seconde** lors de la première tentative
(10 secondes lors des retries) et considère la livraison en échec, même si votre
application finit par répondre `2xx`. Un traitement métier synchrone provoque
donc jusqu'à 8 redélivrances du même événement.

Routez `ConsumeRemoteEventMessage` vers un transport asynchrone :

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async: '%env(MESSENGER_TRANSPORT_DSN)%'
        routing:
            Symfony\Component\RemoteEvent\Messenger\ConsumeRemoteEventMessage: async
```

Vos consumers restent identiques : ils sont simplement exécutés par
`messenger:consume async` au lieu de l'être pendant la requête HTTP.

## 🔒 Restreindre les IP appelantes (optionnel)

```yaml
# config/packages/yousign_webhook.yaml
yousign_webhook:
    secret: '%env(SECRET_YOUSIGN)%'
    allowed_ips:
        - '57.130.41.144/28'
        - '51.38.96.112/28'
        - '5.39.7.128/28'
        - '52.143.162.31'
        - '51.103.81.166'
```

Vide par défaut (contrôle désactivé). N'activez l'option que si votre
application est atteinte directement par Yousign, ou si vos reverse proxies sont
déclarés dans `framework.trusted_proxies` — sinon l'IP cliente n'est pas fiable.

## ♻️ Idempotence

Un même événement peut être livré jusqu'à 9 fois (1 envoi + 8 retries) : son
`event_id` reste identique d'une tentative à l'autre. Activez le
dédoublonnage pour ne le traiter qu'une seule fois :

```yaml
# config/packages/yousign_webhook.yaml
yousign_webhook:
    secret: '%env(SECRET_YOUSIGN)%'
    idempotency:
        enabled: true
        pool: cache.app   # n'importe quel pool PSR-6
        ttl: 86400        # durée de mémorisation, en secondes
```

Les redélivrances déjà connues reçoivent un `202` sans être republiées sur le
bus. Côté consumer, `$event->isRetry()` et `$event->getRetryCount()` restent
disponibles pour tracer les tentatives.
