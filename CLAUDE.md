# Carte IGN pour WS Form

Plugin WordPress gratuit : champs de carte IGN et d'adresse BAN pour WS Form, sans Google ni clé.

**Commence par `docs/BRIEF.md`.** Il contient le périmètre, les décisions, les risques et les critères d'acceptation.

| Fichier | Contenu |
|---|---|
| `docs/BRIEF.md` | Le travail à faire |
| `docs/references/wsform-champ-personnalise.md` | Comment créer un type de champ WS Form |
| `docs/references/ign-geoplateforme.md` | Services IGN testés, limites, licence |
| `docs/references/autoupdate-github.md` | Mise à jour depuis les releases GitHub |
| `docs/dev-ddev.md` | Environnement de test `wsform-lab` |
| `LOCAL.md` | Chemins locaux de lecture, non versionné |

## Façon de travailler

- Tutoie Willy, reste direct et concis, en français.
- Commit ou push seulement sur demande. Pas de push sans accord explicite.
- Ne déclare terminé que ce que tu as vérifié, avec la preuve.

## Code

- Fichiers courts, une responsabilité par fichier. Une fonction de plus de 50 lignes doit te faire réfléchir.
- Pas de fonctions anonymes, en PHP comme en JS.
- PHPDoc et JSDoc partout. Commente le pourquoi, pas le quoi.
- Noms explicites, jamais `$data`, `$temp` ou `$x`.
- Conventions WordPress, PHPCS WordPress.
- Pas d'abstraction pour trois lignes.

## Commits

Format `type(scope): message`, en français sans accents. Types : feat, fix, refactor, perf, docs, test, chore. Propose aussi la version anglaise à Willy.

## Interdits

- Versionner WS Form PRO, ou un extrait long de son code.
- Versionner un secret ou une licence.
- Citer un client de Willy, dans le code comme dans la doc. Les chemins de `LOCAL.md` sont des sources de lecture : ne les recopie dans aucun fichier versionné.
- Modifier un site local de client.
- Ajouter un traceur, une télémétrie ou un CDN.
- Utiliser des tirets cadratins dans les textes publics.
