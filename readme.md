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


Importez les routes exposées par le bundle dans votre fichier config/routes/yousign_webhook.yaml :

```yaml
# config/yousign_webhook.yaml
yousign_webhook:
    resource: '@YousignWebhookBundle/Resources/config/routes.yaml'
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