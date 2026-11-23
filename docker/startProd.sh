#!/usr/bin/env sh

set -eu

cd /app

echo 'Preparing application...'
/prepare-runtime

echo 'Starting server...'
exec rr serve -c .rr.yaml
