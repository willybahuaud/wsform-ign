# Carte IGN pour WS Form

Les champs Google de WS Form demandent un projet Google Cloud, de la facturation et une clé d'API. Ce plugin gratuit offre une carte et une autocomplétion d'adresse avec les services publics français, sans Google et sans clé.

Il fonctionne avec WS Form LITE et PRO.

## Ce qu'il ajoute

**Carte IGN**

- Fond Plan IGN, photos aériennes en option
- Marqueur posé au clic, déplaçable, effaçable, obligatoire si besoin
- Bouton de pose au clavier
- Icône et titre du marqueur personnalisables
- Bouton optionnel pour centrer sur le visiteur : le navigateur ne demande sa position qu'après ce clic
- Sur mobile, un doigt fait défiler la page et deux doigts déplacent la carte

**Adresse BAN**

- Autocomplétion utilisable entièrement au clavier
- Remplissage d'autres champs : numéro et rue, code postal, ville, code INSEE, département, latitude, longitude
- Liaison optionnelle à une carte, et géocodage inverse quand on pose le marqueur
- Saisie libre toujours possible, par exemple pour une adresse à l'étranger

**Personnalisation**

- Style : boutons, messages, contrôles de la carte et suggestions d'adresse suivent les réglages de style de WS Form
- Textes : les messages et le bouton de pose se changent champ par champ, dans Avancé > Libellés
- Coordonnées : une option de la carte les masque sous le message. Les lecteurs d'écran les annoncent toujours

Une tuile en erreur est rechargée automatiquement. Si les services IGN sont coupés, un message s'affiche et le formulaire reste envoyable.

Leaflet et les scripts du plugin ne se chargent que si le formulaire contient l'un de ces champs.

## Afficher le point sur le site

Le plugin ne rend pas de carte en front. Avec Post Management, le point part en méta, et c'est ton thème ou ton bloc qui l'affiche. Tout dépend de la cible du mapping :

| Cible | Résultat | À savoir |
|---|---|---|
| Méta simple | Compatible | La valeur est un JSON : `json_decode( get_post_meta( $post_id, 'ma_meta', true ), true )` |
| Champ Google Map d'ACF | Compatible | `get_field()` renvoie `lat`, `lng`, `zoom` et `address` si elle est connue |
| Bloc ACF en `usePostMeta` | Compatible | Ses champs sont en méta, le mapping les alimente directement |
| Bloc ACF standard | Incompatible | Il lit ses données dans le contenu de l'article, pas en méta |
| ACF OpenStreetMap Field | Incompatible | WS Form ne le propose pas au mapping. Forcé, il perd l'adresse et n'affiche aucun marqueur |
| Autres champs ACF de carte | Non pris en charge | Le blocage est le même : la liste des types mappables de WS Form est fermée |

Un champ Google Map d'ACF ne charge rien de Google sur ton site si tu affiches le point autrement, et la valeur survit à l'enregistrement de l'article. Par contre, son écran d'édition charge l'API Google dans l'admin, même sans clé. Pour éviter Google partout, passe par une méta simple.

Voici un rendu Leaflet avec les tuiles IGN, à partir d'un champ ACF nommé `coordonnees_gps`. Avec une méta simple, seule la lecture de `$point` change. Charge Leaflet de ton côté : celui du plugin n'est présent que sur les pages de formulaire.

```php
$point = get_field( 'coordonnees_gps' );

if ( ! empty( $point['lat'] ) && ! empty( $point['lng'] ) ) {
	printf(
		'<div class="carte-ign" style="height: 400px" data-lat="%s" data-lng="%s" data-zoom="%s"></div>',
		esc_attr( $point['lat'] ),
		esc_attr( $point['lng'] ),
		esc_attr( $point['zoom'] ?? 16 )
	);
}
```

```js
/**
 * Affiche une carte IGN avec un marqueur.
 *
 * @param {HTMLElement} element Conteneur avec data-lat, data-lng et data-zoom.
 */
function initIgnMap( element ) {
	const position = [ parseFloat( element.dataset.lat ), parseFloat( element.dataset.lng ) ];
	const map = L.map( element ).setView( position, parseInt( element.dataset.zoom, 10 ) || 16 );

	L.tileLayer(
		'https://data.geopf.fr/wmts?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2&STYLE=normal&FORMAT=image/png&TILEMATRIXSET=PM&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}',
		{
			maxZoom: 19,
			attribution: '<a href="https://cartes.gouv.fr/rechercher-une-donnee/dataset/IGNF_PLAN-IGN">IGN, Plan IGN</a>',
		}
	).addTo( map );

	L.marker( position ).addTo( map );
}

document.querySelectorAll( '.carte-ign' ).forEach( initIgnMap );
```

La licence des tuiles demande de citer la source : garde l'attribution. Ces pages contactent elles aussi `data.geopf.fr`, et ta politique de confidentialité doit le mentionner.

## Limites

- **Adresses :** seules les adresses françaises sont suggérées.
- **Sections répétables :** LITE n'en a pas, il faut donc PRO pour y placer ces champs.
- **Format de la valeur :** la carte enregistre un JSON. Il s'affiche en `latitude,longitude` dans la liste des soumissions, l'export CSV et les e-mails. Il reste en JSON dans `#field(ID)` hors e-mail et dans le détail d'une soumission, car WS Form n'y offre aucun filtre.
- **Mapping :** le mapping fonctionne vers une méta simple, ACF et Meta Box avec Post Management, y compris pour préremplir le formulaire. Les autres passerelles (JetEngine, Toolset, ACPT) n'ont pas été testées.

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
