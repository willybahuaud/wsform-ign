#!/usr/bin/env bash
#
# Construit dist/wsform-ign-X.Y.Z.zip et son manifeste de compatibilité.
#
# Le plugin n'a pas de dépendance de production : aucune étape Composer n'est nécessaire.
#
# Part des fichiers connus de git (suivis ou nouveaux, jamais ignorés) : un build local
# n'embarque donc ni .env, ni export, ni note oubliée. .distignore retire ensuite les
# fichiers de développement.

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

# Indique si un chemin est exclu par .distignore.
# « /chemin » exclut ce chemin depuis la racine et tout ce qu'il contient.
# « nom » ou « **/nom » exclut tout fichier de ce nom, les jokers compris (*.log).
is_excluded() {
	local path="$1" pattern
	while IFS= read -r pattern || [[ -n "$pattern" ]]; do
		[[ -z "$pattern" || "$pattern" == \#* ]] && continue
		if [[ "$pattern" == /* ]]; then
			pattern="${pattern#/}"
			[[ "$path" == "$pattern" || "$path" == "$pattern"/* ]] && return 0
		else
			pattern="${pattern#\*\*/}"
			# Motif sans guillemets : les jokers doivent s'appliquer.
			# shellcheck disable=SC2053
			[[ "$(basename "$path")" == $pattern ]] && return 0
		fi
	done < "$root_directory/.distignore"
	return 1
}

mkdir -p "$output_directory" "$staging_directory/$slug"

cd "$root_directory"
git ls-files --cached --others --exclude-standard | while IFS= read -r path; do
	if [[ -f "$path" ]] && ! is_excluded "$path"; then
		mkdir -p "$staging_directory/$slug/$(dirname "$path")"
		cp "$path" "$staging_directory/$slug/$path"
	fi
done

rm -f "$output_directory/$slug-$version.zip"
(cd "$staging_directory" && zip -rq "$output_directory/$slug-$version.zip" "$slug")

printf '{"requires":"%s","requires_php":"%s","tested":"%s"}\n' "$requires" "$requires_php" "$tested" > "$output_directory/$slug-$version.json"

echo "$output_directory/$slug-$version.zip"
