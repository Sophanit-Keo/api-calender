<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Work schedule spreadsheet and calendar dashboard">
    <title>ScheduleFlow Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-api-base="{{ url('/api/v1') }}">
    <div id="auth-screen" class="auth-screen">
        <section class="auth-card">
            <div class="brand brand-auth"><span class="brand-mark">S</span><span>ScheduleFlow</span></div>
            <p class="eyebrow">WORK SMARTER</p>
            <h1>Your work, organized.</h1>
            <p class="auth-copy">Plan shifts, assign tasks, and keep your team moving from one focused workspace.</p>
            <div class="auth-tabs" role="tablist">
                <button class="auth-tab active" data-auth-tab="login">Sign in</button>
                <button class="auth-tab" data-auth-tab="register">Create account</button>
            </div>
            <form id="auth-form" class="auth-form">
                <label id="name-field" class="hidden">Full name<input name="name" autocomplete="name" placeholder="Alex Morgan"></label>
                <label>Email<input name="email" type="email" autocomplete="email" required placeholder="you@example.com"></label>
                <label>Password<input name="password" type="password" autocomplete="current-password" required minlength="8" placeholder="At least 8 characters"></label>
                <p id="auth-error" class="form-error" aria-live="polite"></p>
                <button class="button button-primary button-block" type="submit"><span id="auth-submit-label">Sign in</span></button>
            </form>
            <a href="/" class="docs-link">View API documentation →</a>
        </section>
        <aside class="auth-visual" aria-hidden="true">
            <div class="visual-orb orb-one"></div><div class="visual-orb orb-two"></div>
            <div class="preview-card">
                <div class="preview-head"><span></span><span></span><span></span></div>
                <div class="preview-stat"><b>24</b><small>Tasks this week</small></div>
                <div class="preview-lines"><i></i><i></i><i></i><i></i></div>
            </div>
        </aside>
    </div>

    <div id="app" class="app-shell hidden">
        <aside class="sidebar">
            <a href="/dashboard" class="brand"><span class="brand-mark">S</span><span>ScheduleFlow</span></a>
            <nav class="nav-list" aria-label="Main navigation">
                <button class="nav-item active" data-section="overview"><span>⌂</span> Overview</button>
                <button class="nav-item" data-section="calendar"><span>▦</span> Calendar</button>
                <button class="nav-item" data-section="spreadsheet"><span>▤</span> Spreadsheet</button>
            </nav>
            <div class="sidebar-help">
                <div class="help-icon">?</div><b>Need help?</b>
                <p>Drag tasks on the calendar to quickly reschedule them.</p>
                <a href="/">Read the API docs</a>
            </div>
            <button id="logout-button" class="profile-button">
                <span id="profile-avatar" class="avatar">U</span>
                <span><b id="profile-name">User</b><small id="profile-email">user@example.com</small></span>
                <span>↪</span>
            </button>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <button id="mobile-menu" class="icon-button mobile-only" aria-label="Open menu">☰</button>
                <div><p class="eyebrow">SCHEDULE DASHBOARD</p><h1 id="page-title">Good day</h1></div>
                <div class="top-actions">
                    <label class="global-search"><span>⌕</span><input id="search-input" type="search" placeholder="Search tasks..."><kbd>⌘ K</kbd></label>
                    <button id="import-button" class="button button-secondary">⇧ Import</button>
                    <input id="import-file" type="file" accept=".csv,text/csv" hidden>
                    <button id="export-button" class="button button-secondary">⇩ Export</button>
                    <button id="add-button" class="button button-primary">＋ Add schedule</button>
                </div>
            </header>

            <section class="summary-grid" aria-label="Schedule summary">
                <article class="summary-card total"><div class="summary-icon">▤</div><div><span>Total tasks</span><strong id="summary-total">0</strong><small>All visible schedules</small></div></article>
                <article class="summary-card upcoming"><div class="summary-icon">↗</div><div><span>Upcoming</span><strong id="summary-upcoming">0</strong><small>Ready to work</small></div></article>
                <article class="summary-card completed"><div class="summary-icon">✓</div><div><span>Completed</span><strong id="summary-completed">0</strong><small>Great progress</small></div></article>
                <article class="summary-card overdue"><div class="summary-icon">!</div><div><span>Overdue</span><strong id="summary-overdue">0</strong><small>Needs attention</small></div></article>
            </section>

            <section class="workspace-card">
                <div class="workspace-toolbar">
                    <div class="view-tabs">
                        <button class="view-tab active" data-workspace-view="calendar">Calendar</button>
                        <button class="view-tab" data-workspace-view="spreadsheet">Spreadsheet</button>
                    </div>
                    <div class="filter-group">
                        <label class="compact-field"><span>Team member</span><select id="user-filter"><option value="">Everyone</option></select></label>
                        <label class="compact-field"><span>Status</span><select id="status-filter"><option value="">All statuses</option><option value="scheduled">Scheduled</option><option value="in_progress">In progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></label>
                        <label class="compact-field"><span>Priority</span><select id="priority-filter"><option value="">All priorities</option><option value="urgent">Urgent</option><option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option></select></label>
                        <button id="clear-filters" class="text-button">Clear</button>
                    </div>
                </div>

                <div id="calendar-panel">
                    <div class="calendar-toolbar">
                        <div class="calendar-nav"><button id="previous-period" class="icon-button" aria-label="Previous">‹</button><button id="today-button" class="button button-secondary button-small">Today</button><button id="next-period" class="icon-button" aria-label="Next">›</button><h2 id="calendar-title"></h2></div>
                        <div class="period-tabs"><button data-period="day">Day</button><button data-period="week">Week</button><button class="active" data-period="month">Month</button></div>
                    </div>
                    <div class="calendar-legend"><span><i class="dot overdue-dot"></i>Overdue</span><span><i class="dot upcoming-dot"></i>Upcoming</span><span><i class="dot completed-dot"></i>Completed</span><span><i class="dot shift-dot"></i>Work shift</span></div>
                    <div id="calendar-grid" class="calendar-grid" aria-live="polite"></div>
                </div>

                <div id="spreadsheet-panel" class="hidden">
                    <div class="sheet-meta"><p><strong>Schedule sheet</strong><span id="sheet-count">0 rows</span></p><small>Update fields directly, then select Save.</small></div>
                    <div class="table-wrap">
                        <table class="schedule-table">
                            <thead><tr><th>#</th><th>Date</th><th>Time</th><th>Task / Event</th><th>Description</th><th>Priority</th><th>Status</th><th>Assignee</th><th>Actions</th></tr></thead>
                            <tbody id="schedule-rows"></tbody>
                        </table>
                        <div id="table-empty" class="empty-state hidden"><div>▤</div><h3>No schedules found</h3><p>Add a task or adjust your filters.</p></div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <dialog id="schedule-dialog" class="modal">
        <form id="schedule-form" class="modal-card">
            <header><div><p class="eyebrow">SCHEDULE ENTRY</p><h2 id="modal-title">Add schedule</h2></div><button type="button" class="modal-close" aria-label="Close">×</button></header>
            <input name="id" type="hidden">
            <div class="form-grid">
                <label class="span-2">Task or event<input name="task" required maxlength="255" placeholder="What needs to be done?"></label>
                <label>Date<input name="scheduled_date" type="date" required></label>
                <label>Team member<select name="assignee_id"><option value="">Unassigned</option></select></label>
                <label>Start time<input name="start_time" type="time"></label>
                <label>End time<input name="end_time" type="time"></label>
                <label>Priority<select name="priority" required><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></label>
                <label>Status<select name="status" required><option value="scheduled">Scheduled</option><option value="in_progress">In progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></label>
                <label class="span-2">Description<textarea name="description" rows="4" maxlength="5000" placeholder="Add details, links, or notes..."></textarea></label>
            </div>
            <p id="form-error" class="form-error" aria-live="polite"></p>
            <footer><button type="button" class="button button-secondary modal-cancel">Cancel</button><button type="submit" class="button button-primary">Save schedule</button></footer>
        </form>
    </dialog>

    <div id="toast" class="toast" role="status" aria-live="polite"></div>
</body>
</html>