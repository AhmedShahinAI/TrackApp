/**
 * T240 — the import wizard's client half.
 *
 * Deliberately thin. The server decides which step is showing and every check that matters; this
 * component does three things the server cannot do from where it sits:
 *
 *   1. **Answer a file choice immediately.** Extension and size are checked on selection so a 25 MB
 *      PDF is refused before it is uploaded, not after. The server repeats both checks and adds the
 *      content check — this is a courtesy, never the control (FR-055).
 *   2. **Accept a dropped file.** A drop sets the real <input>'s FileList, so the form submits the
 *      dropped file exactly as if it had been chosen.
 *   3. **Follow the queued work.** The shared poller (queue-poller.js) fetches the job every five
 *      seconds and dispatches `track-poll`; this listens, moves the bar and the counter, shows or
 *      hides the queue-waiting alert (C-13), and reloads the page when the status moves on — which
 *      lands the operator on the next step without them doing anything.
 */
export default function trackImportWizard() {
    // From the element's `data-wizard` in init(): the CSP build of Alpine cannot evaluate the
    // `JSON.parse(...)` that `@js($wizard)` compiles to, so the config travels as a data attribute.
    let config = {};

    return {
        status: null,
        progress: 0,
        processed: 0,
        total: 0,
        waiting: false,
        chunk: { current: 1, total: 1 },

        fileName: '',
        fileSize: '',
        clientError: '',
        dragging: false,
        submitting: false,

        init() {
            config = JSON.parse(this.$el.dataset.wizard || '{}');
            this.status = config.status ?? null;
            this.progress = config.progress ?? 0;
            this.processed = config.processed ?? 0;
            this.total = config.total ?? 0;
            this.waiting = Boolean(config.waiting);
            this.chunk = config.chunk ?? { current: 1, total: 1 };

            this.$el.addEventListener('track-poll', (event) => this.onPoll(event.detail ?? {}));
        },

        pick(event) {
            this.describe(event.target.files?.[0] ?? null);
        },

        drop(event) {
            this.dragging = false;

            const files = event.dataTransfer?.files;

            if (! files || files.length === 0) {
                return;
            }

            this.$refs.file.files = files;
            this.describe(files[0]);
        },

        describe(file) {
            this.clientError = '';

            if (! file) {
                this.fileName = '';
                this.fileSize = '';

                return;
            }

            this.fileName = file.name;
            this.fileSize = formatBytes(file.size);

            const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

            if (! ['xlsx', 'csv'].includes(extension)) {
                this.clientError = config.messages?.badType ?? '';
            } else if (config.maxBytes && file.size > config.maxBytes) {
                this.clientError = config.messages?.tooLarge ?? '';
            }
        },

        onPoll(payload) {
            const job = payload.data ?? {};

            this.progress = job.progress_percent ?? this.progress;
            this.processed = job.processed ?? this.processed;
            this.total = job.total_rows ?? this.total;
            this.waiting = Boolean(job.waiting);
            this.chunk = job.chunk ?? this.chunk;

            // The progress component is server-rendered; its bar and figure are moved in place
            // rather than re-rendered, so screen readers keep the same element.
            for (const rail of this.$root.querySelectorAll('.track-progress__rail')) {
                rail.setAttribute('aria-valuenow', String(this.progress));
                rail.querySelector('.track-progress__bar')?.style.setProperty('width', `${this.progress}%`);
            }

            for (const figure of this.$root.querySelectorAll('.track-progress__head .num')) {
                figure.textContent = `${this.progress}%`;
            }

            if (job.status && this.status && job.status !== this.status) {
                window.location.reload();
            }
        },

        counterText() {
            return (config.messages?.counter ?? '')
                .replace(':processed', this.processed.toLocaleString('en'))
                .replace(':total', this.total.toLocaleString('en'));
        },

        chunkText() {
            return (config.messages?.chunk ?? '')
                .replace(':current', String(this.chunk.current))
                .replace(':total', String(this.chunk.total));
        },
    };
}

function formatBytes(bytes) {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}
