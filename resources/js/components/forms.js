/**
 * Components that used to be inline `x-data` objects with function bodies, or templates with arrow
 * functions — moved here because the CSP build of Alpine (security review R-01) evaluates
 * expressions with its own parser: one expression, no statements, no arrow functions, no template
 * literals, no `?.` / `??`, and no browser globals (`window`, `JSON`). Anything that needs those is a
 * method here, and the template only calls it. Data a component needs from the server arrives in
 * `data-*` attributes, read in `init()`.
 */

/** The sign-in form: a submitting flag, and the lockout countdown (FR-010). */
export function trackLoginForm() {
    return {
        submitting: false,
        remaining: 0,

        init() {
            this.remaining = Number(this.$el.dataset.retryAfter || 0);

            if (this.remaining > 0) {
                const timer = setInterval(() => {
                    this.remaining -= 1;

                    if (this.remaining <= 0) {
                        clearInterval(timer);
                    }
                }, 1000);
            }
        },
    };
}

/** Import step 2: the required targets, and whether each has a column (FR-059). */
export function trackMappingForm() {
    return {
        required: [],
        filled: {},

        init() {
            this.required = JSON.parse(this.$el.dataset.required || '[]');

            for (const target of this.required) {
                const select = this.$el.querySelector(`[data-target='${target}']`);
                this.filled[target] = Boolean(select && select.value !== '');
            }
        },

        choose(target, value) {
            this.filled[target] = value !== '';
        },

        incomplete() {
            return this.required.some((target) => ! this.filled[target]);
        },
    };
}

/**
 * The deactivate and reset-password dialogs: which account they act on. Filled from the row's open
 * event, or — sent back with errors — from the server's data attributes.
 */
export function trackUserTarget() {
    return {
        userId: null,
        userName: '',
        dialog: '',
        base: '',
        suffix: '',

        init() {
            const data = this.$el.dataset;
            this.dialog = data.dialog;
            this.base = data.base;
            this.suffix = data.suffix;
            this.userId = data.userId ? Number(data.userId) : null;
            this.userName = data.userName || '';
        },

        receive(event) {
            if (! event.detail || event.detail.id !== this.dialog) {
                return;
            }

            this.userId = event.detail.userId;
            this.userName = event.detail.userName || '';
        },

        action() {
            return `${this.base}/${this.userId}/${this.suffix}`;
        },
    };
}

/** The create / edit account dialog (T132). */
export function trackUserDialog() {
    return {
        user: null,
        draft: {},
        role: '',
        base: '',
        store: '',

        init() {
            const data = this.$el.dataset;
            this.base = data.base;
            this.store = data.store;
            this.user = data.user ? JSON.parse(data.user) : null;
            this.draft = data.draft ? JSON.parse(data.draft) : {};
            this.role = data.role || '';

            window.addEventListener('track-dialog-open', (event) => {
                if (! event.detail || event.detail.id !== 'user-dialog') {
                    return;
                }

                this.user = event.detail.user || null;
                this.draft = {};
                this.role = '';
            });
        },

        field(name) {
            if (this.draft[name] !== undefined && this.draft[name] !== null) {
                return this.draft[name];
            }

            return this.user && this.user[name] !== undefined && this.user[name] !== null ? this.user[name] : '';
        },

        userId() {
            return this.user ? this.user.id : '';
        },

        action() {
            return this.user ? `${this.base}/${this.user.id}` : this.store;
        },
    };
}

/**
 * The one withdraw dialog shared by every roster row (T203). The row's button relays the open with
 * its enrollment; the event is re-dispatched from that button so the dialog records it as the trigger
 * and Escape hands focus back to it.
 */
export function trackWithdrawDialog() {
    return {
        enrollment: null,
        student: '',
        action: '',

        init() {
            const data = this.$el.dataset;
            this.enrollment = data.enrollment ? Number(data.enrollment) : null;
            this.student = data.student || '';
            this.action = data.action || '';
        },

        open(event) {
            const detail = event.detail || {};
            this.enrollment = Number(detail.enrollment);
            this.student = detail.student || '';
            this.action = detail.action || '';

            const trigger = event.target instanceof HTMLElement ? event.target : this.$el;
            trigger.dispatchEvent(new CustomEvent('track-dialog-open', { detail: { id: 'withdraw-dialog' }, bubbles: true }));
        },
    };
}

/**
 * T318 — the programme create/edit dialog.
 *
 * The same shape as trackUserDialog: `program-dialog` opened with no `program` is a create, with one
 * it is an edit, and the form's action, method and title follow. Values reach the inputs through
 * `field()` so a form sent back with errors shows what was typed rather than the stored values.
 */
export function trackProgramDialog() {
    return {
        program: null,
        draft: {},
        unit: 'admin',
        active: true,
        base: '',
        store: '',
        createTitle: '',
        editTitle: '',

        init() {
            const data = this.$el.dataset;
            this.base = data.base;
            this.store = data.store;
            this.createTitle = data.createTitle;
            this.editTitle = data.editTitle;
            this.program = data.program ? JSON.parse(data.program) : null;
            this.draft = data.draft ? JSON.parse(data.draft) : {};
            this.sync();

            window.addEventListener('track-dialog-open', (event) => {
                if (! event.detail || event.detail.id !== 'program-dialog') {
                    return;
                }

                this.program = event.detail.program || null;
                this.draft = {};
                this.sync();
            });
        },

        sync() {
            this.unit = this.field('unit') || 'admin';

            const active = this.field('is_active');
            this.active = active === '' ? true : String(active) === '1';
        },

        field(name) {
            if (this.draft[name] !== undefined && this.draft[name] !== null) {
                return this.draft[name];
            }

            return this.program && this.program[name] !== undefined && this.program[name] !== null ? this.program[name] : '';
        },

        programId() {
            return this.program ? this.program.id : '';
        },

        // A programme holding sections keeps its code (ProgramController::update refuses a change),
        // so the field is read-only rather than an invitation to a refusal.
        codeLocked() {
            return Boolean(this.program && String(this.program.locked) === '1');
        },

        title() {
            return this.program ? this.editTitle : this.createTitle;
        },

        action() {
            return this.program ? `${this.base}/${this.program.id}` : this.store;
        },
    };
}
