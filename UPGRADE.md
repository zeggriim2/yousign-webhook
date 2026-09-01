# Guide de migration

## 0.2 → 0.3

### Le contrôleur maison est déprécié au profit du composant `symfony/webhook`

Le bundle expose désormais un `YousignRequestParser` branché sur le composant
[Webhook](https://symfony.com/doc/current/webhook.html) de Symfony, comme les
bridges officiels. Le contrôleur et la route fournis par le bundle restent
disponibles en `0.3` mais seront supprimés en `1.0`.

**Avant**

```yaml
# config/packages/yousign_webhook.yaml
yousign_webhook:
    secret: '%env(SECRET_YOUSIGN)%'

# config/routes/yousign_webhook.yaml
yousign_webhook:
    resource: '@YousignWebhookBundle/Resources/config/routes.yaml'
```

**Après**

```yaml
# config/packages/framework.yaml
framework:
    webhook:
        routing:
            yousign:
                service: Zeggriim\YousignWebhookBundle\Webhook\YousignRequestParser
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

### Plusieurs abonnements

Chaque abonnement Yousign peut avoir son propre secret :

```yaml
framework:
    webhook:
        routing:
            yousign_signature:
                service: Zeggriim\YousignWebhookBundle\Webhook\YousignRequestParser
                secret: '%env(YOUSIGN_SIGNATURE_SECRET)%'
            yousign_verification:
                service: Zeggriim\YousignWebhookBundle\Webhook\YousignRequestParser
                secret: '%env(YOUSIGN_VERIFICATION_SECRET)%'
```

Les événements sont alors consommés par `#[AsRemoteEventConsumer('yousign_signature')]`
et `#[AsRemoteEventConsumer('yousign_verification')]`.

### Autres changements

- `Zeggriim\YousignWebhookBundle\DependencyInjection\Configuration` et
  `YousignWebhookExtension` ont été supprimés : la configuration est portée par
  `YousignWebhookBundle` (`AbstractBundle`). Ces classes n'étaient pas destinées
  à être utilisées directement.
- La dépendance `webmozart/assert` a été retirée.
- L'option `secret` a une valeur par défaut vide : elle n'est requise que si le
  contrôleur déprécié est actif.
