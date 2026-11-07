# Brief : carte IGN et adresses BAN pour WS Form

Dépôt public : `willybahuaud/wsform-ign`. Les critères cochés ont été vérifiés sur WS Form PRO et LITE 1.12.19.

## Pourquoi

Les champs Google de WS Form exigent un projet Google Cloud avec facturation et une clé d'API. Ce plugin gratuit offre la même chose avec les services publics français, sans clé ni compte.

## Périmètre de la première version

### Champ « Carte IGN »

- Fond Plan IGN par défaut, photos aériennes en option
- Latitude, longitude, zoom et hauteur par défaut, comme le champ Google
- Clic sur la carte qui pose le marqueur, marqueur déplaçable
- Réinitialisation du marqueur
- Marqueur obligatoire possible
- Option de géolocalisation du visiteur, sur demande de sa part : placer le point sur lui, ou seulement centrer la carte
- Hiérarchie des actions : on pose le point sur la carte. Sous la carte, seul le bouton de géolocalisation est visible, et « Placer le point au centre » n'apparaît qu'au focus clavier
- Icône et titre de marqueur personnalisables

### Champ « Adresse BAN »

- Autocomplétion sur un champ texte, avec navigation au clavier
- Mapping des composants vers d'autres champs : numéro et rue, code postal, ville, code INSEE, département, latitude, longitude
- Liaison optionnelle à un champ Carte IGN : la carte se centre sur l'adresse choisie
- Géocodage inverse optionnel après un clic sur la carte
- Saisie libre toujours possible si l'adresse n'est pas trouvée, ou si elle est à l'étranger

### Hors périmètre

- Itinéraires
- Adresses hors de France
- Recherche par IP
- Coordonnées saisies à la main
- Lien avec le SIRET (piste pour plus tard)

## Décisions déjà prises

- **Gratuit**, sans version payante, sans publicité dans l'admin et sans télémétrie.
- **Mêmes réglages que les champs Google**, pour que le passage soit évident. Voir `docs/references/wsform-champ-personnalise.md`.
- **Même format de valeur :** JSON avec `lat`, `lng`, `zoom`, plus `address` quand on en a une. Remplace `map_type_id` par la couche IGN.
- **Leaflet 1.9.4 embarqué dans le plugin**, jamais chargé depuis un CDN.
- **Chargement à la demande :** Leaflet et les scripts ne se chargent que si le formulaire contient l'un de ces champs.
- **Appels depuis le navigateur** vers `data.geopf.fr`, sans clé. Voir `docs/references/ign-geoplateforme.md`.
- **Slug et préfixe :** dossier `wsform-ign`, fichier principal `wsform-ign.php`, text domain `wsform-ign`, types `wsfign_map` et `wsfign_address`, préfixe de réglages `wsfign_`.
- **Nom affiché :** « Carte IGN pour WS Form », car WS Form est la marque d'un tiers et ne doit pas ouvrir le nom.

## Vocabulaire honnête

- Écris « sans Google, sans clé ». N'écris jamais « aucun tiers » : le navigateur du visiteur contacte les serveurs de l'IGN et leur transmet son adresse IP.
- N'écris pas « hébergé en France » sans source.
- La documentation doit dire que l'éditeur du site doit mentionner ce service dans sa politique de confidentialité.

## Mise à jour automatique

Le plugin se met à jour depuis les releases GitHub. Tout est dans `docs/references/autoupdate-github.md`. Aucun jeton ne doit être ajouté au plugin.

## Critères d'acceptation

- [x] Sur `wsform-lab`, avec la dernière version de WS Form PRO, un formulaire contenant les deux champs s'affiche sans erreur en console et sans clé.
- [x] Le choix d'une adresse remplit les champs mappés et centre la carte.
- [x] Un clic pose le marqueur, et sa valeur est bien enregistrée dans la soumission.
- [x] La valeur est lisible dans la liste des soumissions, l'e-mail et l'export CSV. Limites documentées : `#field(ID)` hors e-mail et détail d'une soumission.
- [x] La logique conditionnelle fonctionne au minimum en visibilité, obligatoire et validation.
- [x] Le champ fonctionne dans une section répétable et dans un onglet d'abord masqué.
- [x] L'autocomplétion s'utilise entièrement au clavier, avec un balisage de type combobox.
- [x] La carte reste utilisable sans la souris, ou le formulaire offre une autre saisie.
- [x] Si l'API IGN est coupée ou renvoie 429, le formulaire reste envoyable et un message clair s'affiche.
- [x] Une page sans ces champs ne charge ni Leaflet ni les scripts du plugin.
- [x] PHPCS (WordPress) passe sans erreur, et la vérification avec Plugin Check est faite.
- [x] Le README et le `readme.txt` existent, les limites comprises.
- [x] La compatibilité avec LITE est testée et documentée.

Vérifie en vrai navigateur avec Playwright et donne la preuve de chaque critère. Ne coche pas un critère non vérifié.

## Lien avec Geo Query

Geo Query est un autre plugin de Willy pour les requêtes géolocalisées. Le scénario visé : un formulaire crée un contenu avec Post Management, les coordonnées vont en méta, et Geo Query les interroge. Voir `docs/references/wsform-champ-personnalise.md`.
