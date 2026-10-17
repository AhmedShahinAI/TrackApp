/**
 * T395 — the public support form: the proof-of-work challenge and the description counter.
 *
 * On load: GET /support/challenge, solve it with resources/js/pow (a worker, off the main thread), and
 * fill the two hidden fields. The submit button stays disabled until then; if the challenge cannot be
 * fetched or solved it stays disabled and says so — the form fails closed (FR-129).
 *
 * The solver is imported dynamically, so it is a separate same-origin chunk loaded by this page alone
 * rather than weight in every page's bundle.
 *
 * A challenge expires after `expires_in` seconds and is single-use, so a student who spends longer
 * than that writing gets a fresh one solved a minute before the old one lapses. A background tab may
 * run that timer late; if it fires after the challenge has lapsed, the server refuses with the
 * security-check message and the student's text is kept (old input), so one more submit succeeds.
 *
 * Written for Alpine's CSP build: no inline expressions in the markup beyond names, so every value the
 * template shows is a getter here, and every label is read from the form's data attributes.
 */
export function trackSupportForm() {
    return {
        challenge: '',
        solution: '',
        state: 'working',
        submitting: false,
        length: 0,
        max: 0,
        renewal: null,
        restored: null,

        init() {
            const data = this.$el.dataset;

            this.max = Number(data.descriptionMax || 0);

            const description = this.$el.querySelector('textarea[name="description"]');
            this.length = description ? description.value.length : 0;

            this.solve();

            // Back from the confirmation, the browser may restore this page from its back/forward
            // cache exactly as it was left: `submitting` still true, and a challenge the server has
            // already consumed (Phase 20 review). Start over as a fresh visit would.
            this.restored = (event) => {
                if (event.persisted) {
                    this.submitting = false;
                    this.solve();
                }
            };
            window.addEventListener('pageshow', this.restored);
        },

        destroy() {
            clearTimeout(this.renewal);
            window.removeEventListener('pageshow', this.restored);
        },

        async solve() {
            this.state = 'working';
            this.challenge = '';
            this.solution = '';

            try {
                const response = await fetch(this.$el.dataset.challengeUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) {
                    throw new Error('unavailable');
                }

                const issued = await response.json();
                const { solve } = await import('../pow/solve.js');
                const nonce = await solve(issued.challenge, issued.difficulty);

                this.challenge = issued.challenge;
                this.solution = String(nonce);
                this.state = 'ready';

                const renewIn = Math.max(30, Number(issued.expires_in || 0) - 60) * 1000;
                clearTimeout(this.renewal);
                this.renewal = setTimeout(() => this.solve(), renewIn);
            } catch {
                this.state = 'failed';
            }
        },

        count(event) {
            this.length = event.target.value.length;
        },

        submit(event) {
            if (this.notReady) {
                event.preventDefault();

                return;
            }

            this.submitting = true;
        },

        get notReady() {
            return this.state !== 'ready' || this.submitting;
        },

        get countLabel() {
            return (this.$root.dataset.countTemplate || '')
                .replace(':count', String(this.length))
                .replace(':max', String(this.max));
        },

        get statusLabel() {
            const data = this.$root.dataset;

            return { working: data.working, ready: data.ready, failed: data.failed }[this.state] || '';
        },
    };
}
