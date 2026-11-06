# Carte IGN pour WS Form

Une carte et une autocomplétion d'adresse pour WS Form, sans Google et sans clé.

## Pourquoi ce plugin

Les champs Google de WS Form marchent bien. Par contre, il leur faut un projet Google Cloud, de la facturation et une clé d'API. Pour placer un point sur une carte de France, ça fait beaucoup.

Du coup, ce plugin fait le même travail avec les services publics français : les fonds de carte de l'IGN et la Base Adresse Nationale. Il est gratuit, il ne demande aucun compte, et il marche avec WS Form LITE et PRO.

## Installation

1. Télécharge `wsform-ign-X.Y.Z.zip` depuis la [dernière release](https://github.com/willybahuaud/wsform-ign/releases/latest). Pas l'archive du code source : elle contient tout le dépôt.
2. Installe-le comme n'importe quelle extension, à côté de WS Form.
3. Les deux champs apparaissent dans le groupe « French public services » du constructeur.

Les mises à jour arrivent ensuite dans l'admin, depuis les releases GitHub.

## Ce que ça ajoute

**Carte IGN**

- Plan IGN, ou photos aériennes
- Point placé au clic ou au clavier, déplaçable, effaçable, obligatoire si besoin
- Icône et titre du marqueur au choix
- Bouton optionnel pour centrer sur le visiteur : le navigateur ne demande sa position qu'après ce clic
- Sur mobile, un doigt fait défiler la page et deux doigts déplacent la carte

**Adresse BAN**

- Autocomplétion utilisable entièrement au clavier
- Remplissage d'autres champs : numéro et rue, code postal, ville, code INSEE, département, latitude, longitude
- Lien optionnel avec une carte, dans les deux sens
- Saisie libre toujours possible, pour une adresse à l'étranger par exemple

**Pour les deux**

- Les boutons, les messages et les suggestions suivent tes réglages de style WS Form
- Les messages se changent champ par champ, dans Avancé > Libellés
- Si les services IGN tombent, un message s'affiche et le formulaire reste envoyable
- Rien ne se charge sur une page sans ces champs

## Afficher le point sur le site

Le plugin ne rend pas de carte en front. Avec Post Management, le point part en méta, et c'est ton thème ou ton bloc qui l'affiche.

| Cible du mapping | Ça marche ? | À savoir |
|---|---|---|
| Méta simple | Oui | La valeur est un JSON, à lire avec `json_decode()` |
| Champ Google Map d'ACF | Oui | Son écran d'édition charge l'API Google dans l'admin, même sans clé |
| Meta Box `map` | Oui | Testé à l'écriture et au préremplissage, pas en affichage |
| Bloc ACF en `usePostMeta` | Oui | Ses champs sont en méta |
| Bloc ACF standard | Non | Il lit ses données dans le contenu de l'article |
| Champs ACF de carte tiers | Non | WS Form ne les propose pas au mapping |

Si tu veux éviter Google partout, prends la méta simple.

Voici un rendu Leaflet avec les tuiles IGN, à partir d'un champ ACF `coordonnees_gps`. Avec une méta simple, seule la lecture de `$point` change. Charge Leaflet de ton côté : celui du plugin n'est présent que sur les pages de formulaire.

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

La licence des tuiles demande de citer la source : garde l'attribution.

## Les limites

- **Adresses :** seules les adresses françaises sont suggérées.
- **Sections répétables :** LITE n'en a pas, il te faut donc PRO pour y placer ces champs.
- **Format de la valeur :** la carte enregistre un JSON. Il s'affiche en `latitude,longitude` dans la liste des soumissions, l'export CSV et les e-mails. Il reste en JSON dans `#field(ID)` hors e-mail et dans le détail d'une soumission, car WS Form n'y offre aucun filtre.
- **Autres extensions de champs :** le mapping n'a été testé qu'avec ACF et Meta Box. WS Form sait aussi écrire vers JetEngine, Toolset et ACPT, mais rien n'a été essayé avec ces trois-là.

## Confidentialité

Le plugin n'ajoute ni traceur ni télémétrie. Par contre, le navigateur du visiteur contacte directement les serveurs de l'IGN (`data.geopf.fr`). Ils reçoivent donc son adresse IP et le texte saisi dans le champ adresse.

Mentionne ce service dans ta politique de confidentialité, y compris si tu affiches les tuiles sur d'autres pages.

## Développer

```bash
composer install
composer lint
bin/build-release.sh
```

L'environnement de test est décrit dans `docs/dev-ddev.md`. Pour publier, passe la version dans `wsform-ign.php` (en-tête et constante) et dans `readme.txt`, puis pousse un tag `vX.Y.Z`. Le workflow refuse un tag qui ne correspond pas.

Le plugin se met à jour depuis GitHub, et les règles de WordPress.org l'interdisent. C'est pour ça qu'il n'est pas sur le répertoire.

## Licence

GPL-2.0-or-later. Leaflet 1.9.4 est embarqué sous licence BSD-2-Clause. Ce plugin n'est affilié ni à l'IGN ni à WS Form.

Fait par [Willy Bahuaud](https://wabeo.fr).
