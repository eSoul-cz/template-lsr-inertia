#!/usr/bin/env sh

set -eu

cd /app

echo 'Preparing application in development mode...'
composer install --prefer-dist --no-interaction --no-progress
CI=true pnpm install --no-frozen-lockfile

# Keep both processes under the container's signal/exit lifecycle.
stop() {
  trap - EXIT INT TERM
  kill "$vite_pid" 2>/dev/null || true
  wait "$vite_pid" 2>/dev/null || true
  if [ -n "$rr_pid" ]; then
    kill "$rr_pid" 2>/dev/null || true
    wait "$rr_pid" 2>/dev/null || true
  fi
}

pnpm vite --host=0.0.0.0 &
vite_pid=$!
rr_pid=
trap stop EXIT
trap 'exit 0' INT TERM

# Compile and boot workers only after the dev asset server can answer requests.
attempt=0
until php -r '$c = curl_init("http://127.0.0.1:5173/@vite/client"); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2]); exit(curl_exec($c) !== false && curl_getinfo($c, CURLINFO_RESPONSE_CODE) === 200 ? 0 : 1);'; do
  attempt=$((attempt + 1))
  if [ "$attempt" -ge 30 ] || ! kill -0 "$vite_pid" 2>/dev/null; then
    echo 'Vite did not become ready.' >&2
    exit 1
  fi
  sleep 1
done

/prepare-runtime
rr serve -c .rr.yaml &
rr_pid=$!

while kill -0 "$vite_pid" 2>/dev/null && kill -0 "$rr_pid" 2>/dev/null; do
  sleep 1
done

# An unexpected exit of either process makes the development container fail.
exit 1
