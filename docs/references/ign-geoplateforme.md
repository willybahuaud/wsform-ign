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

Les deux couches vont du zoom 0 au zoom 19 (ensemble `PM_0_19` dans `https://data.geopf.fr/wmts?SERVICE=WMTS&REQUEST=GetCapabilities`, relevé le 2026-11-06).

## Adresses

L'ancienne API `api-adresse.data.gouv.fr` a été décommissionnée fin janvier 2026 : ne l'utilise pas.

### Recherche avec autocomplétion (à privilégier)

```text
https://data.geopf.fr/geocodage/search?q=8+boulevard+du+po&autocomplete=1&limit=5&index=address
```

Réponse GeoJSON. `features[].geometry.coordinates` vaut `[lng, lat]`. Propriétés vues : `label`, `score`, `housenumber`, `id`, `banId`, `name`, `postcode`, `citycode`, `city`, `context`, `type`, `importance`, `depcode`, `x`, `y`.

Variantes relevées le 2026-11-06 :

| `type` | `name` | `street` |
|---|---|---|
| `housenumber` | « 8 Boulevard du Port » | « Boulevard du Port » |
| `street` | « Boulevard du Port » | identique |
| `locality` | « Square de la Motte » | vide |
| `municipality` | « Amiens », le nom de la commune | vide |

La ligne d'adresse vaut donc `name`, sauf pour `municipality` où elle est vide.

Le service répond 400 si `q` fait moins de 3 ou plus de 200 caractères, ou ne commence ni par une lettre ni par un chiffre.

### Géocodage inverse

```text
https://data.geopf.fr/geocodage/reverse?lon=2.29&lat=49.89&limit=1
```

Même forme de réponse, avec ces différences :

- `depcode` est absent. Il manque aussi outre-mer. Le code du département se lit au début de `context` (« 80, Somme, Hauts-de-France »).
- `street` peut être vide, même pour le type `street`.
- `label` peut contenir l'arrondissement (« 75007 Paris,Paris 7e Arrondissement ») : recompose le libellé depuis `name`, `postcode` et `city`.
- La liste `features` est vide loin de toute adresse, en mer par exemple.

Tous les types peuvent sortir, pas seulement un numéro de rue.

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
- Les métadonnées des couches Plan IGN et BD ORTHO indiquent toutes deux la Licence Ouverte (relevé le 2026-11-06, fiches CSW `IGNF_PLAN-IGN` et `IGNF_BD-ORTHO`).
- La Licence Ouverte 2.0 demande de mentionner la source et la date de dernière mise à jour. Elle admet un lien hypertexte vers la source, avec une mention effective de la paternité. Elle interdit de suggérer une caution du concédant.
- **Attribution retenue :** « IGN, Plan IGN » ou « IGN, Photos aériennes », avec un lien vers la fiche cartes.gouv.fr de la couche. Celle du Plan IGN affiche « Mise à jour continue ». Aucun texte officiel imposé n'a été trouvé : les CGU renvoient à la licence de chaque donnée.

## Sources

- https://cartes.gouv.fr/aide/fr/guides-utilisateur/utiliser-les-services-de-la-geoplateforme/limites-d-usage/
- https://cartes.gouv.fr/aide/fr/guides-utilisateur/utiliser-les-services-de-la-geoplateforme/geocodage/
- https://cartes.gouv.fr/aide/fr/guides-utilisateur/utiliser-les-services-de-la-geoplateforme/diffusion/wmts/
- https://cartes.gouv.fr/cgu/
- https://www.data.gouv.fr/posts/lapi-adresse-de-la-base-adresse-nationale-est-transferee-a-lign-10
