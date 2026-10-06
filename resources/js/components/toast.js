/**
 * T078 — the toast queue.
 *
 * Auto-dismisses after 4s, except on the error tone. An error carries a retry action (FR-080,
 * FR-081), and a message that vanishes before the user can press it is worse than no message at
 * all — they are left knowing something failed and with no way to try again.
 *
 * Pushed from anywhere with:
 *     window.dispatchEvent(new CustomEvent('track-toast', {
 *         detail: { tone: 'error', message: '...', retry: () => form.submit() },
 *     }));
 */
const TONE_ICONS = {
    success: 'circle-check',
    error: 'circle-alert',
    info: 'info',
};

const DISMISS_AFTER = 4000;

export default function trackToasts() {
    return {
        toasts: [],
        nextId: 1,

        init() {
            window.addEventListener('track-toast', (event) => this.push(event.detail ?? {}));
        },

        push({ tone = 'info', message = '', retry = null }) {
            const toast = {
                id: this.nextId++,
                tone,
                message,
                retry,
                icon: TONE_ICONS[tone] ?? TONE_ICONS.info,
            };

            this.toasts.push(toast);

            // Errors stay until dismissed. Everything else clears itself, because a success message
            // that needs acknowledging is a success message that interrupts the next action.
            if (tone !== 'error') {
                setTimeout(() => this.dismiss(toast.id), DISMISS_AFTER);
            }
        },

        dismiss(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },

        retry(toast) {
            this.dismiss(toast.id);

            if (typeof toast.retry === 'function') {
                toast.retry();
            }
        },
    };
}
