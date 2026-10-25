#!/usr/bin/env bash
# NovaPHP one-click installer (Linux / macOS)
# Copies default config, .env and error views into the current project.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if ! command -v php >/dev/null 2>&1; then
  echo "PHP is not in PATH. Install PHP 8.3+ and try again." >&2
  exit 1
fi

PHP_BIN="$(command -v php)"
PHP_MAJOR="$("$PHP_BIN" -r 'echo PHP_MAJOR_VERSION;')"
PHP_MINOR="$("$PHP_BIN" -r 'echo PHP_MINOR_VERSION;')"
if [ "$PHP_MAJOR" -lt 8 ] || { [ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 3 ]; }; then
  echo "PHP 8.3+ is required. Found $($PHP_BIN -r 'echo PHP_VERSION;')." >&2
  exit 1
fi

exec "$PHP_BIN" "$SCRIPT_DIR/bin/nova" install "$@"
