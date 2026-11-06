# Mise à jour automatique depuis GitHub

Willy veut que ce plugin se mette à jour depuis les releases GitHub, sur le modèle de `wboard-connector`.

## Modèle

- Dépôt public : https://github.com/willybahuaud/wboard-connector
- Classe : `includes/class-updater.php` (370 lignes). Sur la machine de Willy : `~/GitHub/wboard/plugin/wboard-connector/includes/class-updater.php`
- Instanciée dans le fichier principal, puis `register_hooks()`
- Releases : tag `vX.Y.Z` et archive `wboard-connector-X.Y.Z.zip` jointe à la release

## Fonctionnement à reprendre

| Filtre | Rôle |
|---|---|
| `pre_set_site_transient_update_plugins` | Compare la version du plugin au tag de la dernière release et ajoute l'entrée de mise à jour |
| `plugins_api` | Remplit la fenêtre « Voir les détails », avec les notes de release |
| `upgrader_source_selection` | Corrige le nom du dossier extrait |

La version vient de `GET https://api.github.com/repos/{owner}/{repo}/releases/latest`, avec un transient de 12 h et un cache en mémoire.

## Limite bloquante : le dépôt privé

**L'updater du modèle appelle l'API sans authentification. Sur un dépôt privé, GitHub répond 404 et le plugin ne propose jamais de mise à jour, sans aucune erreur.** Le modèle fonctionne parce que son dépôt est public.

Décisions :

- Ne mets aucun jeton GitHub dans le plugin, car il serait distribué à tout le monde.
- Le mécanisme ne fonctionnera donc qu'une fois le dépôt public. Dis-le clairement à Willy.
- Teste avant cela en simulant les réponses de GitHub avec le filtre `pre_http_request`, puis fais un essai réel sur un dépôt public de test, avec l'accord de Willy.

## Ce qu'il faut améliorer par rapport au modèle

1. **Rendre la classe paramétrable :** propriétaire, dépôt, fichier principal, version et nom affiché en paramètres du constructeur, sans valeurs en dur dans la logique.
2. **Une classe préfixée par plugin :** `WSFIGN_Updater`, par exemple. Ne partage pas une classe de même nom entre plusieurs plugins, car deux versions différentes entreraient en conflit sur un même site.
3. **Archive jointe obligatoire :** retire le repli sur `zipball_url` du modèle. Cette archive contient tout le dépôt (`docs/`, `tests/`, etc.) et ne doit pas partir chez les utilisateurs.
4. **Cache des échecs :** le modèle ne garde pas en cache un échec ou un 404, et l'API anonyme est limitée à 60 requêtes par heure et par IP. Sur un hébergement mutualisé, c'est vite atteint. Garde les échecs en cache environ 1 h.
5. **Interrupteur « Activer les mises à jour automatiques » :** vérifie qu'il s'affiche aussi quand aucune mise à jour n'attend, ce qui demande une entrée dans `no_update`.
6. **En-tête `Update URI` :** ajoute `Update URI: https://github.com/willybahuaud/<dépôt>`, pour qu'un plugin de même slug sur WordPress.org ne puisse jamais le remplacer.
7. **Compatibilité :** lis `Requires at least`, `Requires PHP` et `Tested up to` depuis les en-têtes ou un manifeste de la release au lieu de les écrire en dur.
8. **Comparaison stricte :** ne propose la mise à jour que si l'archive est présente et le tag lisible.

## Publier une version

Ajoute un workflow GitHub Actions qui, sur un tag `v*` :

1. vérifie que le tag est égal à l'en-tête `Version` du plugin
2. construit les dépendances de production
3. crée `<slug>-X.Y.Z.zip`, avec un dossier racine `<slug>/`, en respectant `.distignore`
4. publie la release avec cette archive et ses notes

## Incompatible avec WordPress.org

Les règles du répertoire WordPress.org interdisent à un plugin de se mettre à jour depuis une autre source. Vérifie la règle en vigueur. Si Willy veut plus tard publier sur WordPress.org, la version du répertoire devra retirer ce mécanisme.

## Critères d'acceptation

- [ ] Avec des réponses simulées, une version plus récente apparaît dans l'admin et la mise à jour s'installe dans le bon dossier.
- [ ] Le plugin reste actif après la mise à jour, sans perte de réglages.
- [ ] Un 404, un 403 de limite ou un délai dépassé ne produisent ni erreur visible ni ralentissement de l'admin.
- [ ] L'archive installée ne contient ni `docs/` ni `tests/`.
- [ ] Une release sans archive ne propose pas de mise à jour.
- [ ] Aucun jeton n'est présent dans le code.
- [ ] Le workflow refuse un tag différent de la version du plugin.
