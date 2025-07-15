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

## 🔀 Configuration des routes

Ajouter la cléf secret fourni par Yousign dans l'admin :

```yaml
# config/packages/yousign_webhook.yaml
yousign_webhook:
    secret: "%env(SECRET_YOUSIGN)%"
```


Importez les routes exposées par le bundle dans votre fichier config/routes.yaml :

```yaml
yousign_webhook:
    resource: '@YousignWebhookBundle/Resources/config/routing/routes.yaml'
```

Cela exposera un endpoint (par défaut) POST accessible sur :
```bash
/webhook/yousign
```

Vous pouvez surcharger ce chemin en définissant un paramètre :
```yaml
# config/packages/yousign_webhook.yaml
parameters:
    yousign.webhook.endpoint: /votre/endpoint/personnalisé
```