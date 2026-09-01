# Guide de migration

## 0.1 → 1.0

### Yousign devient YouTrust

Le package s'appelle désormais **`zeggriim/youtrust-webhook-bundle`** et le
namespace est `Zeggriim\YouTrustWebhookBundle\`.

L'ancien package `zeggriim/yousign-webhook-bundle` est marqué *abandoned* sur
Packagist et reste figé en `v0.1.2`. Pour passer en `1.0`, changez la
dépendance :

```bash
composer remove zeggriim/yousign-webhook-bundle
composer require zeggriim/youtrust-webhook-bundle
```

L'ancien nom est déclaré en `replace` : si un autre paquet en dépend de façon
transitive, la résolution reste satisfaite sans conflit.

| Avant                                    | Après                                       |
| ---------------------------------------- | ------------------------------------------- |
| `YousignWebhookBundle`                   | `YouTrustWebhookBundle`                     |
| `Webhook\YousignRequestParser`           | `Webhook\YouTrustRequestParser`             |
| `Webhook\YousignConverter`               | `Webhook\YouTrustConverter`                 |
| `Webhook\YousignIdempotencyStore`        | `Webhook\YouTrustIdempotencyStore`          |
| `Webhook\Payload\YousignPayload`         | `Webhook\Payload\YouTrustPayload`           |
| `RemoteEvent\YousignRemoteEvent`         | `RemoteEvent\YouTrustRemoteEvent`           |
| `RemoteEvent\Consumer\AbstractYousignConsumer` | `RemoteEvent\Consumer\AbstractYouTrustConsumer` |
| `Security\YousignSignatureVerifier`      | `Security\YouTrustSignatureVerifier`        |
| `Security\YousignIpChecker`              | `Security\YouTrustIpChecker`                |
| `Enum\YousignEvent`                      | `Enum\YouTrustEvent`                        |
| `Controller\YousignWebhookController`    | `Controller\YouTrustWebhookController`      |
| clé de configuration `yousign_webhook`   | clé de configuration `youtrust_webhook`     |

Les anciens noms restent utilisables : ils sont créés à la volée en alias, avec
une dépréciation à la première utilisation, et seront supprimés en `2.0`.
L'ancienne classe de bundle `Zeggriim\YousignWebhookBundle\YousignWebhookBundle`
existe toujours et conserve la clé de configuration `yousign_webhook`, ce qui
permet de mettre à jour sans rien changer, puis de migrer.

Ce qui **ne change pas** : les en-têtes HTTP restent `X-Yousign-Signature-256`
et `X-Yousign-Retry` (c'est le protocole envoyé par YouTrust), et la clé de
routing `framework.webhook.routing.yousign` reste libre — elle détermine
simplement l'URL et le nom du consumer.

Migration recommandée :

1. Remplacer `Zeggriim\YousignWebhookBundle\YousignWebhookBundle::class` par
   `Zeggriim\YouTrustWebhookBundle\YouTrustWebhookBundle::class` dans
   `config/bundles.php`.
2. Renommer la clé `yousign_webhook` en `youtrust_webhook`.
3. Remplacer les imports `Zeggriim\YousignWebhookBundle\…` par
   `Zeggriim\YouTrustWebhookBundle\…` (les dépréciations affichées en `dev`
   listent exactement les classes concernées).

### Le contrôleur maison est déprécié au profit du composant `symfony/webhook`

Le bundle expose désormais un `YouTrustRequestParser` branché sur le composant
[Webhook](https://symfony.com/doc/current/webhook.html) de Symfony, comme les
bridges officiels. Le contrôleur et la route fournis par le bundle restent
disponibles en `1.0` mais seront supprimés en `2.0`.

**Avant**

```yaml
# config/packages/yousign_webhook.yaml
yousign_webhook:
    secret: '%env(SECRET_YOUSIGN)%'

# config/routes/yousign_webhook.yaml
yousign_webhook:
    resource: '@YouTrustWebhookBundle/Resources/config/routes.yaml'
```

**Après**

```yaml
# config/packages/framework.yaml
framework:
    webhook:
        routing:
            yousign:
                service: Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustRequestParser
                secret: '%env(YOUSIGN_WEBHOOK_SECRET)%'

# config/routes/webhook.yaml
webhook:
    resource: '@FrameworkBundle/Resources/config/routing/webhook.php'
    prefix: /webhook

# config/packages/yousign_webhook.yaml
yousign_webhook:
    legacy_controller: false
```

Puis supprimez `config/routes/yousign_webhook.yaml` ainsi que les options
`secret`, `endpoint` et `type` du bundle : elles ne concernent plus que le
contrôleur déprécié.

L'URL reste `POST /webhook/yousign` (`/webhook/{clé de routing}`) et vos
consumers `#[AsRemoteEventConsumer('yousign')]` sont inchangés.

Tant que `legacy_controller` vaut `true`, une dépréciation est déclenchée au
démarrage du conteneur et l'option `secret` reste obligatoire.

> Il n'y a pas de version `0.2` ni `0.3` : la ligne `0.x` s'arrête à `v0.1.2`,
> tout ce qui suit est publié directement en `1.0.0`.

### Plusieurs abonnements

Chaque abonnement Yousign peut avoir son propre secret :

```yaml
framework:
    webhook:
        routing:
            yousign_signature:
                service: Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustRequestParser
                secret: '%env(YOUSIGN_SIGNATURE_SECRET)%'
            yousign_verification:
                service: Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustRequestParser
                secret: '%env(YOUSIGN_VERIFICATION_SECRET)%'
```

Les événements sont alors consommés par `#[AsRemoteEventConsumer('yousign_signature')]`
et `#[AsRemoteEventConsumer('yousign_verification')]`.

### Autres changements de la 1.0

- `DependencyInjection\Configuration` et `YousignWebhookExtension` ont été
  supprimés : la configuration est portée par le bundle (`AbstractBundle`).
  Ces classes n'étaient pas destinées à être utilisées directement.
- La dépendance `webmozart/assert` a été retirée.
- L'option `secret` a une valeur par défaut vide : elle n'est requise que si le
  contrôleur déprécié est actif.
- Un corps de requête JSON invalide répond `406` au lieu de `500`.
