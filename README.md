# Khmer Calendar API

A Laravel JSON API for Khmer calendar data, Cambodia public national holidays, notes, normal events, holiday events, and 26th-to-25th work schedules.

The API includes a PHP port of the Khmer lunar calendar logic from `Jeng12/Khmer_Calender_GL`, so clients can request Gregorian dates and receive Khmer lunar date information, Buddhist Era, zodiac, moon phase, built-in holiday names, and auspicious-day markers.

## Quick Start

Install dependencies:

```bash
composer install
```

Create and configure the environment file:

```bash
cp .env.example .env
php artisan key:generate
```

Configure MySQL in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_calender
DB_USERNAME=root
DB_PASSWORD=
APP_TIMEZONE=Asia/Phnom_Penh
```

For TiDB Cloud or another MySQL provider that requires TLS, also set:

```env
MYSQL_ATTR_SSL_CA=C:\laragon\etc\ssl\cacert.pem
```

On GitHub Actions or Ubuntu servers, use:

```env
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
```

Run migrations:

```bash
php artisan migrate
```

Seed the shared public holiday table:

```bash
php artisan db:seed --class=PublicHolidaySeeder
```

Start the API server:

```bash
php artisan serve
```

Default local base URL:

```text
http://127.0.0.1:8000/api/v1
```

Run tests:

```bash
php artisan test
```

## API Rules

- Register or log in to get a bearer token before calling API endpoints.
- Send protected requests with `Authorization: Bearer <token>`.
- All requests and responses are JSON.
- Dates use `YYYY-MM-DD`.
- Date-times can use `YYYY-MM-DD HH:mm:ss` or ISO 8601.
- App timezone defaults to `Asia/Phnom_Penh`.
- Validation errors return HTTP `422`.
- Missing or invalid tokens return HTTP `401`.
- Delete endpoints return HTTP `204`.

## Authentication

### Register

```bash
curl -X POST "http://127.0.0.1:8000/api/v1/auth/register" \
  -H "Content-Type: application/json" \
  -d "{\"name\":\"Student\",\"email\":\"student@example.com\",\"password\":\"password123\",\"device_name\":\"laptop\"}"
```

### Login

```bash
curl -X POST "http://127.0.0.1:8000/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"student@example.com\",\"password\":\"password123\"}"
```

Both endpoints return `data.token`. Use it on every protected request:

```bash
curl "http://127.0.0.1:8000/api/v1/notes" \
  -H "Authorization: Bearer <token>"
```

### Current User And Logout

```http
GET /api/v1/auth/me
POST /api/v1/auth/logout
```

All saved notes, events, holiday events, and work schedule records are scoped to the authenticated user.

## Calendar Endpoints

### Convert One Date

```http
GET /api/v1/calendar/convert?date=2026-04-14
```

Example:

```bash
curl "http://127.0.0.1:8000/api/v1/calendar/convert?date=2026-04-14"
```

Response shape:

```json
{
  "data": {
    "date": "2026-04-14",
    "year": 2026,
    "month": 4,
    "day": 14,
    "day_of_week": "...",
    "day_of_week_en": "Tuesday",
    "day_of_week_short": "...",
    "lunar_day": 12,
    "is_waxing": false,
    "lunar_day_name": "...",
    "lunar_month_name": "...",
    "zodiac": "...",
    "buddhist_era": 2569,
    "moon_phase": "🌘",
    "holiday": "...",
    "is_auspicious": false,
    "auspicious_type": null
  }
}
```

### Day View With Database Overlays

Returns computed Khmer calendar data plus public national holidays, notes, events, holiday events, and work shift for the date.

```http
GET /api/v1/calendar/day?date=2026-06-27
```

Example:

```bash
curl "http://127.0.0.1:8000/api/v1/calendar/day?date=2026-06-27"
```

### Month View With Database Overlays

```http
GET /api/v1/calendar/month?year=2026&month=6
```

Example:

```bash
curl "http://127.0.0.1:8000/api/v1/calendar/month?year=2026&month=6"
```

The response contains `data.days`, one item per Gregorian day in that month. Each day includes a `public_holidays` array.

## Public National Holidays API

Public national holidays are stored in the shared `public_holidays` table. Seed the Cambodia 2020-2026 public holiday calendar with `php artisan db:seed --class=PublicHolidaySeeder`.

### List Public Holidays

```http
GET /api/v1/public-holidays?year=2026
GET /api/v1/public-holidays?year=2025
GET /api/v1/public-holidays?date=2026-12-29
GET /api/v1/public-holidays?from=2026-11-23&to=2026-11-25
```

Example:

```bash
curl "http://127.0.0.1:8000/api/v1/public-holidays?year=2026"
```

Response items include:

```json
{
  "id": "kh-2026-peace-day-in-cambodia-1",
  "country_code": "KH",
  "country": "Cambodia",
  "date": "2026-12-29",
  "name_km": null,
  "name_en": "Peace Day in Cambodia",
  "type": "public_national",
  "is_public": true,
  "is_national": true,
  "start_date": "2026-12-29",
  "end_date": "2026-12-29",
  "day_number": 1,
  "duration_days": 1,
  "source": "Royal Government of Cambodia public holidays for 2026 / MLVT Prakas No. 216/25",
  "source_url": "https://www.kbprasacbank.com.kh/en/media/public-holiday/"
}
```

## Notes API

### List Notes

```http
GET /api/v1/notes
GET /api/v1/notes?date=2026-06-27
GET /api/v1/notes?from=2026-06-01&to=2026-06-30
```

### Create Note

```bash
curl -X POST "http://127.0.0.1:8000/api/v1/notes" \
  -H "Content-Type: application/json" \
  -d "{\"date\":\"2026-06-27\",\"text\":\"Prepare calendar API homework\"}"
```

Body:

```json
{
  "date": "2026-06-27",
  "text": "Prepare calendar API homework"
}
```

### Read, Update, Delete Note

```http
GET /api/v1/notes/{id}
PATCH /api/v1/notes/{id}
DELETE /api/v1/notes/{id}
```

Update example:

```bash
curl -X PATCH "http://127.0.0.1:8000/api/v1/notes/1" \
  -H "Content-Type: application/json" \
  -d "{\"text\":\"Updated note\"}"
```

## Events API

Normal events are one-time events. They can be timed or all-day.

### List Events

```http
GET /api/v1/events
GET /api/v1/events?date=2026-06-27
GET /api/v1/events?from=2026-06-01&to=2026-06-30
```

### Create Event

```bash
curl -X POST "http://127.0.0.1:8000/api/v1/events" \
  -H "Content-Type: application/json" \
  -d "{\"title\":\"Class demo\",\"starts_at\":\"2026-06-27 09:00:00\",\"ends_at\":\"2026-06-27 10:00:00\",\"location\":\"IT STEP\",\"color\":\"#1f7a8c\",\"reminder_minutes_before\":30}"
```

Body:

```json
{
  "title": "Class demo",
  "description": "Demo the calendar API",
  "starts_at": "2026-06-27 09:00:00",
  "ends_at": "2026-06-27 10:00:00",
  "all_day": false,
  "location": "IT STEP",
  "color": "#1f7a8c",
  "reminder_minutes_before": 30
}
```

### Read, Update, Delete Event

```http
GET /api/v1/events/{id}
PATCH /api/v1/events/{id}
DELETE /api/v1/events/{id}
```

## Holiday Events API

Use this API for custom holiday events saved in the database. Built-in Khmer calendar holidays are returned by the calendar endpoints from computed calendar logic.

### List Holiday Events

```http
GET /api/v1/holiday-events
GET /api/v1/holiday-events?date=2026-11-09
GET /api/v1/holiday-events?from=2026-11-01&to=2026-11-30
GET /api/v1/holiday-events?type=school
```

### Create Holiday Event

```bash
curl -X POST "http://127.0.0.1:8000/api/v1/holiday-events" \
  -H "Content-Type: application/json" \
  -d "{\"name_en\":\"School Break\",\"date\":\"2026-11-09\",\"type\":\"school\",\"is_recurring_yearly\":true}"
```

Body:

```json
{
  "name_km": null,
  "name_en": "School Break",
  "date": "2026-11-09",
  "end_date": null,
  "type": "school",
  "source": "manual",
  "is_fixed": false,
  "is_recurring_yearly": true,
  "description": "No class",
  "notes": "Shown every year on the same month/day"
}
```

### Read, Update, Delete Holiday Event

```http
GET /api/v1/holiday-events/{id}
PATCH /api/v1/holiday-events/{id}
DELETE /api/v1/holiday-events/{id}
```

## Work Schedule API

The work schedule matches the Khmer Calendar Android app model:

- A work cycle starts on the 26th day of a month.
- A work cycle ends on the 25th day of the next month.
- Each cycle accepts up to 31 day assignments.
- Each assignment is a shift template `code`, shift template `id`, or `null` for a day off.
- Overnight shifts are supported.
- Back-to-back shifts with no rest are marked as `blocked` in materialized day output.

### Get Settings And Shift Templates

```http
GET /api/v1/work-schedule/settings
```

Example:

```bash
curl "http://127.0.0.1:8000/api/v1/work-schedule/settings"
```

Default templates are created automatically if none exist:

```json
[
  {
    "code": "day",
    "name": "Day",
    "start_time": "07:30",
    "end_time": "19:30"
  },
  {
    "code": "night",
    "name": "Night",
    "start_time": "19:30",
    "end_time": "07:30"
  }
]
```

### Update Settings And Shift Templates

```bash
curl -X PUT "http://127.0.0.1:8000/api/v1/work-schedule/settings" \
  -H "Content-Type: application/json" \
  -d "{\"system_type\":3,\"remind\":true,\"reminder_minutes_before\":30,\"shift_templates\":[{\"code\":\"s1\",\"name\":\"Shift 1\",\"start_time\":\"07:30\",\"end_time\":\"15:30\",\"sort_order\":1},{\"code\":\"s2\",\"name\":\"Shift 2\",\"start_time\":\"15:30\",\"end_time\":\"23:30\",\"sort_order\":2},{\"code\":\"s3\",\"name\":\"Shift 3\",\"start_time\":\"23:30\",\"end_time\":\"07:30\",\"sort_order\":3}]}"
```

Body:

```json
{
  "system_type": 3,
  "remind": true,
  "reminder_minutes_before": 30,
  "shift_templates": [
    {
      "code": "s1",
      "name": "Shift 1",
      "start_time": "07:30",
      "end_time": "15:30",
      "sort_order": 1
    },
    {
      "code": "s2",
      "name": "Shift 2",
      "start_time": "15:30",
      "end_time": "23:30",
      "sort_order": 2
    },
    {
      "code": "s3",
      "name": "Shift 3",
      "start_time": "23:30",
      "end_time": "07:30",
      "sort_order": 3
    }
  ]
}
```

### Get One Cycle

`cycle_start_date` must be the 26th day of a month.

```http
GET /api/v1/work-schedule/cycles/2026-06-26
```

### Save One Cycle

```bash
curl -X PUT "http://127.0.0.1:8000/api/v1/work-schedule/cycles/2026-06-26" \
  -H "Content-Type: application/json" \
  -d "{\"assignments\":[\"night\",\"day\",null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null]}"
```

Body:

```json
{
  "assignments": [
    "night",
    "day",
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null
  ]
}
```

### Materialize Work Days

Returns actual work dates, shift start/end datetimes, and `blocked` status.

```http
GET /api/v1/work-schedule/days?from=2026-06-26&to=2026-06-30
```

Example:

```bash
curl "http://127.0.0.1:8000/api/v1/work-schedule/days?from=2026-06-26&to=2026-06-30"
```

## Example Calendar Workflow

1. Create a note:

```bash
curl -X POST "http://127.0.0.1:8000/api/v1/notes" \
  -H "Content-Type: application/json" \
  -d "{\"date\":\"2026-06-27\",\"text\":\"Review lesson notes\"}"
```

2. Create an event:

```bash
curl -X POST "http://127.0.0.1:8000/api/v1/events" \
  -H "Content-Type: application/json" \
  -d "{\"title\":\"Study PHP API\",\"starts_at\":\"2026-06-27 14:00:00\",\"ends_at\":\"2026-06-27 16:00:00\"}"
```

3. Read the combined day view:

```bash
curl "http://127.0.0.1:8000/api/v1/calendar/day?date=2026-06-27"
```

## Main Tables

- `notes`
- `events`
- `holiday_events`
- `public_holidays`
- `work_shift_templates`
- `work_schedule_settings`
- `work_schedule_cycles`
- `work_schedule_days`

## Project Commands

```bash
php artisan migrate
php artisan route:list --path=api/v1
php artisan test
php artisan serve
```

## Deploy To Vercel

This project includes Vercel configuration for a Laravel API:

```text
vercel.json
api/index.php
.vercelignore
```

The `vercel.json` file fixes the common Vercel error:

```text
No Output Directory named "dist" found after the Build completed.
```

Laravel does not build to `dist`. This API uses:

```json
{
  "outputDirectory": "public"
}
```

### Vercel Project Settings

In Vercel, configure the project like this:

```text
Framework Preset: Other
Build Command: npm run build
Output Directory: public
Install Command: npm install
```

The repository root should be the Laravel app root, where `artisan`, `composer.json`, and `vercel.json` exist.

### Required Vercel Environment Variables

Add these in:

```text
Vercel Project -> Settings -> Environment Variables
```

Required:

```text
APP_KEY
APP_URL
DB_CONNECTION
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
```

Recommended values for TiDB Cloud:

```text
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Phnom_Penh
APP_API_PREFIX=none
DB_CONNECTION=mysql
DB_PORT=4000
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
```

`APP_API_PREFIX` should be `none` on Vercel because Vercel reserves `/api` for serverless functions. Public API URLs still use `/api/v1/...`; Laravel receives them internally as `/v1/...`.

Generate `APP_KEY` locally if needed:

```bash
php artisan key:generate --show
```

### Notes For Vercel

- Do not commit `.env`.
- Do not run migrations during the Vercel build. Use the GitHub Actions migration workflow or run `php artisan migrate --force` locally/server-side.
- Vercel serverless functions have a read-only filesystem, so cache/view paths are configured to `/tmp` in `vercel.json`.

## GitHub Actions: Automatic Migrations

This project includes a migration workflow at:

```text
.github/workflows/laravel-migrations.yml
```

The workflow runs Laravel migrations from the repository root.

It can run in two ways:

- Automatically on pushes to `main` when migration files, `composer.lock`, or the workflow file changes.
- Manually from GitHub Actions with an optional dry-run mode.

### Required GitHub Secrets

In your GitHub repository, open:

```text
Settings -> Environments -> production -> Environment secrets
```

Create these secrets:

```text
APP_KEY
DB_HOST
DB_DATABASE
DB_USERNAME
DB_PASSWORD
```

Optional secrets:

```text
DB_CONNECTION
DB_PORT
MYSQL_ATTR_SSL_CA
```

Recommended values:

```text
DB_CONNECTION=mysql
DB_PORT=3306
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
```

You can also create a `staging` environment with different database secrets.

### Optional GitHub Variables

In the same environment, you may add these variables:

```text
APP_URL
APP_TIMEZONE
```

Default timezone is:

```text
Asia/Phnom_Penh
```

### Run Migrations Manually

1. Go to the GitHub repository.
2. Open the `Actions` tab.
3. Select `Laravel Database Migrations`.
4. Click `Run workflow`.
5. Choose `production` or `staging`.
6. Set `pretend`:
   - `true`: preview SQL only.
   - `false`: run migrations.

### Important Notes

- The production database must be reachable from a GitHub-hosted runner.
- If your database is only available from your server or local machine, use a self-hosted runner or run migrations on the server over SSH instead.
- Keep GitHub environment protection enabled for production so migrations require manual approval.
