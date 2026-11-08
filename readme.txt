=== Carte IGN pour WS Form ===
Contributors: willybahuaud
Tags: ws form, map, address, ign, autocomplete
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.3.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

IGN map and French address fields for WS Form. No Google, no API key.

== Description ==

WS Form's Google fields work well, but they need a Google Cloud project, billing and an API key. That is a lot to place a point on a map of France.

This plugin does the same job with French public services: IGN map backgrounds and the national address database (BAN). It is free, needs no account, and works with WS Form LITE and PRO.

= IGN Map field =

* IGN map or aerial photos
* Place the point by clicking or tapping, then move it, clear it, or make it required
* From the keyboard, a "place the point at the center" button shows when reached with Tab. With a mouse it would be useless
* Custom marker icon and title
* Optional button under the map to place the point on the visitor, or only center the map on them. The visitor is only asked for their location after clicking it
* On mobile, one finger scrolls the page and two fingers move the map

= BAN Address field =

* Address autocomplete, fully usable from the keyboard
* Fills other fields: number and street, postal code, city, INSEE code, department, latitude, longitude
* Optional link with a map, in both directions
* Free typing stays possible, for an address abroad for example

= For both fields =

* Buttons, messages and suggestions follow your WS Form style settings
* Messages can be changed field by field, in Advanced > Labels
* If the IGN services are down, a message appears and the form can still be sent
* Nothing loads on a page without these fields

= Showing the point on your site =

This plugin does not render a map on the front end. With Post Management, the point is stored as post meta, and your theme or block displays it.

* Plain custom field: works. The value is JSON, read it with `json_decode()`.
* ACF Google Map field: works, but its edit screen loads the Google API in the admin, even without a key.
* Meta Box map field: works for saving and populating the form. Displaying it was not tested.
* ACF block with `usePostMeta`: works.
* Standard ACF block: does not work, it reads its data from the post content.
* Third-party ACF map fields: do not work, WS Form does not offer them for mapping.

To avoid Google everywhere, use a plain custom field. The README on GitHub has a Leaflet example with IGN tiles.

= Known limits =

* Only French addresses are suggested.
* LITE has no repeatable sections, so using these fields in one requires PRO.
* The map value is stored as JSON. It shows as `latitude,longitude` in the submissions list, the CSV export and emails. It stays as JSON in `#field(ID)` outside emails and in the submission detail, because WS Form offers no filter there.
* Mapping was only tested with ACF and Meta Box. WS Form can also write to JetEngine, Toolset and ACPT, but none of them has been tried.

== Installation ==

1. Download `wsform-ign-X.Y.Z.zip` from the latest GitHub release: https://github.com/willybahuaud/wsform-ign/releases/latest
2. Install it like any plugin, alongside WS Form.
3. Both fields appear in the "French public services" group of the form builder.

== Privacy ==

This plugin adds no tracking and no telemetry. However, the visitor's browser contacts the IGN servers directly (`data.geopf.fr`). They receive the visitor's IP address and what is typed in the address field.

Mention this service in your privacy policy, including if you display the tiles on other pages.

== Data sources and licenses ==

Map backgrounds and addresses come from the Géoplateforme operated by IGN. The two map layers are published under the French Licence Ouverte / Open Licence 2.0, and the map links to the source of each layer.

The plugin bundles Leaflet 1.9.4 (BSD-2-Clause), see `assets/vendor/leaflet/LICENSE`.

This plugin is not affiliated with or endorsed by IGN or WS Form.

== Updates ==

Updates come from the GitHub releases of the plugin, not from WordPress.org.

== Changelog ==

= 0.3.4 =
* Updates: on a multisite network, the GitHub release is now checked once for the whole network instead of once per site.
* Release packaging: the archive is built from the files known to git, and coding standards are checked before publishing.

= 0.3.3 =
* Map: the location button now sits under the map, with its icon and a text, instead of a small icon inside the map.
* Map: the "place the point at the center" button only shows when reached with the keyboard. With a mouse or a finger, you place the point on the map.

= 0.3.2 =
* Map: the location button can now place the point on the visitor, not only center the map. Choose it in Basic > Map > Visitor location.

= 0.3.1 =
* Security: stricter validation of the map value on the server. HTML, nested values, invalid zoom or layer and oversized values are now refused.
* Security: updates are only downloaded from github.com over HTTPS, and malformed GitHub responses no longer cause errors.
* Map: a latitude, longitude or zoom set to 0 is no longer replaced by the default value.
* Map: a populated value now opens on its stored layer.
* Address: "no suggestion" now shows on screen, not only to screen readers.
* Details window: the author name links to wabeo.fr.

= 0.3.0 =
* Documentation: how to show the point on your site, which ACF targets work, and a Leaflet example. No change to the fields.

= 0.2.0 =
* Map: a short hint explains how to place the point.
* Map: the layer switch is now a direct button, and the location button sits inside the map.
* Map: "Clear" only shows when there is a point.
* Map: the message says whether the point was placed or updated, with its coordinates. A new field option can hide them, and screen readers still announce them.
* Map and address: messages can be customized in the field settings, along with the place button.
* Styling: the map, its messages and the address suggestions now follow the WS Form style settings.
* Mobile: one finger scrolls the page, two fingers move the map. The map is at least 280 px high, with 44 px touch targets.

= 0.1.4 =
* Map: failed tiles are loaded again automatically, after 1 and 3 seconds.
* Map: the "map background unavailable" message now appears only when no tile loads at the current zoom, and goes away once tiles are back.

= 0.1.3 =
* Submissions list: repeatable map values are shown on separate lines, without a visible `<br />`.
* CSV export: repeatable map coordinates are separated by a semicolon.

= 0.1.2 =
* Update details: no more false "not tested" warning on minor WordPress versions.
* Update details: release notes are shown as formatted text.

= 0.1.1 =
* Maintenance release, with no change to the fields.

= 0.1.0 =
* First version: IGN Map and BAN Address fields.
