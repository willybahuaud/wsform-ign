# Services IGN (Géoplateforme)

Testé sans clé le 2026-11-06 sauf mention contraire. Les limites viennent de cartes.gouv.fr, qui remplace geoservices.ign.fr depuis mars 2026.

## Tuiles

Modèle pour Leaflet (les couches sont en projection Web Mercator, ensemble `PM`) :

```text
https://data.geopf.fr/wmts?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER={couche}&STYLE=normal&FORMAT={format}&TILEMATRIXSET=PM&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}
```

| Couche | Format | Test |
|---|---|---|
| `GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2` | `image/png` | 200 |
| `ORTHOIMAGERY.ORTHOPHOTOS` | `image/jpeg` | 200 |

Testé sur une seule tuile, au zoom 12 (`TILEROW=1400`, `TILECOL=2100`). Les zooms maximaux de chaque couche restent à relever dans `https://data.geopf.fr/wmts?SERVICE=WMTS&REQUEST=GetCapabilities`.

## Adresses

L'ancienne API `api-adresse.data.gouv.fr` a été décommissionnée fin janvier 2026 : ne l'utilise pas.

### Recherche avec autocomplétion (à privilégier)

```text
https://data.geopf.fr/geocodage/search?q=8+boulevard+du+po&autocomplete=1&limit=5&index=address
```

Réponse GeoJSON. `features[].geometry.coordinates` vaut `[lng, lat]`. Propriétés vues : `label`, `score`, `housenumber`, `id`, `banId`, `name`, `postcode`, `citycode`, `city`, `context`, `type`, `importance`, `depcode`, `x`, `y`.

La rue seule n'apparaît pas forcément dans une propriété dédiée : `name` vaut par exemple « 8 Boulevard du Port ». Relève les variantes par `type` (`housenumber`, `street`, `locality`, `municipality`) avant de figer le mapping.

### Géocodage inverse

```text
https://data.geopf.fr/geocodage/reverse?lon=2.29&lat=49.89&limit=1
```

Même forme de réponse. L'essai a renvoyé un `locality` et non un numéro de rue : prévois tous les types.

### Autocomplétion dédiée (à éviter pour l'instant)

```text
https://data.geopf.fr/geocodage/completion?text=8+boulevard+du+po&maximumResponses=5
```

Réponse propre à ce service (`x`, `y`, `fulltext`, `zipcode`, `city`, `street`, `kind`), avec un champ `country` qui valait curieusement `StreetAddress`. Une requête abrégée (`8 bd du po`) a renvoyé une liste vide, et cette API a une limite plus basse.

## Limites

| Service | Limite par IP | Au-delà |
|---|---|---|
| WMTS | Aucune, sauf usage abusif | Blocage possible par l'IGN |
| Géocodage | 50 requêtes/s | HTTP 429 pendant 5 s, en-tête `retry-after` |
| Autocomplétion dédiée | 10 requêtes/s | HTTP 429 pendant 5 s |

Les appels partent du navigateur du visiteur (CORS ouvert, `access-control-allow-origin: *`), donc la limite s'applique à chaque visiteur. Temporise quand même la saisie.

## Licence et mentions

- Les données sont sous la licence choisie par leur fournisseur, et par défaut sous Licence Ouverte Etalab.
- Les CGU prévoient un usage « à des fins commerciales ou non », et les services sont gratuits sauf licence spécifique.
- **À vérifier avant publication :** la mention d'attribution à afficher sur la carte. Elle n'a été trouvée sur aucune page lue. Regarde les métadonnées des deux couches et le texte de la Licence Ouverte.

## Sources

- https://cartes.gouv.fr/aide/fr/guides-utilisateur/utiliser-les-services-de-la-geoplateforme/limites-d-usage/
- https://cartes.gouv.fr/aide/fr/guides-utilisateur/utiliser-les-services-de-la-geoplateforme/geocodage/
- https://cartes.gouv.fr/aide/fr/guides-utilisateur/utiliser-les-services-de-la-geoplateforme/diffusion/wmts/
- https://cartes.gouv.fr/cgu/
- https://www.data.gouv.fr/posts/lapi-adresse-de-la-base-adresse-nationale-est-transferee-a-lign-10
