# YouTrustWebhookBundle

[![CI](https://github.com/zeggriim/youtrust-webhook/actions/workflows/ci.yaml/badge.svg)](https://github.com/zeggriim/youtrust-webhook/actions/workflows/ci.yaml)

Bridge Symfony pour recevoir les webhooks **YouTrust** (ex-Yousign) via les
composants [Webhook](https://symfony.com/doc/current/webhook.html) et
[RemoteEvent](https://symfony.com/doc/current/components/remote_event.html).

- vérification de la signature HMAC-SHA256 sur le corps brut ;
- payloads YouTrust v3 (`metadata` + `data`) **et** ancien format à plat ;
- 56 événements documentés typés dans une énumération, plus modèles
  `SignatureRequest` / `Signer` tolérants aux évolutions de l'API ;
- idempotence optionnelle sur `event_id` (YouTrust redélivre jusqu'à 8 fois) ;
- allowlist d'IP optionnelle.

## 📦 Installation

```bash
composer require zeggriim/youtrust-webhook-bundle
```

> Ce package s'appelait `zeggriim/yousign-webhook-bundle` jusqu'à la `v0.1.2`.
> Cette entrée Packagist est abandonnée et figée : voir [UPGRADE.md](UPGRADE.md)
> pour passer en `1.0`.

Sans Symfony Flex, déclarez le bundle :

```php
// config/bundles.php
return [
    Zeggriim\YouTrustWebhookBundle\YouTrustWebhookBundle::class => ['all' => true],
];
```

## ⚙️ Configuration

Branchez le parser sur le composant Webhook, avec la clé secrète fournie par
YouTrust dans l'admin :

```yaml
# config/packages/framework.yaml
framework:
    webhook:
        routing:
            yousign:
                service: Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustRequestParser
                secret: '%env(YOUTRUST_WEBHOOK_SECRET)%'
```

Exposez la route du composant :

```yaml
# config/routes/webhook.yaml
webhook:
    resource: '@FrameworkBundle/Resources/config/routing/webhook.php'
    prefix: /webhook
```

L'endpoint à déclarer côté YouTrust est alors :

```
POST https://votre-domaine.tld/webhook/yousign
```

Une entrée `routing` par abonnement : chacune a son propre secret et son propre
nom de consumer, ce qui permet de séparer par workspace ou par famille
d'événements.

```yaml
framework:
    webhook:
        routing:
            youtrust_signature:
                service: Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustRequestParser
                secret: '%env(YOUTRUST_SIGNATURE_SECRET)%'
            youtrust_verification:
                service: Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustRequestParser
                secret: '%env(YOUTRUST_VERIFICATION_SECRET)%'
```

## 🎯 Consommer les événements

Un consumer `RemoteEvent` classique reçoit tous les événements :

```php
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer('yousign')]
final class YouTrustWebhookConsumer implements ConsumerInterface
{
    public function consume(RemoteEvent $event): void
    {
        // Implement your own logic here
    }
}
```

Pour éviter un gros `match` sur le nom de l'événement, étendez plutôt
`AbstractYouTrustConsumer` : chaque événement est routé vers sa propre méthode,
nommée d'après lui (`signature_request.done` → `onSignatureRequestDone`,
`verification.identity_document.done` → `onVerificationIdentityDocumentDone`).

```php
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Zeggriim\YouTrustWebhookBundle\RemoteEvent\Consumer\AbstractYouTrustConsumer;
use Zeggriim\YouTrustWebhookBundle\RemoteEvent\YouTrustRemoteEvent;

#[AsRemoteEventConsumer('yousign')]
final class YouTrustWebhookConsumer extends AbstractYouTrustConsumer
{
    protected function onSignatureRequestDone(YouTrustRemoteEvent $event): void
    {
        $signatureRequest = $event->getSignatureRequest();

        $this->archive($signatureRequest?->id, $signatureRequest?->externalId);
    }

    protected function onSignerDone(YouTrustRemoteEvent $event): void
    {
        $signer = $event->getSigner();

        $this->notify($signer?->email, $signer?->fullName());
    }

    // Tous les autres événements
    protected function onEvent(YouTrustRemoteEvent $event): void
    {
        // ...
    }
}
```

### Données disponibles

| Méthode                         | Retour                                                                              |
| ------------------------------- | ----------------------------------------------------------------------------------- |
| `getEventType()`                | `YouTrustEvent` ou `null` si YouTrust a ajouté un événement plus récent que le bundle |
| `getSignatureRequest()`         | `SignatureRequest` ou `null`                                                          |
| `getSigner()`                   | `Signer` ou `null`                                                                    |
| `getData()`                     | le contenu brut de la clé `data`                                                      |
| `getPayload()`                  | le payload complet, non modifié                                                       |
| `isSandbox()`                   | environnement sandbox ou production                                                   |
| `getRetryCount()` / `isRetry()` | numéro de tentative de livraison (`X-Yousign-Retry`)                                  |
| `getEventTime()`                | date de l'événement                                                                   |

Les modèles typés sont tolérants : une propriété absente ou inattendue vaut
`null` plutôt que de faire échouer le traitement, et `->raw` donne accès au
tableau d'origine. L'énumération `YouTrustEvent` couvre les 56 événements
documentés (`signature_request.*`, `signer.*`, `approver.*`, `contact.*`,
`user.*`, `electronic_seal.*`, `verification.*`, `monitoring.*`,
`document_analysis.*`, `workflow_session.*`, `workflow_action_group.*`,
`applicant.*`).

## ⏱️ Répondre en moins d'une seconde

YouTrust coupe la connexion au bout d'**1 seconde** lors de la première
tentative (10 secondes lors des retries) et considère la livraison en échec,
même si votre application finit par répondre `2xx`. Un traitement métier
synchrone provoque donc jusqu'à 8 redélivrances du même événement.

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

Vos consumers restent identiques : ils sont exécutés par
`messenger:consume async` au lieu de l'être pendant la requête HTTP.

## ♻️ Idempotence

Un même événement peut être livré jusqu'à 9 fois (1 envoi + 8 retries) : son
`event_id` reste identique d'une tentative à l'autre.

```yaml
# config/packages/youtrust_webhook.yaml
youtrust_webhook:
    legacy_controller: false
    idempotency:
        enabled: true
        pool: cache.app   # n'importe quel pool PSR-6
        ttl: 86400        # durée de mémorisation, en secondes
```

Les redélivrances déjà connues sont acquittées sans être republiées sur le bus.

## 🔒 Restreindre les IP appelantes (optionnel)

```yaml
# config/packages/youtrust_webhook.yaml
youtrust_webhook:
    allowed_ips:
        - '57.130.41.144/28'
        - '51.38.96.112/28'
        - '5.39.7.128/28'
        - '52.143.162.31'
        - '51.103.81.166'
```

Vide par défaut (contrôle désactivé). N'activez l'option que si votre
application est atteinte directement par YouTrust, ou si vos reverse proxies
sont déclarés dans `framework.trusted_proxies` — sinon l'IP cliente n'est pas
fiable.

## 🧪 Tester en local

1. Exposez votre application : `ngrok http 8000` (ou utilisez
   [webhook.site](https://webhook.site) pour inspecter les payloads bruts).
2. Créez une subscription **sandbox** pointant sur
   `https://<votre-tunnel>/webhook/yousign`.
3. Déclenchez une signature request de test et suivez les livraisons dans les
   logs de webhooks de l'admin YouTrust.
4. Pensez à supprimer les subscriptions de test : les endpoints injoignables
   génèrent des tentatives inutiles.

Les payloads des 56 événements sont également disponibles dans
`tests/Fixtures/Events/` pour rejouer un cas sans dépendre du réseau :

```bash
BODY=$(cat tests/Fixtures/Events/signature_request.done.json)
curl -X POST http://localhost:8000/webhook/yousign \
    -H 'Content-Type: application/json' \
    -H "X-Yousign-Signature-256: sha256=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')" \
    -d "$BODY"
```

## 🔁 Migration

- Yousign → YouTrust et abandon du contrôleur maison : voir [UPGRADE.md](UPGRADE.md).
- Évolutions prévues : voir [BACKLOG.md](BACKLOG.md).
- Procédure de publication : voir [docs/RELEASE.md](docs/RELEASE.md).

## 🛠️ Développement

```bash
make install   # dépendances Composer
make test      # PHPUnit
make phpstan   # analyse statique (niveau max)
make cs        # style de code (dry-run)
make cs-fix    # style de code (correction)
```

## Licence

MIT — voir [LICENSE](LICENSE).
