#!/usr/bin/env bash
#
# Refuse un tag différent de l'en-tête Version du plugin.
#
# Usage : bin/check-tag.sh v1.2.3

set -euo pipefail

root_directory="$(cd "$(dirname "$0")/.." && pwd)"
tag="${1:?Usage: check-tag.sh <tag>}"
plugin_version="$(sed -n 's/^ \* Version: *//p' "$root_directory/wsform-ign.php" | head -n 1)"
constant_version="$(sed -n "s/^define( 'WSFIGN_VERSION', '\(.*\)' );$/\1/p" "$root_directory/wsform-ign.php")"
stable_tag="$(sed -n 's/^Stable tag: *//p' "$root_directory/readme.txt" | head -n 1)"

if [[ ! "$tag" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
	echo "Tag invalide : $tag (attendu : vX.Y.Z)" >&2
	exit 1
fi

for declared_version in "$plugin_version" "$constant_version" "$stable_tag"; do
	if [[ "$tag" != "v$declared_version" ]]; then
		echo "Le tag $tag ne correspond pas aux versions du plugin : en-tete $plugin_version, constante $constant_version, readme $stable_tag" >&2
		exit 1
	fi
done

echo "Tag $tag conforme a la version du plugin."
