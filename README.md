# Klareg Live — Overlay Streaming System

Laravel backend + static HTML/JS overlays for OBS. Streams real-time Twitch events (chat, follows, subs, bits, raids) to OBS browser sources via Laravel Reverb (WebSockets).

## Project Layout

```
Live/
├── backend/          Laravel 13 app (Filament admin, Reverb, Twitch EventSub + IRC)
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   └── tests/
└── overlay/          Static overlay pages consumed by OBS as browser sources
    ├── gaming.html
    ├── just-chatting.html
    ├── starting-soon.html
    ├── brb.html
    ├── ending.html
    ├── screen-share.html
    ├── alert-layer.html
    ├── js/
    └── style/
```

## Setup

### 1. Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed       # optional — populates default OverlaySetting + Schedule
```

Edit `backend/.env`:

| Variable | Purpose |
|---|---|
| `APP_URL` | Public URL of the Laravel app (e.g. `http://live-backend.test`) |
| `TWITCH_CLIENT_ID` / `TWITCH_CLIENT_SECRET` | Create an app at https://dev.twitch.tv/console/apps |
| `TWITCH_CHANNEL_NAME` | Your Twitch username |
| `TWITCH_CHANNEL_ID` | Numeric channel ID (auto-resolved on first `twitch:eventsub` run if missing) |
| `REVERB_APP_ID` / `REVERB_APP_KEY` / `REVERB_APP_SECRET` | Strong random strings — **never expose the secret client-side** |
| `REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME` | Where Reverb listens (default `8080`, `http` in dev) |
| `CORS_ALLOWED_ORIGINS` | Comma-separated list. Defaults to `APP_URL` only. |

### 2. Frontend admin (Filament)

```bash
php artisan make:filament-user   # create an admin
npm install && npm run build     # or `npm run dev` in development
```

Admin panel: `APP_URL/admin`.

### 3. Long-running processes

Open four terminals (or use Supervisor / systemd in prod):

```bash
# Web server (Herd handles this automatically via .test domain)
php artisan serve

# WebSocket broadcast server (port 8080)
php artisan reverb:start

# Twitch EventSub listener (follows, subs, bits, raids, hype trains)
php artisan twitch:eventsub

# Twitch IRC chat listener
php artisan twitch:irc
```

Optional:
```bash
php artisan twitch:refresh-token          # manual token refresh
php artisan twitch:cleanup-subscriptions  # remove orphaned EventSub subs
```

### 4. OBS

Add each scene as a **Browser Source** pointing at the matching overlay, e.g.
`http://live-backend.test/overlay/gaming.html` at `1920×1080`.

If the overlay is loaded from a different origin than the backend, add a meta tag
to its `<head>` so it knows where to reach the API:

```html
<meta name="api-base" content="http://live-backend.test/api/overlay">
```

Otherwise the overlay uses `window.location.origin + '/api/overlay'` by default.

## Public API

| Route | Description |
|---|---|
| `GET /api/health` | Liveness probe (DB + cache). `200 ok` / `503 degraded`. |
| `GET /api/overlay/settings` | Current overlay settings (cached 1h, invalidated on save). |
| `GET /api/overlay/schedule` | Active schedule entries, ordered. |
| `GET /api/overlay/reverb-config` | Public Reverb connection params (**never** the secret). |
| `GET /api/twitch/status` | EventSub / IRC / token status. |

## Deployment — Docker / Portainer

Production stack (5 services, auto-restart, managed via Portainer):

```
overlay.philippedelaval.dev   → web container (nginx + php-fpm)
reverb.philippedelaval.dev    → reverb container (WebSocket on 8080)
(internal)                    → eventsub + irc workers + Postgres
```

### One-time setup

1. **DNS / Cloudflare** — create two A/CNAME records pointing at your homelab:
   ```
   overlay.philippedelaval.dev   →  <homelab public IP / tunnel>
   reverb.philippedelaval.dev    →  <homelab public IP / tunnel>
   ```
   Through Cloudflare, proxy (orange cloud) must be compatible with WebSocket upgrade for the reverb subdomain — orange is fine with Cloudflare's WS support.

2. **Twitch app** — on https://dev.twitch.tv/console/apps, add the OAuth Redirect URL:
   ```
   https://overlay.philippedelaval.dev/twitch/oauth/callback
   ```

3. **Portainer stack** — Stacks → Add stack → Git Repository:
   - Repository: `git@github.com:<you>/klareg-live.git` (or public https URL)
   - Compose path: `docker-compose.yml`
   - Environment variables: upload `.env.docker.example` then fill `CHANGE_ME` fields.
   - Required `traefik` external network must already exist on Docker.

4. **Generate secrets** before first deploy:
   ```bash
   # APP_KEY
   docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
   # REVERB_APP_KEY / REVERB_APP_SECRET
   openssl rand -hex 24
   ```

5. **Deploy** the stack. First `up` runs migrations automatically.

6. **Authorise Twitch** — open `https://overlay.philippedelaval.dev/twitch/oauth/redirect` once, click Authoriser. The user-access-token is persisted encrypted.

### Stack architecture

```
                ┌─ Traefik (existing on homelab) ─┐
(internet) ─────┤                                  ├──── klareg-web     (nginx + php-fpm + Filament)
                │                                  ├──── klareg-reverb  (Laravel Reverb WebSocket)
                └──────────────────────────────────┘
                                                    ┌── klareg-eventsub (twitch:eventsub daemon)
                                (internal network) ─┼── klareg-irc      (twitch:irc daemon)
                                                    └── klareg-db       (Postgres 16)
```

Every service uses the same image (`build: .`); only `CMD` differs. All worker containers wait for Postgres to be healthy, then enter their long-running loop with `restart: unless-stopped`.

## Tests

```bash
cd backend
php artisan test
```

Covers `OverlayApiController` (settings cache, reverb-config secret leak check,
schedule ordering, health, twitch status) and `TwitchApiClient` (token cache,
at-rest encryption, EventSub creation, error paths).

## Security notes

- `REVERB_APP_SECRET` and `TWITCH_CLIENT_SECRET` are server-only. The overlays fetch only the public Reverb key via `/api/overlay/reverb-config`.
- Twitch tokens (`twitch_tokens.access_token`, `refresh_token`) are encrypted at rest via Eloquent's `encrypted` cast. Rotating `APP_KEY` without re-encrypting rows will brick them — truncate the table and let the app fetch a fresh app-access token.
- CORS defaults to `APP_URL` only. Widen explicitly via `CORS_ALLOWED_ORIGINS`.
- Overlays render Twitch-sourced strings (chat usernames, messages, event payloads) exclusively through `textContent` / `createElement` — no `innerHTML` templating of user data.

## Troubleshooting

- **WebSocket won't connect** — check Reverb is running on the port in `REVERB_PORT`, browser console shows connection URL, and CORS origin allows the overlay origin.
- **No chat messages** — `php artisan twitch:irc` must be running; IRC uses anonymous `justinfan` auth, no OAuth needed.
- **EventSub reconnects constantly** — verify `TWITCH_CLIENT_ID`/`SECRET` are valid and `TWITCH_CHANNEL_ID` matches the authenticated user.
- **Filament edits don't update overlays live** — Reverb must be running; observer broadcast fails silently otherwise (settings still persist and the overlay picks them up on next `fetchSettings()`).
