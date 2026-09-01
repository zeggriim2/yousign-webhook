# Publier la 1.0.0

Le package change de nom (`zeggriim/yousign-webhook-bundle` →
`zeggriim/youtrust-webhook-bundle`). Packagist lie une entrée à une URL de
dépôt et **refuse une mise à jour dont le `name` du `composer.json` a changé** :
sans la procédure ci-dessous, l'ancienne entrée cesserait simplement de recevoir
les nouvelles versions, sans avertir personne.

Rappel de l'existant : `zeggriim/yousign-webhook-bundle`, dernier tag `v0.1.2`,
~1 200 téléchargements cumulés. Il y a donc des installations réelles à ne pas
laisser dans le noir.

## Ordre des opérations

1. **Fusionner** la branche de travail dans `main`.

2. **Renommer le dépôt GitHub** : `yousign-webhook` → `youtrust-webhook`.
   GitHub conserve une redirection depuis l'ancienne URL, donc les `git remote`
   existants continuent de fonctionner.

3. **Soumettre le nouveau package** sur <https://packagist.org/packages/submit>
   avec la nouvelle URL `https://github.com/zeggriim/youtrust-webhook`.
   Vérifier que le hook GitHub est bien actif (Settings → Webhooks).

4. **Taguer la 1.0.0** :

   ```bash
   git tag -a v1.0.0 -m "YouTrust alignment"
   git push origin main --tags
   ```

   Pas de `0.2` ni de `0.3` : la ligne `0.x` s'arrête à `v0.1.2`.

5. **Marquer l'ancien package comme abandonné** : sur la page Packagist de
   `zeggriim/yousign-webhook-bundle`, « Abandon package » en indiquant
   `zeggriim/youtrust-webhook-bundle` comme remplaçant. Composer affichera alors
   un avertissement explicite aux utilisateurs restants, avec le nom à requérir.

6. **Publier la release GitHub** en pointant vers `UPGRADE.md` pour la migration.

## Vérifications avant tag

```bash
make cs        # php-cs-fixer, dry-run
make phpstan   # niveau max, src + tests
make test      # PHPUnit
composer validate --strict
```

Puis, sur une application Symfony neuve :

```bash
composer config repositories.local path ../youtrust-webhook
composer require zeggriim/youtrust-webhook-bundle:@dev
```

et rejouer une fixture de `tests/Fixtures/Events/` sur `/webhook/yousign`
(commande `curl` documentée dans le README).

## Après la 1.0.0

- Soumettre la recette Flex (`docs/flex-recipe/`) à
  [`symfony/recipes-contrib`](https://github.com/symfony/recipes-contrib).
- Prévoir la `2.0` pour supprimer la couche de compatibilité : `src/Legacy/`,
  l'option `legacy_controller`, le contrôleur et `Resources/config/routes.yaml`.
