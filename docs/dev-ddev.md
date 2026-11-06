# Environnement de test partagé : `wsform-lab`

Un seul site ddev sert aux trois plugins (`wsform-ign`, `wsform-monetico`, `wsform-to-externaldb`). Ce site n'est pas encore monté et ces commandes n'ont pas été exécutées : ajuste-les si besoin, puis corrige ce fichier.

## Montage

```bash
mkdir -p ~/ddev/wsform-lab && cd ~/ddev/wsform-lab
ddev config --project-type=wordpress --project-name=wsform-lab --docroot=.
ddev start
ddev wp core download --locale=fr_FR
ddev wp core install --url=https://wsform-lab.ddev.site --title="WS Form Lab" \
  --admin_user=admin --admin_password=admin --admin_email=dev@example.test
```

Ddev ne suit pas les liens symboliques vers l'extérieur du projet. Monte les plugins dans `.ddev/docker-compose.plugins.yaml` :

```yaml
services:
  web:
    volumes:
      - ~/GitHub/wsform-ign:/var/www/html/wp-content/plugins/wsform-ign
      - ~/GitHub/wsform-monetico:/var/www/html/wp-content/plugins/wsform-monetico
      - ~/GitHub/wsform-to-externaldb:/var/www/html/wp-content/plugins/wsform-to-externaldb
```

Puis `ddev restart`.

## WS Form

- **PRO :** Willy le télécharge depuis son compte (`https://wsform.com/my-account/downloads/`) et le dézippe dans `wp-content/plugins/`. Prends la dernière version.
- **LITE :** `ddev wp plugin install ws-form` pour les tests de compatibilité. N'active jamais LITE et PRO ensemble.
- **Licence :** ne l'écris dans aucun fichier versionné, et demande-la à Willy si l'activation est nécessaire.
- **Code PRO :** il ne doit jamais entrer dans un dépôt, ni tel quel ni par extraits longs.

Une copie de lecture, plus ancienne que la version courante, est indiquée dans `LOCAL.md`. Elle se trouve sur le site local d'un client : lecture seule, ne l'utilise pas pour tester, ne la modifie pas et ne cite pas ce client.

## Règles

- Aucun secret dans le dépôt : identifiants bancaires, mots de passe de base, clés. Utilise un fichier local ignoré par git.
- Le mot de passe `admin` ci-dessus ne vaut que pour ce site local.
