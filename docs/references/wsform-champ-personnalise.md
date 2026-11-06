# Créer un type de champ WS Form

Relevé dans WS Form PRO. La documentation publique ne décrit pas ce mécanisme : le code fait foi, et il faut le revérifier à chaque version majeure.

## Sources à lire (lecture seule)

Racine PRO : voir `LOCAL.md` (fichier local non versionné).

| Fichier | À regarder |
|---|---|
| `includes/class-ws-form-config.php` | Filtre `wsf_config_field_types`, définition de `googlemap`, clés `google_map_*` et `google_address_*` |
| `public/js/ws-form-public-google-map.js` | Comportement de la carte et format de la valeur |
| `public/js/ws-form-public-google-address.js` | Autocomplétion, mapping des composants, liaison à une carte |
| `includes/core/class-ws-form-submit.php` | Traitement de la valeur à l'enregistrement (`case 'googlemap'`) |
| `includes/third-party/acf/class-ws-form-acf.php`, `includes/third-party/meta-box/class-ws-form-meta-box.php` | Correspondance avec les champs personnalisés |

## Accroches

```php
add_filter( 'wsf_config_field_types', array( __CLASS__, 'register_field_type' ) );
add_filter( 'wsf_config_meta_keys', array( __CLASS__, 'register_meta_keys' ), 10, 2 );
add_action( 'wsf_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 10, 1 );
add_action( 'wsf_enqueue_visual_builder', array( __CLASS__, 'enqueue_assets' ) );
```

## Structure d'un type

```php
$field_types[ $group ] = array( 'label' => '…', 'types' => array() );

$field_types[ $group ]['types'][ $type ] = array(
	'label'                 => '…',
	'label_default'         => '…',
	'submit_save'           => true,
	'submit_edit'           => false,
	'submit_array'          => false,
	'calc_in'               => false,
	'calc_out'              => false,
	'text_in'               => false,
	'text_out'              => true,
	'value_out'             => true,
	'mappable'              => true,
	'has_required'          => true,
	'keyword'               => '…',
	'mask_field'            => '#pre_label#pre_help<div …><input type="hidden" id="#id" name="#name"#attributes value="#value" /></div>#post_label#invalid_feedback#post_help',
	'mask_field_label'      => '<label id="#label_id" for="#id"#attributes>#label</label>',
	'mask_field_attributes' => array( 'class', 'required', 'disabled', 'aria_describedby', 'aria_labelledby', 'aria_label', 'custom_attributes' ),
	'fieldsets'             => array(
		'basic' => array( 'label' => '…', 'meta_keys' => array( '…' ) ),
	),
);
```

Le type `googlemap` ajoute `conditional`, `events`, `trigger` et `progress` : repars de sa définition pour la logique conditionnelle. Le rendu front passe par les masques en JS, pas par du PHP.

## Initialisation en JS

Le champ écoute `wsf-rendered` sur `document`, avec un balayage de secours pour les chargements tardifs. Les sections répétables déclenchent `wsf-section-repeatable` : une carte ajoutée à la volée doit aussi s'initialiser.

## Format de valeur de `googlemap`

Une chaîne JSON dans un `input` masqué :

```json
{ "lat": 48.85, "lng": 2.35, "zoom": 14, "map_type_id": "roadmap" }
```

Après un choix d'adresse, Google y ajoute notamment `place_id` et `address`. Le cœur accepte aussi l'ancien format `lat,lng`.

## Ce qu'un champ tiers n'a pas d'office

Le type `googlemap` est traité à part dans le cœur :

- `includes/core/class-ws-form-submit.php`, `class-ws-form-submit-export.php`, `class-ws-form-form.php`
- `includes/class-ws-form-common.php`, `includes/config/class-ws-form-config-public.php`
- `includes/third-party/acf/`, `includes/third-party/meta-box/`
- `public/class-ws-form-public.php`, `admin/class-ws-form-wp-list-table-submit.php`

Il faut donc vérifier, pour le nouveau champ : l'affichage dans la liste des soumissions, les e-mails (`#email_submission`), l'export CSV, `#field(ID)`, et le mapping vers ACF, Meta Box et Post Management. Cherche un filtre avant de contourner.

## Vérifié sur 1.12.19 (PRO et LITE, 2026-11-06)

| Sortie | Comportement d'un type tiers | Solution |
|---|---|---|
| Liste des soumissions | JSON brut, déjà passé dans `esc_html`, lignes répétées jointes par un vrai `<br />` | Filtre `wsf_table_submit_field_type_list` : remplacer le JSON sans ré-échapper la chaîne |
| Export CSV | JSON brut, lignes répétées jointes par une virgule | Filtre `wsf_submit_field_type_csv`, avec `;` entre les paires de coordonnées |
| E-mail (`#email_submission`, `#field(ID)`) | JSON brut, aucun filtre sur la valeur | Filtre `wsf_action_email_message`, sur le message composé |
| `#field(ID)` hors e-mail | JSON brut | Aucun filtre trouvé : limite à documenter |
| Détail d'une soumission (barre latérale) | JSON brut, rendu par le JS admin | Aucun filtre trouvé : limite à documenter |

À savoir :

- **Chargement à la demande :** `wsf_form_pre_process_field` reçoit chaque champ d'un formulaire rendu. C'est le bon crochet pour savoir si le formulaire contient le champ.
- **Touche Entrée :** le cœur l'annule sur tous les `:input` du formulaire, par délégation sur `keydown`. Un bouton ajouté au champ doit arrêter la propagation de cet événement.
- **Types ACF mappables :** `WS_Form_ACF::acf_field_mappable()` est une liste fermée, et aucun filtre n'a été trouvé. Un champ ACF tiers n'est donc pas proposé au mapping. Vérifié avec ACF OpenStreetMap Field et ACF: Leaflet Map Field. Un bloc ACF n'est alimenté que s'il est en `usePostMeta`.
- **Style :** pour suivre le Styler, réutilise les classes du cœur (`wsf-button`, `wsf-help`) ou ses variables (`--wsf-field-color`, `--wsf-form-font-family`), avec une valeur de repli. Le cœur ne charge le CSS des boutons que pour ses propres types de champ : force-le avec le filtre `wsf_enqueue_css_button`. Vérifié sur PRO et LITE avec un style publié.
- **Inputs tiers :** tout `input` nommé dans le formulaire part dans le POST. C'est pour ça que la carte n'utilise pas le sélecteur de couches de Leaflet, et ses radios.
- **Sections répétables :** elles sont absentes de LITE. Le suffixe d'une ligne vient de `get_section_repeatable_suffix()`, et une ligne ajoutée déclenche `wsf-section-repeatable`.
- **Écrire dans un autre champ :** passe par `field_value_set( $wrapper, $field, value )`, qui gère les listes et déclenche les conditions.
- **Répéteur de réglages :** une clé de type `select` dotée de `'key' => 'ws_form_field'` range sa valeur sous cette clé dans chaque ligne.
- **Création par code :** ne passe pas de `label` à `WS_Form_Field::db_create()`. Le cœur saute alors le chargement de la config, et le champ perd ses valeurs par défaut.

## Mapping vers les contenus (vérifié le 2026-11-06)

Testé avec WS Form Post 1.6.13, ACF PRO 6.8.11 et Meta Box 5.15.4, dans les deux sens : création d'un contenu et préremplissage du formulaire.

| Cible | Sans correctif | Solution |
|---|---|---|
| Méta simple | Chaîne JSON complète | Rien à faire |
| ACF `google_map` | `update_value` décode le JSON | Rien à faire à l'écriture |
| Meta Box `map` | **`0,0,5` écrit en silence** : la passerelle attend un tableau | `WSFIGN_Post_Mapping` présente un tableau pendant le filtre `wsf_action_post_field_mapping`, puis restaure la chaîne pour le mapping de méta simple, qui passe juste après |
| Préremplissage ACF et Meta Box | Un tableau, que le cœur n'encode qu'en `googlemap` | `wsf_action_post_get`, en priorité 11, encode en JSON |

## Geo Query

Le plugin lit `coordonnees_gps` en JSON ou en tableau, avec les clés `lat` et `lng`.

- **Méta simple `coordonnees_gps`, sans champ ACF de ce nom :** fonctionne de bout en bout, car `wp_insert_post` écrit `meta_input` avant `save_post`.
- **Champ ACF :** la valeur arrive bien, mais la table miroir de Geo Query reste vide. Post Management écrit le champ après le premier `save_post`, que Geo Query marque déjà comme synchronisé. Le correctif est à faire dans Geo Query.
- **Ne mappe pas une méta simple sur le nom d'un champ ACF existant :** ACF lirait la chaîne JSON avec `wp_parse_args`.

## Documentation publique

- https://wsform.com/knowledgebase/google-map/
- https://wsform.com/knowledgebase/google-address/
- https://wsform.com/knowledgebase/conditional-logic/
