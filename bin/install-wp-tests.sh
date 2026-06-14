#!/usr/bin/env bash
# Install the WordPress test suite and a test database.
#
# Usage:
#   bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-db-create]
#
# Examples:
#   bash bin/install-wp-tests.sh wp_test root '' localhost latest
#   DB_NAME=wp_test DB_USER=root DB_PASS='' bash bin/install-wp-tests.sh
#
# Environment variables (override positional args):
#   DB_NAME, DB_USER, DB_PASS, DB_HOST, WP_VERSION, WP_TESTS_DIR, WP_CORE_DIR

set -euo pipefail

DB_NAME="${1:-${DB_NAME:-wp_test}}"
DB_USER="${2:-${DB_USER:-root}}"
DB_PASS="${3:-${DB_PASS:-}}"
DB_HOST="${4:-${DB_HOST:-localhost}}"
WP_VERSION="${5:-${WP_VERSION:-latest}}"
SKIP_DB_CREATE="${6:-false}"

WP_TESTS_DIR="${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}"
WP_CORE_DIR="${WP_CORE_DIR:-/tmp/wordpress}"

# ---- helpers ----------------------------------------------------------------

download() {
    if command -v curl &>/dev/null; then
        curl --silent "$1" >"$2"
    elif command -v wget &>/dev/null; then
        wget --quiet -O "$2" "$1"
    else
        echo "Neither curl nor wget found." >&2; exit 1
    fi
}

if [[ "$WP_VERSION" == "latest" ]]; then
    local_version_file="/tmp/wp-latest.json"
    download "https://api.wordpress.org/core/version-check/1.7/" "$local_version_file"
    WP_VERSION=$(grep -oP '"version":"\K[^"]+' "$local_version_file" | head -1)
    echo "Latest WordPress version: $WP_VERSION"
fi

WP_TESTS_TAG="tags/$WP_VERSION"
WP_SVN_BASE="https://develop.svn.wordpress.org"

# ---- install WordPress core --------------------------------------------------

if [[ ! -d "$WP_CORE_DIR" ]]; then
    mkdir -p "$WP_CORE_DIR"
    download "https://wordpress.org/wordpress-${WP_VERSION}.tar.gz" /tmp/wp.tar.gz
    tar --strip-components=1 -zxf /tmp/wp.tar.gz -C "$WP_CORE_DIR"
fi

if [[ ! -f "$WP_CORE_DIR/wp-includes/version.php" ]]; then
    echo "WordPress core not installed at $WP_CORE_DIR" >&2; exit 1
fi

# ---- install test suite ------------------------------------------------------

if [[ ! -d "$WP_TESTS_DIR/includes" ]]; then
    mkdir -p "$WP_TESTS_DIR"
    svn co --quiet "$WP_SVN_BASE/${WP_TESTS_TAG}/tests/phpunit/includes/" "$WP_TESTS_DIR/includes"
    svn co --quiet "$WP_SVN_BASE/${WP_TESTS_TAG}/tests/phpunit/data/"     "$WP_TESTS_DIR/data"
fi

if [[ ! -f "$WP_TESTS_DIR/wp-tests-config.php" ]]; then
    download "$WP_SVN_BASE/${WP_TESTS_TAG}/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s|dirname( __FILE__ ) . '/src/'|'$WP_CORE_DIR/'|" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s|youremptytestdbnamehere|$DB_NAME|"              "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s|yourusernamehere|$DB_USER|"                     "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s|yourpasswordhere|$DB_PASS|"                     "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s|localhost|$DB_HOST|"                            "$WP_TESTS_DIR/wp-tests-config.php"
fi

# ---- create test database ----------------------------------------------------

if [[ "$SKIP_DB_CREATE" != "true" ]]; then
    mysqladmin create "$DB_NAME" --user="$DB_USER" --password="$DB_PASS" --host="$DB_HOST" 2>/dev/null || true
fi

echo "WordPress test suite installed."
echo "  Core:   $WP_CORE_DIR"
echo "  Tests:  $WP_TESTS_DIR"
echo "  DB:     $DB_NAME @ $DB_HOST"
