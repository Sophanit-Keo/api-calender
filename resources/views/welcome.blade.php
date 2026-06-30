@php
    $baseUrl = rtrim(url('/api/v1'), '/');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Developer documentation for the Khmer Calendar API.">

    <title>Khmer Calendar API Documentation</title>

    @fonts

    <style>
        :root {
            color-scheme: light;
            --bg: #f6f7f1;
            --surface: #ffffff;
            --surface-soft: #f1f4ec;
            --ink: #1b211d;
            --muted: #657064;
            --line: #d9dfd3;
            --green: #1f6f4a;
            --teal: #0f766e;
            --gold: #b7791f;
            --red: #b42318;
            --code-bg: #18201d;
            --code-ink: #e8efe7;
            --shadow: 0 12px 34px rgba(31, 42, 35, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 16px;
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        code,
        pre {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }

        .shell {
            max-width: 1240px;
            margin: 0 auto;
            padding: 24px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 0 28px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .brand-mark {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            color: var(--green);
            font-weight: 800;
            box-shadow: var(--shadow);
        }

        .brand-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 760;
            line-height: 1.2;
        }

        .brand-subtitle {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 0.875rem;
        }

        .top-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .pill-link,
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 8px 13px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            color: var(--ink);
            font-size: 0.9rem;
            font-weight: 650;
        }

        .button.primary {
            border-color: var(--green);
            background: var(--green);
            color: #ffffff;
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(300px, 0.95fr);
            gap: 24px;
            align-items: stretch;
            padding: 30px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow);
        }

        .hero h1 {
            max-width: 760px;
            margin: 0;
            font-size: clamp(2rem, 5vw, 4.4rem);
            line-height: 1.02;
            font-weight: 820;
        }

        .lead {
            max-width: 760px;
            margin: 18px 0 0;
            color: #3d483f;
            font-size: 1.1rem;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 24px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 28px;
        }

        .meta-item {
            min-height: 92px;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface-soft);
        }

        .meta-label {
            display: block;
            color: var(--muted);
            font-size: 0.75rem;
            font-weight: 760;
            text-transform: uppercase;
        }

        .meta-value {
            display: block;
            margin-top: 8px;
            font-size: 1rem;
            font-weight: 780;
            overflow-wrap: anywhere;
        }

        .visual-panel {
            display: grid;
            align-content: space-between;
            gap: 18px;
            min-height: 100%;
            padding: 20px;
            border: 1px solid #cdd8cf;
            border-radius: 8px;
            background:
                linear-gradient(135deg, rgba(31, 111, 74, 0.1), transparent 34%),
                linear-gradient(0deg, #f8faf5, #ffffff);
        }

        .calendar-mini {
            display: grid;
            grid-template-columns: repeat(7, minmax(26px, 1fr));
            gap: 6px;
        }

        .calendar-mini span {
            display: grid;
            min-height: 42px;
            place-items: center;
            border: 1px solid var(--line);
            border-radius: 6px;
            background: #ffffff;
            color: #445047;
            font-size: 0.78rem;
            font-weight: 720;
        }

        .calendar-mini .muted {
            color: #9aa49a;
            background: #f6f7f1;
        }

        .calendar-mini .active {
            border-color: var(--green);
            background: var(--green);
            color: #ffffff;
        }

        .calendar-mini .holiday {
            border-color: #e0b45d;
            background: #fff6df;
            color: #7b4b00;
        }

        .endpoint-preview {
            display: grid;
            gap: 8px;
        }

        .preview-row {
            display: grid;
            grid-template-columns: 64px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            padding: 9px 10px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #ffffff;
            font-size: 0.86rem;
        }

        .method {
            display: inline-flex;
            justify-content: center;
            padding: 3px 7px;
            border-radius: 6px;
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 780;
        }

        .method.get {
            background: var(--teal);
        }

        .method.post {
            background: var(--green);
        }

        .method.put {
            background: var(--gold);
        }

        .method.patch {
            background: #8a5cf6;
        }

        .method.delete {
            background: var(--red);
        }

        .layout {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 24px;
            margin-top: 24px;
            align-items: start;
        }

        .sidebar {
            position: sticky;
            top: 18px;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow);
        }

        .sidebar h2 {
            margin: 0 0 12px;
            font-size: 0.9rem;
            font-weight: 820;
        }

        .sidebar nav {
            display: grid;
            gap: 4px;
        }

        .sidebar a {
            display: block;
            padding: 7px 8px;
            border-radius: 6px;
            color: #4e5a51;
            font-size: 0.9rem;
        }

        .sidebar a:hover,
        .sidebar a:focus {
            background: var(--surface-soft);
            color: var(--ink);
        }

        .content {
            display: grid;
            gap: 18px;
        }

        .section {
            padding: 24px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow);
        }

        .section h2 {
            margin: 0;
            font-size: clamp(1.35rem, 3vw, 2rem);
            line-height: 1.16;
        }

        .section h3 {
            margin: 26px 0 10px;
            font-size: 1.08rem;
            line-height: 1.25;
        }

        .section p {
            margin: 10px 0 0;
            color: #47534a;
        }

        .grid-2,
        .grid-3 {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }

        .grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .info-box {
            padding: 15px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fbfcf8;
        }

        .info-box strong {
            display: block;
            margin-bottom: 5px;
            font-size: 0.95rem;
        }

        .info-box p {
            margin: 0;
            font-size: 0.92rem;
        }

        .steps {
            display: grid;
            gap: 10px;
            margin: 16px 0 0;
            padding: 0;
            list-style: none;
            counter-reset: steps;
        }

        .steps li {
            position: relative;
            padding: 14px 14px 14px 54px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fbfcf8;
        }

        .steps li::before {
            position: absolute;
            left: 14px;
            top: 14px;
            display: grid;
            width: 28px;
            height: 28px;
            place-items: center;
            border-radius: 6px;
            background: var(--green);
            color: #ffffff;
            content: counter(steps);
            counter-increment: steps;
            font-size: 0.85rem;
            font-weight: 820;
        }

        .endpoint-group {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .endpoint {
            display: grid;
            grid-template-columns: 86px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fbfcf8;
        }

        .endpoint code {
            display: block;
            margin-top: 2px;
            color: #25312a;
            font-size: 0.95rem;
            overflow-wrap: anywhere;
        }

        .endpoint p {
            margin: 5px 0 0;
            font-size: 0.92rem;
        }

        .table-wrap {
            margin-top: 16px;
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: 8px;
        }

        table {
            width: 100%;
            min-width: 680px;
            border-collapse: collapse;
            background: #ffffff;
        }

        th,
        td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: top;
            font-size: 0.92rem;
        }

        th {
            background: var(--surface-soft);
            color: #2d3831;
            font-size: 0.78rem;
            font-weight: 820;
            text-transform: uppercase;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        pre {
            margin: 14px 0 0;
            padding: 16px;
            overflow-x: auto;
            border-radius: 8px;
            background: var(--code-bg);
            color: var(--code-ink);
            font-size: 0.88rem;
            line-height: 1.55;
        }

        .inline-code {
            padding: 2px 6px;
            border: 1px solid #d6ddd2;
            border-radius: 6px;
            background: #f1f4ec;
            color: #233029;
            font-size: 0.9em;
        }

        .note {
            margin-top: 16px;
            padding: 14px;
            border-left: 4px solid var(--gold);
            border-radius: 8px;
            background: #fff8e7;
            color: #5b4215;
        }

        .footer {
            padding: 26px 0 10px;
            color: var(--muted);
            font-size: 0.9rem;
            text-align: center;
        }

        @media (max-width: 980px) {
            .hero,
            .layout,
            .grid-3 {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
            }
        }

        @media (max-width: 720px) {
            .shell {
                padding: 16px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .top-links {
                justify-content: flex-start;
            }

            .hero,
            .section {
                padding: 18px;
            }

            .meta-grid,
            .grid-2 {
                grid-template-columns: 1fr;
            }

            .endpoint {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <header class="topbar">
            <a class="brand" href="#top" aria-label="Khmer Calendar API documentation home">
                <span class="brand-mark">KC</span>
                <span>
                    <span class="brand-title">Khmer Calendar API</span>
                    <span class="brand-subtitle">Calendar, notes, events, holidays, and work schedules</span>
                </span>
            </a>
            <div class="top-links" aria-label="Documentation shortcuts">
                <a class="pill-link" href="#quick-start">Quick Start</a>
                <a class="pill-link" href="#endpoints">Endpoints</a>
                <a class="pill-link" href="#integration">Integration</a>
            </div>
        </header>

        <main id="top">
            <section class="hero" aria-labelledby="page-title">
                <div>
                    <h1 id="page-title">Developer guide for the Khmer Calendar API</h1>
                    <p class="lead">
                        Build calendar experiences with Khmer lunar date conversion, user-owned notes, normal events, custom holiday events, and 26th-to-25th work schedule cycles. All saved user data is protected by bearer token authentication.
                    </p>
                    <div class="hero-actions">
                        <a class="button primary" href="#authentication">Get an API token</a>
                        <a class="button" href="#examples">View examples</a>
                    </div>
                    <div class="meta-grid" aria-label="API facts">
                        <div class="meta-item">
                            <span class="meta-label">Base URL</span>
                            <span class="meta-value">{{ $baseUrl }}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Auth</span>
                            <span class="meta-value">Bearer token</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Timezone</span>
                            <span class="meta-value">Asia/Phnom_Penh</span>
                        </div>
                    </div>
                </div>

                <aside class="visual-panel" aria-label="API overview preview">
                    <div class="calendar-mini" aria-hidden="true">
                        <span class="muted">30</span><span>1</span><span>2</span><span>3</span><span>4</span><span class="holiday">5</span><span>6</span>
                        <span>7</span><span>8</span><span>9</span><span>10</span><span>11</span><span>12</span><span>13</span>
                        <span>14</span><span>15</span><span class="active">16</span><span>17</span><span>18</span><span>19</span><span>20</span>
                    </div>
                    <div class="endpoint-preview">
                        <div class="preview-row"><span class="method post">POST</span><code>/auth/login</code></div>
                        <div class="preview-row"><span class="method get">GET</span><code>/calendar/day</code></div>
                        <div class="preview-row"><span class="method post">POST</span><code>/notes</code></div>
                        <div class="preview-row"><span class="method put">PUT</span><code>/work-schedule/cycles/{date}</code></div>
                    </div>
                </aside>
            </section>

            <div class="layout">
                <aside class="sidebar" aria-label="Page navigation">
                    <h2>Documentation</h2>
                    <nav>
                        <a href="#overview">Overview</a>
                        <a href="#quick-start">Quick Start</a>
                        <a href="#authentication">Authentication</a>
                        <a href="#requests">Requests</a>
                        <a href="#endpoints">Endpoint Reference</a>
                        <a href="#examples">Examples</a>
                        <a href="#errors">Errors</a>
                        <a href="#integration">Integration Steps</a>
                    </nav>
                </aside>

                <div class="content">
                    <section class="section" id="overview" aria-labelledby="overview-title">
                        <h2 id="overview-title">Overview</h2>
                        <p>
                            The API returns JSON for Khmer calendar calculations and user-specific calendar overlays. Calendar conversion can calculate Gregorian date details, Khmer lunar fields, Buddhist Era, zodiac, moon phase, built-in holiday names, and auspicious-day markers. Authenticated users can store their own notes, events, custom holiday records, and work schedule settings without mixing data with other accounts.
                        </p>
                        <div class="grid-3">
                            <div class="info-box">
                                <strong>Computed calendar data</strong>
                                <p>Convert one date, inspect one day, or fetch a full Gregorian month with Khmer lunar metadata.</p>
                            </div>
                            <div class="info-box">
                                <strong>User-owned overlays</strong>
                                <p>Notes, events, holiday events, and work schedule records are scoped to the authenticated user.</p>
                            </div>
                            <div class="info-box">
                                <strong>Consistent JSON shape</strong>
                                <p>Successful responses use a <code class="inline-code">data</code> property. Validation failures include field errors.</p>
                            </div>
                        </div>
                    </section>

                    <section class="section" id="quick-start" aria-labelledby="quick-start-title">
                        <h2 id="quick-start-title">Quick Start</h2>
                        <ol class="steps">
                            <li>
                                <strong>Create an account or log in.</strong>
                                <p>Use <code class="inline-code">POST /auth/register</code> or <code class="inline-code">POST /auth/login</code> to receive a bearer token.</p>
                            </li>
                            <li>
                                <strong>Send the token on every API call.</strong>
                                <p>Add <code class="inline-code">Authorization: Bearer &lt;token&gt;</code> and <code class="inline-code">Accept: application/json</code>.</p>
                            </li>
                            <li>
                                <strong>Read or write calendar data.</strong>
                                <p>Call endpoints under <code class="inline-code">{{ $baseUrl }}</code>. User-created records are visible only to the token owner.</p>
                            </li>
                        </ol>

                        <h3>First request</h3>
                        <pre><code>curl -X POST "{{ $baseUrl }}/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Student",
    "email": "student@example.com",
    "password": "password123",
    "device_name": "laptop"
  }'</code></pre>

                        <h3>Use the returned token</h3>
                        <pre><code>curl "{{ $baseUrl }}/calendar/day?date=2026-06-27" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer &lt;token&gt;"</code></pre>
                    </section>

                    <section class="section" id="authentication" aria-labelledby="authentication-title">
                        <h2 id="authentication-title">Authentication</h2>
                        <p>
                            Authentication uses opaque bearer tokens. Tokens are returned once at register/login time, stored hashed in the database, and revoked when you call logout with the same token.
                        </p>

                        <div class="endpoint-group">
                            <div class="endpoint">
                                <span class="method post">POST</span>
                                <div>
                                    <code>/auth/register</code>
                                    <p>Create a user and return a new API token.</p>
                                </div>
                            </div>
                            <div class="endpoint">
                                <span class="method post">POST</span>
                                <div>
                                    <code>/auth/login</code>
                                    <p>Verify credentials and return a new API token.</p>
                                </div>
                            </div>
                            <div class="endpoint">
                                <span class="method get">GET</span>
                                <div>
                                    <code>/auth/me</code>
                                    <p>Return the authenticated user for the current token.</p>
                                </div>
                            </div>
                            <div class="endpoint">
                                <span class="method post">POST</span>
                                <div>
                                    <code>/auth/logout</code>
                                    <p>Revoke the current bearer token. Returns <code class="inline-code">204 No Content</code>.</p>
                                </div>
                            </div>
                        </div>

                        <h3>Login request</h3>
                        <pre><code>curl -X POST "{{ $baseUrl }}/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "student@example.com",
    "password": "password123",
    "device_name": "mobile"
  }'</code></pre>

                        <h3>Login response</h3>
                        <pre><code>{
  "data": {
    "user": {
      "id": 1,
      "name": "Student",
      "email": "student@example.com",
      "email_verified_at": null,
      "created_at": "2026-07-01T00:00:00.000000Z",
      "updated_at": "2026-07-01T00:00:00.000000Z"
    },
    "token": "plain-text-token-returned-once"
  }
}</code></pre>

                        <div class="note">
                            Store the token securely in your client. Do not place bearer tokens in query strings, logs, public repositories, or client-side source that should be shared.
                        </div>
                    </section>

                    <section class="section" id="requests" aria-labelledby="requests-title">
                        <h2 id="requests-title">Requests And Responses</h2>
                        <div class="grid-2">
                            <div class="info-box">
                                <strong>Required headers</strong>
                                <p><code class="inline-code">Accept: application/json</code>, <code class="inline-code">Content-Type: application/json</code> for bodies, and <code class="inline-code">Authorization: Bearer &lt;token&gt;</code>.</p>
                            </div>
                            <div class="info-box">
                                <strong>Date formats</strong>
                                <p>Dates use <code class="inline-code">YYYY-MM-DD</code>. Date-times accept <code class="inline-code">YYYY-MM-DD HH:mm:ss</code> or ISO 8601.</p>
                            </div>
                        </div>

                        <h3>Successful response envelope</h3>
                        <pre><code>{
  "data": {
    "id": 1,
    "date": "2026-06-27",
    "text": "Prepare calendar API homework"
  }
}</code></pre>

                        <h3>Validation error response</h3>
                        <pre><code>{
  "message": "The date field is required.",
  "errors": {
    "date": [
      "The date field is required."
    ]
  }
}</code></pre>
                    </section>

                    <section class="section" id="endpoints" aria-labelledby="endpoints-title">
                        <h2 id="endpoints-title">Endpoint Reference</h2>

                        <h3>Calendar</h3>
                        <div class="endpoint-group">
                            <div class="endpoint"><span class="method get">GET</span><div><code>/calendar/convert?date=2026-04-14</code><p>Return computed Khmer calendar fields for one Gregorian date.</p></div></div>
                            <div class="endpoint"><span class="method get">GET</span><div><code>/calendar/day?date=2026-06-27</code><p>Return calendar fields plus the authenticated user's notes, events, holiday events, and work shift for one date.</p></div></div>
                            <div class="endpoint"><span class="method get">GET</span><div><code>/calendar/month?year=2026&amp;month=6</code><p>Return all days in a Gregorian month with computed calendar data and user overlays.</p></div></div>
                        </div>

                        <h3>Notes</h3>
                        <div class="endpoint-group">
                            <div class="endpoint"><span class="method get">GET</span><div><code>/notes?date=2026-06-27</code><p>List notes. Optional filters: <code class="inline-code">date</code>, <code class="inline-code">from</code>, <code class="inline-code">to</code>.</p></div></div>
                            <div class="endpoint"><span class="method post">POST</span><div><code>/notes</code><p>Create a note with <code class="inline-code">date</code> and <code class="inline-code">text</code>.</p></div></div>
                            <div class="endpoint"><span class="method get">GET</span><div><code>/notes/{id}</code><p>Read one note owned by the authenticated user.</p></div></div>
                            <div class="endpoint"><span class="method patch">PATCH</span><div><code>/notes/{id}</code><p>Update <code class="inline-code">date</code> and/or <code class="inline-code">text</code>.</p></div></div>
                            <div class="endpoint"><span class="method delete">DELETE</span><div><code>/notes/{id}</code><p>Delete one owned note. Returns <code class="inline-code">204</code>.</p></div></div>
                        </div>

                        <h3>Events</h3>
                        <div class="endpoint-group">
                            <div class="endpoint"><span class="method get">GET</span><div><code>/events?from=2026-06-01&amp;to=2026-06-30</code><p>List events overlapping a date, date range, or all events.</p></div></div>
                            <div class="endpoint"><span class="method post">POST</span><div><code>/events</code><p>Create a timed or all-day event.</p></div></div>
                            <div class="endpoint"><span class="method get">GET</span><div><code>/events/{id}</code><p>Read one owned event.</p></div></div>
                            <div class="endpoint"><span class="method patch">PATCH</span><div><code>/events/{id}</code><p>Update event fields.</p></div></div>
                            <div class="endpoint"><span class="method delete">DELETE</span><div><code>/events/{id}</code><p>Delete one owned event. Returns <code class="inline-code">204</code>.</p></div></div>
                        </div>

                        <div class="table-wrap" aria-label="Event request fields">
                            <table>
                                <thead>
                                    <tr><th>Field</th><th>Required</th><th>Description</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>title</code></td><td>Create only</td><td>Event title, maximum 255 characters.</td></tr>
                                    <tr><td><code>starts_at</code></td><td>Create only</td><td>Start date-time.</td></tr>
                                    <tr><td><code>ends_at</code></td><td>No</td><td>End date-time. Must be after or equal to <code>starts_at</code>.</td></tr>
                                    <tr><td><code>description</code></td><td>No</td><td>Long-form event details.</td></tr>
                                    <tr><td><code>all_day</code></td><td>No</td><td>Boolean flag for all-day events.</td></tr>
                                    <tr><td><code>location</code></td><td>No</td><td>Location text, maximum 255 characters.</td></tr>
                                    <tr><td><code>color</code></td><td>No</td><td>Display color text, maximum 20 characters.</td></tr>
                                    <tr><td><code>reminder_minutes_before</code></td><td>No</td><td>Integer from 0 to 10080.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h3>Holiday Events</h3>
                        <div class="endpoint-group">
                            <div class="endpoint"><span class="method get">GET</span><div><code>/holiday-events?date=2027-11-09</code><p>List custom holiday events. Optional filters: <code class="inline-code">date</code>, <code class="inline-code">from</code>, <code class="inline-code">to</code>, <code class="inline-code">type</code>.</p></div></div>
                            <div class="endpoint"><span class="method post">POST</span><div><code>/holiday-events</code><p>Create a custom holiday or yearly recurring holiday.</p></div></div>
                            <div class="endpoint"><span class="method get">GET</span><div><code>/holiday-events/{id}</code><p>Read one owned holiday event.</p></div></div>
                            <div class="endpoint"><span class="method patch">PATCH</span><div><code>/holiday-events/{id}</code><p>Update holiday event fields.</p></div></div>
                            <div class="endpoint"><span class="method delete">DELETE</span><div><code>/holiday-events/{id}</code><p>Delete one owned holiday event. Returns <code class="inline-code">204</code>.</p></div></div>
                        </div>

                        <h3>Work Schedule</h3>
                        <div class="endpoint-group">
                            <div class="endpoint"><span class="method get">GET</span><div><code>/work-schedule/settings</code><p>Return user settings and shift templates. Default day/night templates are created automatically for each user.</p></div></div>
                            <div class="endpoint"><span class="method put">PUT</span><div><code>/work-schedule/settings</code><p>Update schedule settings and upsert shift templates by code.</p></div></div>
                            <div class="endpoint"><span class="method get">GET</span><div><code>/work-schedule/cycles/2026-06-26</code><p>Return one 26th-anchored cycle and its assignments.</p></div></div>
                            <div class="endpoint"><span class="method put">PUT</span><div><code>/work-schedule/cycles/2026-06-26</code><p>Save up to 31 assignments using shift template code, template id, or null for day off.</p></div></div>
                            <div class="endpoint"><span class="method get">GET</span><div><code>/work-schedule/days?from=2026-06-26&amp;to=2026-06-30</code><p>Materialize actual dates, shift start/end date-times, and blocked status.</p></div></div>
                        </div>
                    </section>

                    <section class="section" id="examples" aria-labelledby="examples-title">
                        <h2 id="examples-title">Request And Response Examples</h2>

                        <h3>Create a note</h3>
                        <pre><code>curl -X POST "{{ $baseUrl }}/notes" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer &lt;token&gt;" \
  -d '{
    "date": "2026-06-27",
    "text": "Prepare calendar API homework"
  }'</code></pre>
                        <pre><code>{
  "data": {
    "id": 12,
    "date": "2026-06-27",
    "text": "Prepare calendar API homework",
    "created_at": "2026-07-01T00:00:00+07:00",
    "updated_at": "2026-07-01T00:00:00+07:00"
  }
}</code></pre>

                        <h3>Create an event</h3>
                        <pre><code>curl -X POST "{{ $baseUrl }}/events" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer &lt;token&gt;" \
  -d '{
    "title": "Class demo",
    "starts_at": "2026-06-27 09:00:00",
    "ends_at": "2026-06-27 10:00:00",
    "location": "IT STEP",
    "color": "#1f6f4a",
    "reminder_minutes_before": 30
  }'</code></pre>
                        <pre><code>{
  "data": {
    "id": 4,
    "title": "Class demo",
    "description": null,
    "starts_at": "2026-06-27T09:00:00+07:00",
    "ends_at": "2026-06-27T10:00:00+07:00",
    "all_day": false,
    "location": "IT STEP",
    "color": "#1f6f4a",
    "reminder_minutes_before": 30
  }
}</code></pre>

                        <h3>Read a day view</h3>
                        <pre><code>curl "{{ $baseUrl }}/calendar/day?date=2026-06-27" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer &lt;token&gt;"</code></pre>
                        <pre><code>{
  "data": {
    "calendar": {
      "date": "2026-06-27",
      "year": 2026,
      "month": 6,
      "day": 27,
      "day_of_week_en": "Saturday",
      "lunar_day": 13,
      "is_waxing": true,
      "buddhist_era": 2570,
      "moon_phase": "waxing",
      "holiday": null,
      "is_auspicious": false,
      "auspicious_type": null
    },
    "notes": [],
    "events": [],
    "holiday_events": [],
    "work_shift": null
  }
}</code></pre>

                        <h3>Save a work schedule cycle</h3>
                        <pre><code>curl -X PUT "{{ $baseUrl }}/work-schedule/cycles/2026-06-26" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer &lt;token&gt;" \
  -d '{
    "assignments": [
      "night", "day", null, null, null, null, null,
      null, null, null, null, null, null, null, null,
      null, null, null, null, null, null, null, null,
      null, null, null, null, null, null, null, null
    ]
  }'</code></pre>
                    </section>

                    <section class="section" id="errors" aria-labelledby="errors-title">
                        <h2 id="errors-title">Error Codes</h2>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr><th>Status</th><th>Meaning</th><th>Typical cause</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>200 OK</code></td><td>Request succeeded.</td><td>Returned for most read/update operations.</td></tr>
                                    <tr><td><code>201 Created</code></td><td>Record or token was created.</td><td>Register, create note, create event, create holiday event.</td></tr>
                                    <tr><td><code>204 No Content</code></td><td>Request succeeded without a body.</td><td>Logout or delete endpoints.</td></tr>
                                    <tr><td><code>401 Unauthorized</code></td><td>The request is not authenticated.</td><td>Missing, invalid, or revoked bearer token.</td></tr>
                                    <tr><td><code>404 Not Found</code></td><td>The record cannot be found for this user.</td><td>Wrong id, deleted record, or another user's private record.</td></tr>
                                    <tr><td><code>422 Unprocessable Content</code></td><td>The JSON body or query string failed validation.</td><td>Missing fields, invalid dates, invalid cycle start date, or out-of-range values.</td></tr>
                                    <tr><td><code>500 Server Error</code></td><td>Unexpected server-side failure.</td><td>Configuration, database, or runtime issue.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h3>Unauthenticated response</h3>
                        <pre><code>{
  "message": "Unauthenticated."
}</code></pre>

                        <h3>Record isolation behavior</h3>
                        <p>
                            When a token tries to access another user's note, event, holiday event, or work schedule data, the API returns <code class="inline-code">404 Not Found</code>. This avoids exposing whether a private record exists.
                        </p>
                    </section>

                    <section class="section" id="integration" aria-labelledby="integration-title">
                        <h2 id="integration-title">Integration Steps</h2>
                        <ol class="steps">
                            <li>
                                <strong>Register or log in from your app.</strong>
                                <p>Keep the returned token in secure storage for your platform.</p>
                            </li>
                            <li>
                                <strong>Create one API client wrapper.</strong>
                                <p>Attach the bearer token and JSON headers in one place so every request behaves consistently.</p>
                            </li>
                            <li>
                                <strong>Handle validation and auth failures.</strong>
                                <p>Show field-level messages for <code class="inline-code">422</code>. Redirect users to sign in again for <code class="inline-code">401</code>.</p>
                            </li>
                            <li>
                                <strong>Refresh local calendar views after writes.</strong>
                                <p>After creating or updating overlays, call <code class="inline-code">/calendar/day</code> or <code class="inline-code">/calendar/month</code> to rebuild the UI.</p>
                            </li>
                        </ol>

                        <h3>JavaScript fetch client</h3>
                        <pre><code>const API_BASE_URL = "{{ $baseUrl }}";

async function apiRequest(path, token, options = {}) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
      ...(options.headers ?? {}),
    },
  });

  if (response.status === 204) {
    return null;
  }

  const payload = await response.json();

  if (!response.ok) {
    throw payload;
  }

  return payload.data;
}

const day = await apiRequest("/calendar/day?date=2026-06-27", token);</code></pre>

                        <h3>PHP HTTP client</h3>
                        <pre><code>$response = Http::withToken($token)
    ->acceptJson()
    ->post('{{ $baseUrl }}/notes', [
        'date' => '2026-06-27',
        'text' => 'Prepare calendar API homework',
    ]);

if ($response->failed()) {
    throw new RuntimeException($response->body());
}

$note = $response->json('data');</code></pre>
                    </section>
                </div>
            </div>
        </main>

        <footer class="footer">
            Khmer Calendar API documentation. Base URL: <code class="inline-code">{{ $baseUrl }}</code>
        </footer>
    </div>
</body>
</html>
