# Deploying to Coolify

This guide deploys the app to [Coolify](https://coolify.io) using the `compose.yaml` and `Dockerfile` in the project root.

## How it works

One Docker image (`serversideup/php` FrankenPHP) is built once and runs as four containers, each with a different command:

| Service     | What it does                           | Command                 | Needs a domain |
| ----------- | -------------------------------------- | ----------------------- | -------------- |
| `web`       | Serves the app (FrankenPHP, port 8080) | default                 | Yes            |
| `horizon`   | Processes queued jobs                  | `artisan horizon`       | No             |
| `scheduler` | Runs scheduled tasks                   | `artisan schedule:work` | No             |
| `reverb`    | WebSocket server (port 8000)           | `artisan reverb:start`  | Yes            |

PostgreSQL and Redis are **not** part of `compose.yaml`. They run as their own Coolify resources.

Only `web` runs migrations on startup (`AUTORUN_ENABLED=true`), so they never run more than once per deploy.

## Prerequisites

- A server connected to Coolify.
- Two DNS records pointing to that server, for example:
    - `app.example.com` for the app
    - `ws.example.com` for Reverb (WebSockets)

## Step 1: Create PostgreSQL and Redis

1. In your Coolify project, click **+ New** → **Database** → **PostgreSQL**. Deploy it.
2. Click **+ New** → **Database** → **Redis**. Deploy it.
3. Open each one and note its **internal hostname**, **username**, **password**, and **database name** (for Postgres). Do not make them public.

## Step 2: Create the app resource

1. In the project and environment, click **+ New**.
2. Select the source: a GitHub App, a deploy key (private repository) or a public repository. For a public repository, paste the HTTPS URL and click **Check Repository**.
3. Go to **Configuration > General** and set:

    | Field                       | Value            |
    | --------------------------- | ---------------- |
    | **Build Pack**              | `Docker Compose` |
    | **Base Directory**          | `/`              |
    | **Docker Compose Location** | `/compose.yaml`  |

4. Click **Save**. Coolify loads the **Docker Compose Content** and lists the four services: `web`, `horizon`, `scheduler`, `reverb`.

## Step 3: Connect to the database network

The app must reach the Postgres and Redis resources.

Go to **Configuration > Advanced** and enable **Connect To Predefined Network**.

If you skip this, the containers cannot resolve `DB_HOST` or `REDIS_HOST` and will fail on startup.

## Step 4: Set the domains

Go to **Configuration > General**. Each service has its own **Domains** field. Fill in two of them:

| Service  | **Domains** field              |
| -------- | ------------------------------ |
| `web`    | `https://app.example.com:8080` |
| `reverb` | `https://ws.example.com:8000`  |

Leave the **Domains** field of `horizon` and `scheduler` empty.

Example of how the **General** page looks after this step:

```text
Build Pack               Docker Compose
Base Directory           /
Docker Compose Location  /compose.yaml

Service: web        Domains  https://app.example.com:8080
Service: horizon    Domains  (empty)
Service: scheduler  Domains  (empty)
Service: reverb     Domains  https://ws.example.com:8000
```

> **About the `:8080` and `:8000` suffix.** The suffix is the port **inside the container**. It does not open a port on your server and it does not conflict with other apps on the same server. Visitors still use `https://app.example.com` on port 443. The suffix is necessary because the image exposes several ports (`2019`, `8080`, `8443`) and Reverb listens on `8000`, which the image does not expose. Without it, the proxy can route to the wrong port and return a 502.

## Step 5: Set the environment variables

Generate the secrets on your machine:

```bash
php artisan key:generate --show
```

```bash
openssl rand -hex 16
```

Run the `openssl` command three times, one each for `REVERB_APP_ID`, `REVERB_APP_KEY` and `REVERB_APP_SECRET`.

Go to **Configuration > Environment Variables**. The easiest way is **Developer view**: paste all the values at once in `.env` format, then click **Save**.

```dotenv
APP_NAME=AroundThat
APP_KEY=base64:your-generated-key
APP_URL=https://app.example.com

DB_HOST=your-postgres-internal-hostname
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-postgres-password

REDIS_HOST=your-redis-internal-hostname
REDIS_PASSWORD=your-redis-password

REVERB_APP_ID=your-random-id
REVERB_APP_KEY=your-random-key
REVERB_APP_SECRET=your-random-secret
REVERB_PUBLIC_HOST=ws.example.com
```

Then switch to **Normal view** and check the scope of each variable. Every variable needs **Runtime Variable** enabled. `APP_NAME`, `REVERB_APP_KEY` and `REVERB_PUBLIC_HOST` also need **Build Variable** enabled:

| Variable             | **Build Variable** | **Runtime Variable** |
| -------------------- | :----------------: | :------------------: |
| `APP_NAME`           |         ✅         |          ✅          |
| `REVERB_APP_KEY`     |         ✅         |          ✅          |
| `REVERB_PUBLIC_HOST` |         ✅         |          ✅          |
| All other variables  |         ⬜         |          ✅          |

Reference for each variable:

| Variable             | Example                    | Notes                           |
| -------------------- | -------------------------- | ------------------------------- |
| `APP_NAME`           | `AroundThat`               | Also used at build time         |
| `APP_KEY`            | `base64:...`               | From `key:generate --show`      |
| `APP_URL`            | `https://app.example.com`  |                                 |
| `DB_HOST`            | Postgres internal hostname | From Step 1                     |
| `DB_DATABASE`        | `postgres`                 | From Step 1                     |
| `DB_USERNAME`        | `postgres`                 | From Step 1                     |
| `DB_PASSWORD`        | ...                        | From Step 1                     |
| `REDIS_HOST`         | Redis internal hostname    | From Step 1                     |
| `REDIS_PASSWORD`     | ...                        | From Step 1                     |
| `REVERB_APP_ID`      | random                     |                                 |
| `REVERB_APP_KEY`     | random                     | **Also mark as build variable** |
| `REVERB_APP_SECRET`  | random                     |                                 |
| `REVERB_PUBLIC_HOST` | `ws.example.com`           | **Also mark as build variable** |

Optional, with defaults:

| Variable               | Default | Notes                         |
| ---------------------- | ------- | ----------------------------- |
| `DB_CONNECTION`        | `pgsql` | Use `mysql` for MySQL/MariaDB |
| `DB_PORT`              | `5432`  | Use `3306` for MySQL/MariaDB  |
| `REDIS_PORT`           | `6379`  |                               |
| `REVERB_PUBLIC_PORT`   | `443`   | Build variable                |
| `REVERB_PUBLIC_SCHEME` | `https` | Build variable                |

> **Why build variables?** Vite compiles `VITE_REVERB_*` values into the JavaScript during `pnpm run build`. If `REVERB_APP_KEY` or `REVERB_PUBLIC_HOST` is missing at build time, the browser cannot connect to Reverb. After you change them, you must **redeploy** (rebuild), not only restart.

You do not need to set `REVERB_HOST`, `REVERB_PORT`, `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER` or `BROADCAST_CONNECTION`. `compose.yaml` sets them.

## Step 6: Deploy

1. Make sure Postgres and Redis are running.
2. Click **Deploy**.
3. Wait until all four services show **healthy**.

The first deploy runs the migrations in the `web` container.

## Step 7: Check it works

- Open `https://app.example.com/up`. It returns HTTP 200.
- Open the app in a browser. In the developer tools **Network** tab, filter by **WS**. A connection to `wss://ws.example.com/app/...` shows status `101`.
- Check the logs of the `horizon` service for `Horizon started successfully`.

## Horizon dashboard

`/horizon` is blocked in production by default. To allow access, add the allowed users to the `viewHorizon` gate in `app/Providers/HorizonServiceProvider.php`.

## Troubleshooting

| Problem                                          | Fix                                                                                                              |
| ------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------- |
| Containers fail with a DB/Redis connection error | Enable **Configuration > Advanced > Connect To Predefined Network** (Step 3) and check `DB_HOST` / `REDIS_HOST`. |
| WebSocket does not connect                       | Check that **Build Variable** is enabled on `REVERB_APP_KEY` and `REVERB_PUBLIC_HOST`, then redeploy.            |
| WebSocket returns 404 or 502                     | Check the `reverb` domain ends with `:8000`.                                                                     |
| App returns 502                                  | Check the `web` domain ends with `:8080`.                                                                        |
| Jobs stay pending                                | Check the `horizon` service is running and healthy.                                                              |
| Scheduled tasks do not run                       | Check the `scheduler` service is running and healthy.                                                            |

## Test the stack locally

You can build and run the four app services on your machine. You must supply your own Postgres and Redis through `DB_HOST` and `REDIS_HOST`.

```bash
docker compose --env-file .env.production build
```

```bash
docker compose --env-file .env.production up -d --wait
```
