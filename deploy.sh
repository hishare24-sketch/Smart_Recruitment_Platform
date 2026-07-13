#!/usr/bin/env bash
# صفح / التوظيف — نشر: compose up + health + migrate + nginx reload
set -euo pipefail
cd "$(dirname "$0")"

export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-sufha}"

if docker compose version >/dev/null 2>&1; then DC="docker compose"; else DC="docker-compose"; fi

dc() { # shellcheck disable=SC2086
  $DC "$@"
}

if [ ! -f .env ]; then
  cp .env.docker.example .env
  echo "Created .env from .env.docker.example"
fi

NGINX_NETWORK="$(grep -E '^NGINX_NETWORK=' .env 2>/dev/null | cut -d= -f2 || true)"
NGINX_NETWORK="${NGINX_NETWORK:-dashboard-net}"

# Prefer the network that the running nginx container actually uses
NGINX_CT="$(docker ps --format '{{.Names}}' | grep -Ei 'nginx' | head -n1 || true)"
if [ -n "$NGINX_CT" ]; then
  DETECTED="$(docker inspect -f '{{range $k,$v := .NetworkSettings.Networks}}{{$k}} {{end}}' "$NGINX_CT" 2>/dev/null | awk '{print $1}' || true)"
  [ -n "$DETECTED" ] && NGINX_NETWORK="$DETECTED"
fi
export NGINX_NETWORK
echo "Using nginx network: $NGINX_NETWORK"

if ! docker network inspect "$NGINX_NETWORK" >/dev/null 2>&1; then
  docker network create "$NGINX_NETWORK"
fi

if grep -qE '^NGINX_NETWORK=' .env 2>/dev/null; then
  sed -i "s|^NGINX_NETWORK=.*|NGINX_NETWORK=${NGINX_NETWORK}|" .env
else
  echo "NGINX_NETWORK=${NGINX_NETWORK}" >> .env
fi

echo "Building and starting containers..."
# Avoid docker-compose 1.29 ContainerConfig recreate bug
dc down --remove-orphans || true
dc up -d --build --remove-orphans

WEB_CT="$(docker ps --format '{{.Names}}' | grep -E "${COMPOSE_PROJECT_NAME}.*web" | head -n1 || true)"
if [ -n "$WEB_CT" ]; then
  docker network connect --alias sufha-web "$NGINX_NETWORK" "$WEB_CT" 2>/dev/null || true
fi

echo "Waiting for API health..."
ok=0
for i in $(seq 1 40); do
  if dc exec -T api php -r "exit(@file_get_contents('http://127.0.0.1:10000/up')===false?1:0);" 2>/dev/null; then
    ok=1
    echo "API healthy after ${i} attempt(s)"
    break
  fi
  sleep 5
done

if [ "$ok" != 1 ]; then
  echo "API did not become healthy" >&2
  dc ps >&2 || true
  dc logs --tail=120 api >&2 || true
  exit 1
fi

echo "Running migrations..."
dc exec -T api php artisan migrate --force
dc exec -T api php artisan permission:insert || true

if [ -n "$NGINX_CT" ]; then
  echo "Reloading nginx: $NGINX_CT"
  docker exec "$NGINX_CT" nginx -t
  docker exec "$NGINX_CT" nginx -s reload
else
  echo "No nginx container found - skip reload"
  docker ps --format '  - {{.Names}}'
fi

echo "Done: https://sufha.com"
