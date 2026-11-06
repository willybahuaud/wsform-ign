# Mise à jour automatique depuis GitHub

Le plugin se met à jour depuis les releases d'un dépôt public, sans jeton. Un jeton serait distribué à tout le monde, et l'API répond 404 sans erreur sur un dépôt privé : le mécanisme ne marche donc que sur un dépôt public.

## Fonctionnement

| Filtre | Rôle |
|---|---|
| `update_plugins_github.com` | Déclenché par l'en-tête `Update URI`. Compare la version à la dernière release. WordPress range la réponse dans `response` ou `no_update`, ce qui affiche l'interrupteur des mises à jour automatiques même sans mise à jour en attente |
| `plugins_api` | Remplit la fenêtre « Voir les détails » |
| `upgrader_source_selection` | Garantit le nom du dossier installé |

Tout plugin dont l'`Update URI` vise github.com passe par le premier filtre : il faut comparer le basename.

La version vient de `GET /repos/{owner}/{repo}/releases/latest`. La compatibilité vient d'un manifeste `<slug>-X.Y.Z.json` joint à la release, car les en-têtes du plugin installé décrivent l'ancienne version.

## Choix

- **Classe préfixée :** `WSFIGN_Updater`. Une classe au nom générique entrerait en conflit avec un autre plugin.
- **Archive jointe obligatoire :** sans `<slug>-X.Y.Z.zip`, pas de mise à jour. Le zipball de GitHub contient tout le dépôt.
- **Hôte vérifié :** l'archive doit venir de `github.com` en HTTPS. Les champs de la réponse sont lus sans présumer de leur type.
- **Cache :** 12 h après un succès, 1 h après un échec. L'API anonyme est limitée à 60 requêtes par heure et par IP, vite atteintes sur un mutualisé.

## Publier une version

Sur un tag `v*`, le workflow vérifie que le tag égale la version du plugin, construit l'archive en respectant `.distignore`, contrôle son contenu et publie la release. Il refuse un tag qui ne correspond pas.

Tester une vraie mise à jour : voir `docs/dev-ddev.md`, car le lab monte le dépôt lui-même.

## Fenêtre « Voir les détails »

- **Version testée :** la fenêtre compare les versions telles quelles, et préviendrait à tort pour `Tested up to: 7.1` sur WordPress 7.1.4. `WSFIGN_Updater` présente donc la version courante si elle appartient à la branche déclarée.
- **Notes de version :** GitHub les génère en Markdown. `WSFIGN_Release_Notes` les convertit après échappement, sans `make_clickable`, qui couperait les liens de comparaison.

## WordPress.org

Ses règles interdisent à un plugin de se mettre à jour depuis une autre source. Une version pour le répertoire devrait retirer ce mécanisme.

## Vérifié

- Vraie mise à jour de v0.1.0 vers v0.1.1, puis de v0.1.1 vers v0.1.2 : bon dossier, plugin actif, réglages conservés.
- Un 404, un 403 de limite ou un délai dépassé ne produisent ni erreur visible ni ralentissement.
- Une release sans archive ne propose pas de mise à jour.
- Des réponses GitHub malformées ou une archive hors de github.com sont ignorées sans erreur.
