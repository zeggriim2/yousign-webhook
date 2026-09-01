# Backlog d'amélioration

Ce document recense les évolutions prévues pour aligner le bundle sur l'API
**YouTrust (ex-Yousign) v3** documentée sur <https://developers.youtrust.com>.

## Contexte

Le bundle reçoit les webhooks Yousign et les republie via le composant Symfony
`RemoteEvent`. Il fonctionne, mais il a été écrit contre l'ancienne forme du
payload et reste minimal : contrôleur maison, payload en `array<string, mixed>`,
aucun typage des ~45 événements, tests limités au contrôleur.

La documentation YouTrust v3 décrit aujourd'hui :

- un payload `{ "metadata": { event_id, event_name, event_time, subscription_id,
  subscription_description, sandbox }, "data": {…} }` — alors que
  `YouTrustPayload` lit ces champs **à la racine**, ce qui provoque une
  `ParseException` systématique sur ce format ;
- une politique de retry (`X-Yousign-Retry`, timeout **1 s** au premier envoi et
  10 s ensuite, 8 tentatives, suspension d'endpoint) et une recommandation
  explicite d'**idempotence sur `event_id`** ;
- une recommandation « tolerant reader » : ignorer les champs inconnus, de
  nouveaux événements pouvant apparaître sans changement de version majeure ;
- un catalogue d'événements documenté (`signature_request.*`, `signer.*`,
  `approver.*`, `contact.*`, `user.*`, `electronic_seal.*`, `verification.*`,
  `monitoring.*`, `document_analysis.*`, `workflow_session.*`,
  `workflow_action_group.*`, `applicant.*`).

**Objectif** : une lib *webhook-only* mais fiable, typée et idiomatique Symfony.

## Décisions de cadrage

| Sujet            | Décision                                                                                  |
| ---------------- | ----------------------------------------------------------------------------------------- |
| Périmètre        | Webhook uniquement. La gestion des subscriptions via l'API `/webhooks` est hors scope.      |
| Réception HTTP   | Migration vers le composant `symfony/webhook` (`AbstractRequestParser`).                    |
| Branding         | Renommage Yousign → YouTrust avec alias de rétro-compatibilité dépréciés.                   |
| Compatibilité    | PHP 8.2+, Symfony 6.4 LTS et 7.x (abandon de 5.4 / 6.0).                                     |

## État d'avancement

| Ticket | Sujet                                             | État |
| ------ | ------------------------------------------------- | ---- |
| A1     | Payload `metadata` imbriqué + rétro-compat         | ✅   |
| A2     | Tolerant reader                                    | ✅   |
| A3     | Métadonnées de livraison (`X-Yousign-Retry`)       | ✅   |
| B1     | Vérification de signature durcie                   | ✅   |
| B2     | Gestion d'erreurs et journalisation                | ✅   |
| B3     | Idempotence sur `event_id`                         | ✅   |
| B4     | Documentation de la réponse < 1 s                  | ✅   |
| B5     | Allowlist d'IP                                     | ✅   |
| C1     | `YouTrustRequestParser` (`symfony/webhook`)        | ✅   |
| C2     | Dépréciation du contrôleur maison                  | ✅   |
| C3     | Multi-abonnements                                  | ✅   |
| C4     | Passage à `AbstractBundle`                         | ✅   |
| D1     | Énumération des 56 événements                      | ✅   |
| D2     | Modèles typés `SignatureRequest` / `Signer`        | ✅   |
| D3     | Consumer avec dispatch par événement               | ✅   |
| E1     | Fixtures JSON des 56 événements                    | ✅   |
| E2     | Couverture unitaire et fonctionnelle               | ✅   |
| E3     | CI matricielle                                     | ✅   |
| E4     | Hygiène du dépôt                                   | ✅   |
| F1     | Renommage YouTrust avec alias de BC                | ✅   |
| F2     | Documentation + squelette de recette Flex          | ⏳   |

F2 : la documentation est livrée et le squelette de recette est dans
`docs/flex-recipe/`, il reste à le soumettre à `symfony/recipes-contrib`.

## Roadmap

| Version  | Contenu                                                                  | Épics   |
| -------- | ------------------------------------------------------------------------ | ------- |
| `0.2.0`  | Parsing conforme aux payloads YouTrust actuels, robustesse HTTP           | A + B   |
| `0.3.0`  | Bascule sur `symfony/webhook`, multi-abonnements                          | C       |
| `0.4.0`  | Événements typés, confort de consommation                                 | D       |
| `1.0.0`  | Tests exhaustifs, CI matricielle, rebranding YouTrust, recette Flex       | E + F   |

---

## Épic A — Alignement du parsing (bloquant)

### A1 — Supporter le payload `metadata` imbriqué (+ rétro-compat format plat)

*Pourquoi* : bug fonctionnel, `YouTrustPayload` exige `event_id` à la racine alors
que la doc v3 place ces champs sous `metadata`.
*Quoi* : détecter la forme du payload et normaliser vers les mêmes propriétés,
sans changer l'API publique du DTO.
*Fini quand* : tests paramétrés sur les deux formes, la fixture
`signature_request.activated` de la doc passe.

### A2 — Tolerant reader

*Pourquoi* : la page `reference/versioning` demande d'ignorer les champs inconnus.
*Quoi* : seuls `event_id`, `event_name` et `data` restent obligatoires ;
`subscription_id`, `subscription_description`, `sandbox` et `event_time`
obtiennent des valeurs par défaut. `event_time` est accepté en `string` comme en
`int`.
*Fini quand* : un payload avec clés inconnues et sans `subscription_description`
est accepté.

### A3 — Exposer les métadonnées de livraison

*Quoi* : ajouter `retryCount` (`X-Yousign-Retry`) et le payload brut au
`YouTrustRemoteEvent`.
*Fini quand* : `getRetryCount()` renvoie la valeur du header, test dédié.

## Épic B — Sécurité et robustesse HTTP

### B1 — Durcir la vérification de signature

*Quoi* : header insensible à la casse, rejet propre d'un format sans préfixe
`sha256=`, ordre des arguments de `hash_equals` corrigé (connu / fourni), hash
toujours calculé sur le corps brut.
*Fini quand* : tests header absent / malformé / mauvaise signature / signature valide.

### B2 — Gestion d'erreurs et journalisation

*Pourquoi* : `catch (Exception)` transforme tout en 500 sans trace, et un JSON
invalide devrait répondre 406, pas 500.
*Quoi* : décodage JSON explicite, mapping JSON invalide → 406, `LoggerInterface`
optionnel (warning signature, error exception).

### B3 — Idempotence sur `event_id`

*Pourquoi* : jusqu'à 8 redélivrances par événement.
*Quoi* : option `idempotency: { enabled, pool, ttl }` s'appuyant sur un
`CacheItemPoolInterface` ; un événement déjà vu répond 2xx sans redispatcher.

### B4 — Garantir une réponse en moins d'une seconde

*Quoi* : documenter le transport Messenger asynchrone pour
`ConsumeRemoteEventMessage`, sous peine de retries en boucle.

### B5 — Allowlist d'IP (optionnel)

*Quoi* : option `allowed_ips` (désactivée par défaut) avec les plages
documentées : `57.130.41.144/28`, `51.38.96.112/28`, `5.39.7.128/28`,
`52.143.162.31`, `51.103.81.166`.

## Épic C — Migration vers `symfony/webhook`

### C1 — `YouTrustRequestParser`

*Quoi* : `AbstractRequestParser` avec `getRequestMatcher()` et `doParse()`,
configuré via `framework.webhook.routing`. Supprime le contrôleur, la route,
l'option `endpoint` et le dispatch manuel sur le bus.

### C2 — Dépréciation du contrôleur maison

*Quoi* : contrôleur et route conservés en `@deprecated` sur `0.3.x`, supprimés en
`1.0.0`, migration documentée dans `UPGRADE.md`.

### C3 — Multi-abonnements

*Pourquoi* : YouTrust autorise 10 à 50 subscriptions selon le plan.
*Quoi* : plusieurs entrées `framework.webhook.routing` avec des secrets distincts.

### C4 — Passage à `AbstractBundle`

*Quoi* : fusionner `Configuration` et `Extension`, services en PHP-DSL, retirer
`webmozart/assert` si la validation native suffit.

## Épic D — Événements typés

### D1 — Enum `YouTrustEvent`

*Quoi* : énumération du catalogue documenté. `tryFrom()` uniquement : un
événement inconnu ne doit jamais faire échouer le parsing.

### D2 — DTO typés par ressource

*Quoi* : objets en lecture seule hydratés depuis `data`, tolérants aux champs
manquants, le tableau brut restant accessible. Commencer par `SignatureRequest`
et `Signer`, qui couvrent la majorité des usages.

### D3 — Dispatch par type d'événement

*Quoi* : consumer abstrait avec méthodes `onSignerDone()` / `onSignatureRequestDone()`,
pour éviter le `match` géant chez chaque intégrateur. Le consumer `RemoteEvent`
brut reste supporté.

## Épic E — Qualité, tests et CI

### E1 — Fixtures JSON des événements

*Quoi* : `tests/Fixtures/Events/<event_name>.json` + test paramétré vérifiant que
chaque fixture se parse et produit le bon `event_name`.

### E2 — Couverture unitaire

*Quoi* : tests dédiés pour le vérificateur de signature, le payload, le converter
et le request parser ; chemins d'erreur couverts.

### E3 — CI matricielle

*Pourquoi* : la CI ne teste qu'un seul PHP 8.3 et une seule résolution de
dépendances.
*Quoi* : matrice PHP × Symfony avec `--prefer-lowest`, `composer validate --strict`,
PHPStan étendu aux tests.

### E4 — Hygiène du dépôt

*Quoi* : dé-versionner `.idea/`, ajouter `LICENSE` (MIT, déclaré mais absent),
`CHANGELOG.md`, `UPGRADE.md`, corriger `package.json`.

## Épic F — Rebranding et documentation

### F1 — Renommage avec alias de rétro-compatibilité

*Quoi* : namespace `Zeggriim\YouTrustWebhookBundle\`, package
`zeggriim/youtrust-webhook-bundle`, anciennes classes en `class_alias`
`@deprecated`. Les headers HTTP restent `x-yousign-signature-256` et
`X-Yousign-Retry` : c'est le protocole.

### F2 — Recette Flex et documentation

*Quoi* : recette `symfony/recipes-contrib`, README réécrit (installation,
configuration, consumer typé, transport async, test local via ngrok /
webhook.site, tableau des événements).

---

## Hors scope (plus tard)

- Client HTTP des endpoints `/webhooks` (list / create / update / delete
  subscription, auth `Bearer`).
- Commande `youtrust:webhook:sync` pour synchroniser les abonnements depuis la
  configuration.
- Bridge des événements vers l'EventDispatcher applicatif ou Mercure.
