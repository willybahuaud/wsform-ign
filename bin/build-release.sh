#!/usr/bin/env bash
#
# Construit dist/wsform-ign-X.Y.Z.zip et son manifeste de compatibilité.
#
# Le plugin n'a pas de dépendance de production : aucune étape Composer n'est nécessaire.

set -euo pipefail

root_directory="$(cd "$(dirname "$0")/.." && pwd)"
slug="wsform-ign"
main_file="$root_directory/$slug.php"
readme_file="$root_directory/readme.txt"
output_directory="${1:-$root_directory/dist}"
version="$(sed -n 's/^ \* Version: *//p' "$main_file" | head -n 1)"
requires="$(sed -n 's/^ \* Requires at least: *//p' "$main_file" | head -n 1)"
requires_php="$(sed -n 's/^ \* Requires PHP: *//p' "$main_file" | head -n 1)"
tested="$(sed -n 's/^Tested up to: *//p' "$readme_file" | head -n 1)"
staging_directory="$(mktemp -d)"

trap 'rm -rf "$staging_directory"' EXIT

mkdir -p "$output_directory" "$staging_directory/$slug"
rsync -a --exclude-from="$root_directory/.distignore" "$root_directory/" "$staging_directory/$slug/"

rm -f "$output_directory/$slug-$version.zip"
(cd "$staging_directory" && zip -rq "$output_directory/$slug-$version.zip" "$slug")

printf '{"requires":"%s","requires_php":"%s","tested":"%s"}\n' "$requires" "$requires_php" "$tested" > "$output_directory/$slug-$version.json"

echo "$output_directory/$slug-$version.zip"
