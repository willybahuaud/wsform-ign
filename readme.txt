=== Carte IGN pour WS Form ===
Contributors: willybahuaud
Tags: ws form, map, address, ign, autocomplete
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

IGN map and French address fields for WS Form. No Google, no API key.

== Description ==

WS Form ships Google map and address fields, which need a Google Cloud project, billing and an API key. This plugin offers the same kind of fields with French public services, with no key and no account.

= IGN Map field =

* IGN map background, with optional aerial photos
* Default latitude, longitude, zoom and height, like the Google field
* Click to place the marker, drag to move it, link to reset it
* A button to place the marker from the keyboard
* Required marker, custom marker icon and title
* Optional button to center on the visitor, who is only asked for their location after clicking it

= BAN Address field =

* Address autocomplete, fully usable from the keyboard (combobox pattern)
* Fills other fields: number and street, postal code, city, INSEE code, department, latitude, longitude
* Optional link to an IGN Map field, which centers on the chosen address
* Optional reverse geocoding when the marker is placed
* Free typing stays possible when an address is not found or is abroad

If the IGN services are down, a message appears and the form can still be sent.

Leaflet and the plugin scripts only load on pages whose form contains one of these fields.

= WS Form editions =

Both fields work with WS Form LITE and PRO. Repeatable sections are not available in LITE, so using these fields in a repeatable section requires PRO.

= Known limits =

* Addresses outside France are not suggested. They can still be typed by hand.
* The map value is stored as JSON. It is shown as `latitude,longitude` in the submissions list, the CSV export and emails. It stays as JSON in `#field(ID)` outside emails and in the submission detail sidebar, because WS Form offers no filter there.
* The zoom and layer are kept in the JSON value. With Post Management, mapping works with a plain custom field, ACF and Meta Box, including to populate the form. Other integrations (JetEngine, Toolset, ACPT) have not been tested.

== Privacy ==

This plugin adds no tracking and no telemetry. It has no settings page and no ads.

The visitor's browser contacts the IGN servers at `data.geopf.fr` directly, to load map tiles and search addresses. These servers therefore receive the visitor's IP address, and what they type in the address field.

As the site owner, you should mention this service in your privacy policy.

== Data sources and licenses ==

Map backgrounds and addresses come from the Géoplateforme operated by IGN. The two map layers are published under the French Licence Ouverte / Open Licence 2.0. The map links to the source page of each layer.

This plugin is not affiliated with or endorsed by IGN or WS Form.

The plugin bundles Leaflet 1.9.4 (BSD-2-Clause), see `assets/vendor/leaflet/LICENSE`.

== Updates ==

Updates come from the GitHub releases of the plugin, not from WordPress.org.

== Changelog ==

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
