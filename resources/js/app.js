const apiBase = document.body.dataset.apiBase;

const state = {
    token: localStorage.getItem('schedule_token'),
    user: null,
    users: [],
    schedules: [],
    shifts: [],
    authMode: 'login',
    workspaceView: 'calendar',
    period: 'month',
    cursor: startOfDay(new Date()),
    filters: { search: '', user_id: '', status: '', priority: '' },
    roster: { cursor: startOfWeek(new Date()), codes: [], staff: [], loaded: false },
};

const elements = {
    authScreen: document.querySelector('#auth-screen'),
    authForm: document.querySelector('#auth-form'),
    authError: document.querySelector('#auth-error'),
    app: document.querySelector('#app'),
    calendarGrid: document.querySelector('#calendar-grid'),
    calendarTitle: document.querySelector('#calendar-title'),
    calendarPanel: document.querySelector('#calendar-panel'),
    spreadsheetPanel: document.querySelector('#spreadsheet-panel'),
    scheduleRows: document.querySelector('#schedule-rows'),
    dialog: document.querySelector('#schedule-dialog'),
    scheduleForm: document.querySelector('#schedule-form'),
    rosterHeadRow: document.querySelector('#roster-head-row'),
    rosterRows: document.querySelector('#roster-rows'),
    rosterLegend: document.querySelector('#roster-legend'),
    staffDialog: document.querySelector('#staff-dialog'),
    staffForm: document.querySelector('#staff-form'),
    toast: document.querySelector('#toast'),
};

function startOfDay(date) {
    const value = new Date(date);
    value.setHours(0, 0, 0, 0);
    return value;
}

function addDays(date, days) {
    const value = new Date(date);
    value.setDate(value.getDate() + days);
    return value;
}

function addMonths(date, months) {
    const value = new Date(date);
    value.setDate(1);
    value.setMonth(value.getMonth() + months);
    return value;
}

function startOfWeek(date) {
    const value = startOfDay(date);
    const offset = (value.getDay() + 6) % 7;
    return addDays(value, -offset);
}

function localDate(value) {
    if (value instanceof Date) return value;
    const [year, month, day] = String(value).split('-').map(Number);
    return new Date(year, month - 1, day);
}

function dateKey(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function sameDay(first, second) {
    return dateKey(first) === dateKey(second);
}

function escapeHtml(value = '') {
    return String(value ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
    })[character]);
}

function option(value, label, selectedValue) {
    return `<option value="${escapeHtml(value)}"${String(value) === String(selectedValue ?? '') ? ' selected' : ''}>${escapeHtml(label)}</option>`;
}

function filtersQuery() {
    const params = new URLSearchParams();
    Object.entries(state.filters).forEach(([key, value]) => value && params.set(key, value));
    return params.toString();
}

async function api(path, options = {}) {
    const headers = new Headers(options.headers || {});
    if (state.token) headers.set('Authorization', `Bearer ${state.token}`);
    if (options.body && !(options.body instanceof FormData)) headers.set('Content-Type', 'application/json');
    headers.set('Accept', 'application/json');

    const response = await fetch(`${apiBase}${path}`, { ...options, headers });
    if (response.status === 401) {
        signOut(false);
        throw new Error('Your session has expired. Please sign in again.');
    }
    if (response.status === 204) return null;
    const contentType = response.headers.get('content-type') || '';
    const payload = contentType.includes('application/json') ? await response.json() : null;
    if (!response.ok) {
        const validation = payload?.errors ? Object.values(payload.errors).flat().join(' ') : null;
        throw new Error(validation || payload?.message || `Request failed (${response.status}).`);
    }
    return payload;
}

function showToast(message, type = 'success') {
    elements.toast.textContent = message;
    elements.toast.className = `toast show${type === 'error' ? ' error' : ''}`;
    clearTimeout(showToast.timer);
    showToast.timer = setTimeout(() => elements.toast.classList.remove('show'), 3200);
}

function setLoading(active) {
    document.querySelector('.workspace-card')?.classList.toggle('loading', active);
}

async function authenticate(event) {
    event.preventDefault();
    elements.authError.textContent = '';
    const submit = elements.authForm.querySelector('button[type="submit"]');
    submit.disabled = true;
    const data = Object.fromEntries(new FormData(elements.authForm));
    data.device_name = 'schedule-dashboard';
    if (state.authMode === 'login') delete data.name;

    try {
        const payload = await api(`/auth/${state.authMode}`, { method: 'POST', body: JSON.stringify(data) });
        state.token = payload.data.token;
        localStorage.setItem('schedule_token', state.token);
        state.user = payload.data.user;
        await enterApp();
    } catch (error) {
        elements.authError.textContent = error.message;
    } finally {
        submit.disabled = false;
    }
}

function selectAuthMode(mode) {
    state.authMode = mode;
    document.querySelectorAll('[data-auth-tab]').forEach((button) => button.classList.toggle('active', button.dataset.authTab === mode));
    document.querySelector('#name-field').classList.toggle('hidden', mode !== 'register');
    document.querySelector('#name-field input').required = mode === 'register';
    document.querySelector('#auth-submit-label').textContent = mode === 'register' ? 'Create account' : 'Sign in';
    elements.authError.textContent = '';
}

async function enterApp() {
    elements.authScreen.classList.add('hidden');
    elements.app.classList.remove('hidden');
    const firstName = state.user.name?.split(' ')[0] || 'there';
    const hour = new Date().getHours();
    const greeting = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
    document.querySelector('#page-title').textContent = `${greeting}, ${firstName}`;
    document.querySelector('#profile-name').textContent = state.user.name;
    document.querySelector('#profile-email').textContent = state.user.email;
    document.querySelector('#profile-avatar').textContent = state.user.name?.charAt(0).toUpperCase() || 'U';

    try {
        await loadUsers();
        await refreshData();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

async function initialize() {
    bindEvents();
    if (!state.token) return;
    try {
        const payload = await api('/auth/me');
        state.user = payload.data;
        await enterApp();
    } catch {
        signOut(false);
    }
}

function signOut(callApi = true) {
    if (callApi && state.token) api('/auth/logout', { method: 'POST' }).catch(() => {});
    state.token = null;
    state.user = null;
    state.schedules = [];
    localStorage.removeItem('schedule_token');
    elements.app.classList.add('hidden');
    elements.authScreen.classList.remove('hidden');
    elements.authForm.reset();
}

async function loadUsers() {
    const payload = await api('/schedules/users');
    state.users = payload.data;
    const filter = document.querySelector('#user-filter');
    const assignee = elements.scheduleForm.elements.assignee_id;
    filter.innerHTML = '<option value="">Everyone</option>' + state.users.map((user) => option(user.id, user.name, state.filters.user_id)).join('');
    assignee.innerHTML = '<option value="">Unassigned</option>' + state.users.map((user) => option(user.id, `${user.name} · ${user.email}`, '')).join('');
}

function visibleRange() {
    if (state.period === 'day') return { from: startOfDay(state.cursor), to: startOfDay(state.cursor) };
    if (state.period === 'week') {
        const from = startOfWeek(state.cursor);
        return { from, to: addDays(from, 6) };
    }
    const monthStart = new Date(state.cursor.getFullYear(), state.cursor.getMonth(), 1);
    const from = startOfWeek(monthStart);
    return { from, to: addDays(from, 41) };
}

async function refreshData() {
    setLoading(true);
    const query = filtersQuery();
    const { from, to } = visibleRange();
    try {
        const [entries, summary, shifts] = await Promise.all([
            api(`/schedules${query ? `?${query}` : ''}`),
            api(`/schedules/summary${query ? `?${query}` : ''}`),
            api(`/work-schedule/days?from=${dateKey(from)}&to=${dateKey(to)}`),
        ]);
        state.schedules = entries.data;
        state.shifts = shifts.data;
        renderSummary(summary.data);
        renderAll();
    } finally {
        setLoading(false);
    }
}

async function refreshShifts() {
    const { from, to } = visibleRange();
    try {
        const payload = await api(`/work-schedule/days?from=${dateKey(from)}&to=${dateKey(to)}`);
        state.shifts = payload.data;
        renderCalendar();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

function renderSummary(summary) {
    ['total', 'upcoming', 'completed', 'overdue'].forEach((key) => {
        document.querySelector(`#summary-${key}`).textContent = summary[key] ?? 0;
    });
}

function renderAll() {
    renderCalendar();
}

function renderCalendar() {
    const { from, to } = visibleRange();
    const days = [];
    for (let day = from; day <= to; day = addDays(day, 1)) days.push(day);
    const titleOptions = { month: 'long', year: 'numeric' };
    if (state.period === 'day') {
        elements.calendarTitle.textContent = state.cursor.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    } else if (state.period === 'week') {
        elements.calendarTitle.textContent = `${from.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })} – ${to.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}`;
    } else {
        elements.calendarTitle.textContent = state.cursor.toLocaleDateString(undefined, titleOptions);
    }

    elements.calendarGrid.className = `calendar-grid ${state.period}-view`;
    const weekdays = state.period === 'day' ? '' : days.slice(0, 7).map((day) => `<div class="calendar-weekday">${escapeHtml(day.toLocaleDateString(undefined, { weekday: 'short' }))}</div>`).join('');
    const cells = days.map((day) => renderDay(day)).join('');
    elements.calendarGrid.innerHTML = weekdays + cells;
    bindCalendarInteractions();
}

function renderDay(day) {
    const key = dateKey(day);
    const entries = state.schedules.filter((entry) => entry.scheduled_date === key);
    const shift = state.shifts.find((item) => item.date === key && item.shift_template);
    const outside = state.period === 'month' && day.getMonth() !== state.cursor.getMonth();
    const chips = entries.map((entry) => `
        <article class="event-chip ${escapeHtml(entry.status === 'cancelled' ? 'cancelled' : entry.timing)}" draggable="${entry.can_edit}" data-schedule-id="${entry.id}" title="${escapeHtml(entry.description || entry.task)}">
            <div><b>${escapeHtml(entry.task)}</b><span>${escapeHtml(entry.start_time || 'All day')}${entry.assignee ? ` · ${escapeHtml(entry.assignee.name)}` : ''}</span></div>
        </article>`).join('');
    const shiftChip = shift ? `<div class="shift-chip" title="${escapeHtml(`${shift.starts_at} – ${shift.ends_at}`)}">${escapeHtml(shift.shift_template.name)} · ${escapeHtml(shift.shift_template.start_time)}–${escapeHtml(shift.shift_template.end_time)}${shift.blocked ? ' · Blocked' : ''}</div>` : '';
    return `<div class="calendar-day${outside ? ' outside' : ''}${sameDay(day, new Date()) ? ' today' : ''}" data-date="${key}">
        <div class="day-head"><span class="day-number">${day.getDate()}</span><button class="day-add" data-add-date="${key}" aria-label="Add schedule on ${key}">＋</button></div>
        ${shiftChip}${chips}
    </div>`;
}

function bindCalendarInteractions() {
    elements.calendarGrid.querySelectorAll('[data-add-date]').forEach((button) => button.addEventListener('click', () => openScheduleDialog(null, button.dataset.addDate)));
    elements.calendarGrid.querySelectorAll('[data-schedule-id]').forEach((chip) => {
        chip.addEventListener('click', () => openScheduleDialog(Number(chip.dataset.scheduleId)));
        chip.addEventListener('dragstart', (event) => {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', chip.dataset.scheduleId);
        });
    });
    elements.calendarGrid.querySelectorAll('[data-date]').forEach((cell) => {
        cell.addEventListener('dragover', (event) => { event.preventDefault(); cell.classList.add('drag-over'); });
        cell.addEventListener('dragleave', () => cell.classList.remove('drag-over'));
        cell.addEventListener('drop', async (event) => {
            event.preventDefault();
            cell.classList.remove('drag-over');
            const id = Number(event.dataTransfer.getData('text/plain'));
            const entry = state.schedules.find((item) => item.id === id);
            if (!entry || entry.scheduled_date === cell.dataset.date) return;
            try {
                await api(`/schedules/${id}`, { method: 'PATCH', body: JSON.stringify({ scheduled_date: cell.dataset.date }) });
                showToast(`“${entry.task}” moved to ${cell.dataset.date}.`);
                await refreshData();
            } catch (error) {
                showToast(error.message, 'error');
            }
        });
    });
}

function visibleRosterRange() {
    const from = state.roster.cursor;
    return { from, to: addDays(from, 6) };
}

async function refreshRoster() {
    setLoading(true);
    const { from, to } = visibleRosterRange();
    try {
        const payload = await api(`/work-schedule/roster?from=${dateKey(from)}&to=${dateKey(to)}`);
        state.roster.codes = payload.data.codes;
        state.roster.staff = payload.data.staff;
        state.roster.loaded = true;
        renderRoster();
    } catch (error) {
        showToast(error.message, 'error');
    } finally {
        setLoading(false);
    }
}

function rosterDays() {
    const { from } = visibleRosterRange();
    return Array.from({ length: 7 }, (_, index) => addDays(from, index));
}

function renderRosterLegend() {
    elements.rosterLegend.innerHTML = state.roster.codes.map((code) => `
        <span><i class="dot roster-dot-${escapeHtml(code.color)}"></i>${escapeHtml(code.code)} — ${escapeHtml(code.name)}</span>
    `).join('');
}

function renderRosterHead() {
    const days = rosterDays();
    const { from, to } = visibleRosterRange();
    document.querySelector('#roster-range-label').textContent = `${from.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })} – ${to.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}`;
    const dayHeaders = days.map((day) => `<th class="${sameDay(day, new Date()) ? 'roster-today' : ''}">${escapeHtml(day.toLocaleDateString(undefined, { weekday: 'short' }))}<small>${day.getDate()}</small></th>`).join('');
    elements.rosterHeadRow.innerHTML = '<th>#</th><th>Staff ID</th><th>Full name</th><th>Position</th><th>Group</th>' + dayHeaders + '<th>Actions</th>';
}

function renderRoster() {
    document.querySelector('#roster-count').textContent = `${state.roster.staff.length} ${state.roster.staff.length === 1 ? 'staff' : 'staff'}`;
    document.querySelector('#roster-empty').classList.toggle('hidden', state.roster.staff.length > 0);
    renderRosterLegend();
    renderRosterHead();
    const days = rosterDays();

    elements.rosterRows.innerHTML = state.roster.staff.map((staff, index) => {
        const cells = days.map((day) => {
            const key = dateKey(day);
            const cell = staff.entries[key] || null;
            const colorClass = cell ? `roster-cell-${escapeHtml(cell.color)}` : '';
            const options = '<option value="">—</option>' + state.roster.codes.map((code) => option(code.id, code.code, cell?.id)).join('');
            return `<td class="roster-cell ${colorClass}"><select data-user-id="${staff.id}" data-work-date="${key}" aria-label="${escapeHtml(staff.name)} on ${key}">${options}</select></td>`;
        }).join('');
        return `<tr data-staff-id="${staff.id}">
            <td>${index + 1}</td>
            <td><input class="roster-inline-input" type="text" data-field="staff_id" value="${escapeHtml(staff.staff_id || '')}" placeholder="—" aria-label="${escapeHtml(staff.name)} staff ID" maxlength="255"></td>
            <td>${escapeHtml(staff.name)}</td>
            <td><input class="roster-inline-input" type="text" data-field="position" value="${escapeHtml(staff.position || '')}" placeholder="—" aria-label="${escapeHtml(staff.name)} position" maxlength="255"></td>
            <td><input class="roster-inline-input" type="text" data-field="group" value="${escapeHtml(staff.group || '')}" placeholder="—" aria-label="${escapeHtml(staff.name)} group" maxlength="255"></td>
            ${cells}
            <td><button class="sheet-action delete" data-clear-staff="${staff.id}" title="Clear this staff member's week">×</button></td>
        </tr>`;
    }).join('');
    bindRosterInteractions();
}

function bindRosterInteractions() {
    elements.rosterRows.querySelectorAll('input.roster-inline-input').forEach((input) => input.addEventListener('change', async () => {
        const row = input.closest('tr');
        const userId = Number(row.dataset.staffId);
        const payload = {
            staff_id: row.querySelector('[data-field="staff_id"]').value.trim() || null,
            position: row.querySelector('[data-field="position"]').value.trim() || null,
            group: row.querySelector('[data-field="group"]').value.trim() || null,
        };
        input.disabled = true;
        try {
            await api(`/work-schedule/roster/staff/${userId}`, { method: 'PUT', body: JSON.stringify(payload) });
            const staff = state.roster.staff.find((item) => item.id === userId);
            if (staff) Object.assign(staff, payload);
            showToast('Staff details updated.');
        } catch (error) {
            showToast(error.message, 'error');
            await refreshRoster();
        } finally {
            input.disabled = false;
        }
    }));
    elements.rosterRows.querySelectorAll('select[data-user-id]').forEach((select) => select.addEventListener('change', async () => {
        select.disabled = true;
        try {
            await api('/work-schedule/roster/cell', {
                method: 'PUT',
                body: JSON.stringify({
                    user_id: Number(select.dataset.userId),
                    work_date: select.dataset.workDate,
                    work_shift_template_id: select.value || null,
                }),
            });
            await refreshRoster();
        } catch (error) {
            showToast(error.message, 'error');
            select.disabled = false;
        }
    }));
    elements.rosterRows.querySelectorAll('[data-clear-staff]').forEach((button) => button.addEventListener('click', async () => {
        const staff = state.roster.staff.find((item) => item.id === Number(button.dataset.clearStaff));
        if (!staff || !window.confirm(`Clear ${staff.name}’s roster for this week? This cannot be undone.`)) return;
        const { from, to } = visibleRosterRange();
        try {
            await api(`/work-schedule/roster/staff/${staff.id}?from=${dateKey(from)}&to=${dateKey(to)}`, { method: 'DELETE' });
            showToast(`Cleared ${staff.name}’s roster for this week.`);
            await refreshRoster();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }));
}

function openStaffDialog() {
    elements.staffForm.reset();
    document.querySelector('#staff-form-error').textContent = '';
    elements.staffDialog.showModal();
    setTimeout(() => elements.staffForm.elements.name.focus(), 50);
}

async function saveStaff(event) {
    event.preventDefault();
    const form = elements.staffForm;
    const submit = form.querySelector('[type="submit"]');
    const data = Object.fromEntries(new FormData(form));
    data.device_name = 'roster-add-staff';
    submit.disabled = true;
    document.querySelector('#staff-form-error').textContent = '';
    try {
        await api('/auth/register', { method: 'POST', body: JSON.stringify(data) });
        elements.staffDialog.close();
        showToast(`${data.name} added to the roster.`);
        await loadUsers();
        await refreshRoster();
    } catch (error) {
        document.querySelector('#staff-form-error').textContent = error.message;
    } finally {
        submit.disabled = false;
    }
}

function openScheduleDialog(id = null, date = null) {
    const entry = id ? state.schedules.find((item) => item.id === id) : null;
    const form = elements.scheduleForm;
    form.reset();
    form.elements.id.value = entry?.id || '';
    form.elements.task.value = entry?.task || '';
    form.elements.scheduled_date.value = entry?.scheduled_date || date || dateKey(state.cursor);
    form.elements.start_time.value = entry?.start_time || '';
    form.elements.end_time.value = entry?.end_time || '';
    form.elements.description.value = entry?.description || '';
    form.elements.priority.value = entry?.priority || 'medium';
    form.elements.status.value = entry?.status || 'scheduled';
    form.elements.assignee_id.value = entry?.assignee?.id || '';
    document.querySelector('#modal-title').textContent = entry ? 'Edit schedule' : 'Add schedule';
    document.querySelector('#form-error').textContent = '';
    elements.dialog.showModal();
    setTimeout(() => form.elements.task.focus(), 50);
}

async function saveSchedule(event) {
    event.preventDefault();
    const form = elements.scheduleForm;
    const submit = form.querySelector('[type="submit"]');
    const id = form.elements.id.value;
    const data = Object.fromEntries(new FormData(form));
    delete data.id;
    ['start_time', 'end_time', 'assignee_id', 'description'].forEach((key) => { if (!data[key]) data[key] = null; });
    submit.disabled = true;
    document.querySelector('#form-error').textContent = '';
    try {
        await api(`/schedules${id ? `/${id}` : ''}`, { method: id ? 'PUT' : 'POST', body: JSON.stringify(data) });
        elements.dialog.close();
        showToast(id ? 'Schedule updated.' : 'Schedule added.');
        await refreshData();
    } catch (error) {
        document.querySelector('#form-error').textContent = error.message;
    } finally {
        submit.disabled = false;
    }
}

async function deleteSchedule(id) {
    const entry = state.schedules.find((item) => item.id === id);
    if (!entry || !window.confirm(`Delete “${entry.task}”? This cannot be undone.`)) return;
    try {
        await api(`/schedules/${id}`, { method: 'DELETE' });
        showToast('Schedule deleted.');
        await refreshData();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

function selectWorkspaceView(view) {
    state.workspaceView = view;
    document.querySelectorAll('[data-workspace-view]').forEach((button) => button.classList.toggle('active', button.dataset.workspaceView === view));
    elements.calendarPanel.classList.toggle('hidden', view !== 'calendar');
    elements.spreadsheetPanel.classList.toggle('hidden', view !== 'spreadsheet');
    document.querySelector('.filter-group').classList.toggle('hidden', view !== 'calendar');
    if (view === 'spreadsheet') refreshRoster().catch((error) => showToast(error.message, 'error'));
}

function selectSection(section) {
    document.querySelectorAll('[data-section]').forEach((button) => button.classList.toggle('active', button.dataset.section === section));
    if (section === 'calendar' || section === 'spreadsheet') selectWorkspaceView(section);
    document.querySelector('.workspace-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    document.querySelector('.sidebar').classList.remove('open');
}

async function exportSchedules() {
    try {
        const query = filtersQuery();
        const response = await fetch(`${apiBase}/schedules/export${query ? `?${query}` : ''}`, { headers: { Authorization: `Bearer ${state.token}` } });
        if (!response.ok) throw new Error('Unable to export schedules.');
        const blob = await response.blob();
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `schedules-${dateKey(new Date())}.csv`;
        link.click();
        URL.revokeObjectURL(link.href);
        showToast('CSV export downloaded.');
    } catch (error) {
        showToast(error.message, 'error');
    }
}

async function importSchedules(file) {
    if (!file) return;
    const form = new FormData();
    form.append('file', file);
    try {
        const payload = await api('/schedules/import', { method: 'POST', body: form });
        const count = payload.data.imported;
        const errorCount = payload.data.errors.length;
        showToast(`Imported ${count} schedule${count === 1 ? '' : 's'}${errorCount ? `; ${errorCount} rows skipped` : ''}.`, errorCount && !count ? 'error' : 'success');
        await refreshData();
    } catch (error) {
        showToast(error.message, 'error');
    } finally {
        document.querySelector('#import-file').value = '';
    }
}

function debounce(callback, delay = 350) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => callback(...args), delay);
    };
}

function bindEvents() {
    elements.authForm.addEventListener('submit', authenticate);
    document.querySelectorAll('[data-auth-tab]').forEach((button) => button.addEventListener('click', () => selectAuthMode(button.dataset.authTab)));
    document.querySelector('#logout-button').addEventListener('click', () => signOut(true));
    document.querySelector('#add-button').addEventListener('click', () => openScheduleDialog());
    elements.scheduleForm.addEventListener('submit', saveSchedule);
    document.querySelector('.modal-close').addEventListener('click', () => elements.dialog.close());
    document.querySelector('.modal-cancel').addEventListener('click', () => elements.dialog.close());
    elements.dialog.addEventListener('click', (event) => { if (event.target === elements.dialog) elements.dialog.close(); });
    document.querySelectorAll('[data-workspace-view]').forEach((button) => button.addEventListener('click', () => selectWorkspaceView(button.dataset.workspaceView)));
    document.querySelectorAll('[data-section]').forEach((button) => button.addEventListener('click', () => selectSection(button.dataset.section)));
    document.querySelectorAll('[data-period]').forEach((button) => button.addEventListener('click', async () => {
        state.period = button.dataset.period;
        document.querySelectorAll('[data-period]').forEach((item) => item.classList.toggle('active', item === button));
        await refreshShifts();
    }));
    document.querySelector('#today-button').addEventListener('click', async () => { state.cursor = startOfDay(new Date()); await refreshShifts(); });
    document.querySelector('#previous-period').addEventListener('click', async () => {
        state.cursor = state.period === 'month' ? addMonths(state.cursor, -1) : addDays(state.cursor, state.period === 'week' ? -7 : -1);
        await refreshShifts();
    });
    document.querySelector('#next-period').addEventListener('click', async () => {
        state.cursor = state.period === 'month' ? addMonths(state.cursor, 1) : addDays(state.cursor, state.period === 'week' ? 7 : 1);
        await refreshShifts();
    });
    const applyFilters = () => refreshData().catch((error) => showToast(error.message, 'error'));
    document.querySelector('#search-input').addEventListener('input', debounce((event) => { state.filters.search = event.target.value.trim(); applyFilters(); }));
    ['user', 'status', 'priority'].forEach((name) => document.querySelector(`#${name}-filter`).addEventListener('change', (event) => { state.filters[name === 'user' ? 'user_id' : name] = event.target.value; applyFilters(); }));
    document.querySelector('#clear-filters').addEventListener('click', () => {
        state.filters = { search: '', user_id: '', status: '', priority: '' };
        document.querySelector('#search-input').value = '';
        ['user', 'status', 'priority'].forEach((name) => { document.querySelector(`#${name}-filter`).value = ''; });
        applyFilters();
    });
    document.querySelector('#add-staff-button').addEventListener('click', openStaffDialog);
    elements.staffForm.addEventListener('submit', saveStaff);
    elements.staffDialog.querySelector('.modal-close').addEventListener('click', () => elements.staffDialog.close());
    elements.staffDialog.querySelector('.modal-cancel').addEventListener('click', () => elements.staffDialog.close());
    elements.staffDialog.addEventListener('click', (event) => { if (event.target === elements.staffDialog) elements.staffDialog.close(); });
    document.querySelector('#roster-today').addEventListener('click', async () => { state.roster.cursor = startOfWeek(new Date()); await refreshRoster(); });
    document.querySelector('#roster-previous-week').addEventListener('click', async () => { state.roster.cursor = addDays(state.roster.cursor, -7); await refreshRoster(); });
    document.querySelector('#roster-next-week').addEventListener('click', async () => { state.roster.cursor = addDays(state.roster.cursor, 7); await refreshRoster(); });
    document.querySelector('#export-button').addEventListener('click', exportSchedules);
    document.querySelector('#import-button').addEventListener('click', () => document.querySelector('#import-file').click());
    document.querySelector('#import-file').addEventListener('change', (event) => importSchedules(event.target.files[0]));
    document.querySelector('#mobile-menu').addEventListener('click', () => document.querySelector('.sidebar').classList.toggle('open'));
    document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            document.querySelector('#search-input').focus();
        }
        if (event.key === 'Escape' && elements.dialog.open) elements.dialog.close();
        if (event.key === 'Escape' && elements.staffDialog.open) elements.staffDialog.close();
    });
}

initialize();
