# Carte IGN pour WS Form

Les champs Google de WS Form demandent un projet Google Cloud, de la facturation et une clé d'API. Ce plugin gratuit offre une carte et une autocomplétion d'adresse avec les services publics français, sans Google et sans clé.

Il fonctionne avec WS Form LITE et PRO.

## Ce qu'il ajoute

**Carte IGN**

- Fond Plan IGN, photos aériennes en option
- Marqueur posé au clic, déplaçable, réinitialisable, obligatoire si besoin
- Bouton de pose au clavier
- Icône et titre du marqueur personnalisables
- Bouton optionnel pour centrer sur le visiteur : le navigateur ne demande sa position qu'après ce clic

**Adresse BAN**

- Autocomplétion utilisable entièrement au clavier
- Remplissage d'autres champs : numéro et rue, code postal, ville, code INSEE, département, latitude, longitude
- Liaison optionnelle à une carte, et géocodage inverse quand on pose le marqueur
- Saisie libre toujours possible, par exemple pour une adresse à l'étranger

Si les services IGN sont coupés, un message s'affiche et le formulaire reste envoyable.

Leaflet et les scripts du plugin ne se chargent que si le formulaire contient l'un de ces champs.

## Limites

- **Adresses :** seules les adresses françaises sont suggérées.
- **Sections répétables :** LITE n'en a pas, il faut donc PRO pour y placer ces champs.
- **Format de la valeur :** la carte enregistre un JSON. Il s'affiche en `latitude,longitude` dans la liste des soumissions, l'export CSV et les e-mails. Il reste en JSON dans `#field(ID)` hors e-mail et dans le détail d'une soumission, car WS Form n'y offre aucun filtre.
- **Mapping :** le mapping vers ACF, Meta Box et Post Management n'a pas encore été testé.

## Confidentialité

Le plugin n'ajoute ni traceur ni télémétrie. Par contre, le navigateur du visiteur contacte directement les serveurs de l'IGN (`data.geopf.fr`) et leur transmet son adresse IP, ainsi que le texte saisi dans le champ adresse.

Si tu utilises ce plugin, mentionne ce service dans ta politique de confidentialité.

## Mises à jour

Le plugin se met à jour depuis les releases GitHub, pas depuis WordPress.org. Il ne peut donc pas être publié tel quel sur le répertoire WordPress.org, dont les règles interdisent une autre source de mise à jour.

## Développer

```bash
composer install
composer lint
bin/build-release.sh
```

L'environnement de test est décrit dans `docs/dev-ddev.md`. Pour publier, passe la version dans `wsform-ign.php` (en-tête et constante) et dans `readme.txt`, puis pousse un tag `vX.Y.Z` : le workflow refuse un tag qui ne correspond pas.

## Licence

GPL-2.0-or-later. Leaflet 1.9.4 est embarqué sous licence BSD-2-Clause. Ce plugin n'est affilié ni à l'IGN ni à WS Form.
