#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
DIST="$ROOT/dist"
TMP="$(mktemp -d "${TMPDIR:-/tmp}/reactwp-core.XXXXXX")"

cleanup() {
  echo "Cleaning temporary directory: $TMP"
  rm -rf "$TMP"
}
trap cleanup EXIT INT TERM

WP_ZIP="$TMP/latest.zip"
ACF_ZIP="$TMP/advanced-custom-fields-pro.zip"

echo "Project root: $ROOT"
echo "Dist directory: $DIST"
echo "Temp directory: $TMP"

echo "Ensuring dist directories exist..."
mkdir -p "$DIST"
mkdir -p "$DIST/wp-content/mu-plugins"

echo "Downloading WordPress..."
echo "Target: $WP_ZIP"
curl -L -o "$WP_ZIP" https://wordpress.org/latest.zip

echo "Unzipping WordPress..."
unzip -o "$WP_ZIP" -d "$TMP"

echo "Copying WordPress core into dist..."
cp -a "$TMP/wordpress/." "$DIST/"

echo "Removing default WordPress bundled content..."
rm -rf "$DIST/wp-content/plugins/akismet"
rm -f "$DIST/wp-content/plugins/hello.php"
rm -rf "$DIST/wp-content/themes"/twenty*

echo "Removing previous ACF PRO..."
rm -rf "$DIST/wp-content/mu-plugins/advanced-custom-fields-pro"

echo "Downloading ACF PRO..."
echo "Target: $ACF_ZIP"
curl -L -o "$ACF_ZIP" "http://reactwp.com/download/plugins/advanced-custom-fields-pro.zip"

echo "Unzipping ACF PRO..."
unzip -o "$ACF_ZIP" -d "$DIST/wp-content/mu-plugins"

echo "get:core completed successfully."