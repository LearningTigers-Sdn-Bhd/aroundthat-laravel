# Development

## Prerequisites

- PHP 8.4 and Composer 2
- Node 22 and pnpm
- PostgreSQL (CI uses 18)
- Redis (queues run on Horizon)

## First setup

```bash
composer setup
```

This installs the PHP and JS packages, copies `.env.example` to `.env`, generates `APP_KEY`, runs the migrations and builds the frontend.

Before you run it, check the database settings in `.env`. The defaults are:

```dotenv
DB_CONNECTION=pgsql
DB_DATABASE=db_aroundthat
DB_USERNAME=postgres
```

Create the test database that Pest uses:

```bash
createdb db_aroundthat_testing
```

### Admin account

The admin seeder runs only when both values are set:

```dotenv
ADMIN_SEED_EMAIL=admin@example.com
ADMIN_SEED_PASSWORD=choose-a-password
```

```bash
php artisan db:seed
```

The seeder also creates the default categories.

## Running the app

```bash
composer run dev
```

This runs `php artisan dev`, which starts these processes together:

| Process   | Command                                    |
| --------- | ------------------------------------------ |
| `server`  | `php artisan serve`                        |
| `vite`    | `pnpm run dev`                             |
| `horizon` | `php artisan horizon`                      |
| `reverb`  | `php artisan reverb:start`                 |
| `logs`    | `php artisan pail`                         |
| `types`   | `php artisan typescript:transform --watch` |

Mail goes to the log by default (`MAIL_MAILER=log`). Staff invitations and moderation notices are queued, so Horizon must be running to send them.

## Checks

Run everything CI runs:

```bash
composer ci:check
```

| Step                | Command                             |
| ------------------- | ----------------------------------- |
| JS lint and format  | `pnpm run check` (Vite+ `vp check`) |
| TypeScript          | `pnpm run types:check`              |
| PHP format          | `pint --parallel --test`            |
| PHP static analysis | `phpstan analyse` (level 7)         |
| Tests               | `php artisan test`                  |

While you work, run only the tests you need:

```bash
php artisan test --compact tests/Feature/App/CounterTest.php
```

```bash
php artisan test --compact --filter=redeem
```

Fix PHP formatting on changed files:

```bash
vendor/bin/pint --dirty --format agent
```

Fix JS formatting and lint:

```bash
pnpm run check:fix
```

## Project layout

```text
app/
  Actions/        one class per write use case, grouped by area
  Data/           spatie/laravel-data classes for Inertia props and API output
  Enums/          backed enums; labels use __()
  Http/
    Controllers/
      Admin/      /admin
      App/        /app (the business workspace)
      Api/V1/     /api/v1 (partners)
      Settings/   /settings
    Middleware/   workspace, suspension, forced password change, API capabilities
    Requests/Api/ form requests for the partner API
  Jobs/           queued mail and notices
  Models/         Eloquent models; UUID keys
  Policies/       role abilities per business, admin gate
  Support/        shared logic: Reports, Vouchers, Workspace, OpeningHours, ...
routes/
  web.php         landing, place pages, invitations, workspace chooser
  app.php         /app
  admin.php       /admin
  settings.php    /settings
  api.php         /api/v1
resources/js/
  pages/          Inertia pages, mirroring the route areas
  components/     shared components; ui/ holds shadcn components
  types/          generated from app/Data — do not edit by hand
docs/             this documentation
```

## Conventions

### Backend

- **Controllers stay thin.** They authorize, validate and call an Action. The Action holds the rule and is shared with the API when both need it.
- **Actions that change voucher state lock the row.** Issuing, redeeming and cancelling run in a transaction and lock the voucher or offer row first.
- **Output goes through Data classes.** Do not pass Eloquent models to Inertia. The Data class is also the source of the TypeScript type.
- **Every owner-visible change is logged** with spatie/laravel-activitylog. Admin actions on someone else's data record a reason.
- **Enum keys are TitleCase** (`TemporaryPassword`), values are snake_case.
- Use `php artisan make:*` with `--no-interaction` to create files.

### Frontend

- Inertia 3 with React 19 and the React Compiler.
- UI components come from shadcn with the `base-vega` style (Base UI, not Radix). Toasts use the Base UI `toast` component, not sonner. `sidebar`, `toast`, `select`, `dialog`, `sheet` and `alert-dialog` carry local edits — after `shadcn add --overwrite`, re-apply them. `Select` requires `items`, so the trigger shows labels instead of raw values.
- Routes come from Wayfinder: import from `@/routes` or `@/actions`, never hard-code a URL.
- Create and edit forms open as routed sheets and dialogs with `@inertiaui/modal-react`.
- Lists use the shared `DataTable` component. Page tabs, status badges, reason dialogs and the activity timeline are shared too — check `resources/js/components` before you write a new one.

### Types

`app/Data` classes marked for TypeScript generate `resources/js/types/generated.d.ts`. The `types` dev process keeps it up to date. If it is not running:

```bash
php artisan typescript:transform
```

Commit the generated file with the Data change.

### Tests

- Pest 5, feature tests first. Unit tests only for pure classes such as `VoucherCode`.
- Tests run against PostgreSQL, not SQLite, so locking and JSON queries behave as in production.
- Use model factories and their states.
- Test the rule and its important failure modes. Do not test the framework.

## Commits

Conventional commits, with the area as the scope and a plain-language subject:

```text
feat(counter): scan voucher QR codes with the camera
fix(reports): stretch the report tiles across the full row
```
