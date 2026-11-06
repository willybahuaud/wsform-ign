# Brief : carte IGN et adresses BAN pour WS Form

Rédigé le 2026-11-06. Dépôt GitHub privé : `willybahuaud/wsform-ign`. Aucun code n'existe encore dans ce dépôt, et aucune de ces hypothèses n'a été essayée dans WS Form.

## Pourquoi

Les champs Google de WS Form exigent un projet Google Cloud avec facturation et une clé d'API. Ce plugin gratuit offre la même chose avec les services publics français, sans clé ni compte.

Willy ne cherche pas à le vendre. Le plugin sert sa notoriété et fait découvrir WS Form en France. Il doit donc être exemplaire et simple à essayer, et il sera raconté dans un article de wabeo.fr prévu en février 2027.

## Périmètre de la première version

### Champ « Carte IGN »

- Fond Plan IGN par défaut, photos aériennes en option
- Latitude, longitude, zoom et hauteur par défaut, comme le champ Google
- Clic sur la carte qui pose le marqueur, marqueur déplaçable
- Réinitialisation du marqueur
- Marqueur obligatoire possible
- Option de centrage sur le navigateur du visiteur, sur demande de sa part
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

Le plugin se met à jour depuis les releases GitHub, sur le modèle de `wboard-connector`. Tout est dans `docs/references/autoupdate-github.md`, critères d'acceptation compris.

**Le mécanisme ne peut pas fonctionner tant que le dépôt est privé**, et aucun jeton ne doit être ajouté au plugin. Teste-le avec des réponses simulées.

## Risques à lever en premier, dans cet ordre

1. **Type de champ tiers :** le cœur traite `googlemap` à part dans une dizaine de fichiers. Vérifie la liste des soumissions, les e-mails, l'export CSV, `#field(ID)` et le mapping ACF, Meta Box et Post Management.
2. **WS Form LITE :** le filtre semble partagé, mais rien n'a été essayé. Si le champ marche sur LITE, tout le monde peut l'essayer sans licence. Dis clairement ce qui marche ou non sur chaque édition.
3. **Éditeur visuel :** le rendu et les réglages du champ dans le constructeur.
4. **Sections répétables :** une carte ajoutée à la volée.
5. **Onglets masqués :** Leaflet doit recalculer sa taille (`invalidateSize`) quand un onglet ou une section s'affiche.

## Critères d'acceptation

- [ ] Sur `wsform-lab`, avec la dernière version de WS Form PRO, un formulaire contenant les deux champs s'affiche sans erreur en console et sans clé.
- [ ] Le choix d'une adresse remplit les champs mappés et centre la carte.
- [ ] Un clic pose le marqueur, et sa valeur est bien enregistrée dans la soumission.
- [ ] La valeur est lisible dans la liste des soumissions, l'e-mail et l'export CSV.
- [ ] La logique conditionnelle fonctionne au minimum en visibilité, obligatoire et validation.
- [ ] Le champ fonctionne dans une section répétable et dans un onglet d'abord masqué.
- [ ] L'autocomplétion s'utilise entièrement au clavier, avec un balisage de type combobox.
- [ ] La carte reste utilisable sans la souris, ou le formulaire offre une autre saisie.
- [ ] Si l'API IGN est coupée ou renvoie 429, le formulaire reste envoyable et un message clair s'affiche.
- [ ] Une page sans ces champs ne charge ni Leaflet ni les scripts du plugin.
- [ ] PHPCS (WordPress) passe sans erreur, et la vérification avec Plugin Check est faite.
- [ ] Le README et le `readme.txt` existent, les limites comprises.
- [ ] La compatibilité avec LITE est testée et documentée.

Vérifie en vrai navigateur avec Playwright et donne la preuve de chaque critère. Ne coche pas un critère non vérifié.

## Lien avec Geo Query

Geo Query est un autre plugin de Willy pour les requêtes géolocalisées. Une copie est indiquée dans `LOCAL.md`.

Le scénario visé : un formulaire crée un contenu avec Post Management, les coordonnées vont en méta, et Geo Query interroge ces métas. Regarde le format qu'il attend et rends ce scénario possible. Ce n'est pas un critère bloquant.

## À demander à Willy

1. Le passage du dépôt en public, indispensable pour que les utilisateurs reçoivent les mises à jour.
2. Les chaînes source : français avec une traduction anglaise, ou anglais avec une traduction française. Par défaut, anglais et `fr_FR` fourni, comme l'attend WordPress.org.
3. La licence WS Form PRO pour l'environnement de test.

## Pour commencer

1. Lis `CLAUDE.md`, `LOCAL.md`, puis les deux fichiers de `docs/references/`.
2. Monte `wsform-lab` avec `docs/dev-ddev.md`, et corrige ce fichier s'il est faux.
3. Fais un premier prototype du seul champ Carte IGN pour lever les risques 1 à 3, et montre le résultat à Willy.
4. Ajoute ensuite le champ Adresse BAN.
