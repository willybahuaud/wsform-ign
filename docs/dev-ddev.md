# Environnement de test partagé : `wsform-lab`

Un seul site ddev sert aux trois plugins (`wsform-ign`, `wsform-monetico`, `wsform-to-externaldb`). Commandes exécutées telles quelles le 2026-11-06, avec ddev 1.25.1 (WordPress 7.1.4, PHP 8.4).

## Montage

```bash
mkdir -p ~/ddev/wsform-lab && cd ~/ddev/wsform-lab
ddev config --project-type=wordpress --project-name=wsform-lab --docroot=.
ddev start
ddev wp core download --locale=fr_FR
ddev wp core install --url=https://wsform-lab.ddev.site --title="WS Form Lab" \
  --admin_user=admin --admin_password=admin --admin_email=dev@example.test
ddev wp rewrite structure '/%postname%/' --hard
```

Sans la dernière commande, une URL comme `/test-carte/` renvoie l'accueil, sans erreur.

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

**Attention :** ce montage expose le dépôt lui-même. Une vraie mise à jour du plugin dans ce dossier effacerait le dépôt, `.git` compris. Pour tester l'auto-update :

1. Commite ton travail.
2. Retire la ligne `wsform-ign` du montage et relance ddev.
3. Vérifie que `ddev exec "mount | grep -c 'plugins/wsform-ign '"` affiche `0`.
4. Dézippe l'archive de `bin/build-release.sh` dans `wp-content/plugins/` et fais tes essais.
5. Supprime cette copie, remets la ligne et relance ddev.

## WS Form

- **PRO :** Willy le télécharge depuis son compte (`https://wsform.com/my-account/downloads/`) et le dézippe dans `wp-content/plugins/`. Prends la dernière version.
- **LITE :** `ddev wp plugin install ws-form` pour les tests de compatibilité. N'active jamais LITE et PRO ensemble.
- **Licence :** ne l'écris dans aucun fichier versionné, et demande-la à Willy si l'activation est nécessaire.
- **WP-CLI :** les méthodes de WS Form vérifient les droits. Passe `--user=admin` à `ddev wp`, sinon elles lèvent une erreur fatale.
- **E-mails :** ddev les capture dans Mailpit, sur `https://wsform-lab.ddev.site:8026`.
- **Code PRO :** il ne doit jamais entrer dans un dépôt, ni tel quel ni par extraits longs.

Une copie de lecture, plus ancienne que la version courante, est indiquée dans `LOCAL.md`. Elle se trouve sur le site local d'un client : lecture seule, ne l'utilise pas pour tester, ne la modifie pas et ne cite pas ce client.

## Règles

- Aucun secret dans le dépôt : identifiants bancaires, mots de passe de base, clés. Utilise un fichier local ignoré par git.
- Le mot de passe `admin` ci-dessus ne vaut que pour ce site local.
