/**
 * T079 — dialog open/close with a focus trap.
 *
 * Two things SC-014 requires, and both are the kind that are easy to half-do:
 *
 *   1. Focus is trapped while the dialog is open. Without it, Tab walks out of the dialog and into
 *      the page behind the scrim, where the user is operating controls they cannot see.
 *   2. Focus returns to whatever opened the dialog. Without it, every confirmation drops a keyboard
 *      user back at the top of the document and they re-traverse the page to get where they were.
 *
 * The trigger is remembered on open rather than assumed to be document.activeElement at close time,
 * because by then it may have been removed from the DOM by whatever the dialog did.
 */
const FOCUSABLE = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

export default function trackDialog(initiallyOpen = false) {
    return {
        open: initiallyOpen,
        trigger: null,

        // A dialog open on arrival is one the server sent back with errors (x-track-dialog). Focus
        // goes to the first field that failed, so the next keystroke fixes it.
        init() {
            if (! this.open) {
                return;
            }

            this.$nextTick(() => {
                const invalid = this.$refs.panel?.querySelector('[aria-invalid="true"]');
                (invalid ?? this.focusable()[0] ?? this.$refs.panel)?.focus();
            });
        },

        show(trigger = null) {
            this.trigger = trigger instanceof HTMLElement ? trigger : document.activeElement;
            this.open = true;

            // $nextTick, because the panel is x-show'd and is not focusable until Alpine has made
            // it visible.
            this.$nextTick(() => {
                const first = this.focusable()[0] ?? this.$refs.panel;
                first?.focus();
            });
        },

        // The open / close events name a dialog by id; each dialog answers only its own. A method,
        // because the CSP build of Alpine cannot evaluate an `if` in the template.
        onOpen(event) {
            if (event.detail && event.detail.id === this.$root.id) {
                this.show(event.target);
            }
        },

        onClose(event) {
            if (event.detail && event.detail.id === this.$root.id) {
                this.close();
            }
        },

        close() {
            if (! this.open) {
                return;
            }

            this.open = false;

            // Only restore to a trigger still in the document. A dialog that deleted the row it was
            // launched from would otherwise focus a detached node and the browser would fall back
            // to <body>, silently losing the user's place.
            if (this.trigger && document.contains(this.trigger)) {
                this.trigger.focus();
            }

            this.trigger = null;
        },

        focusable() {
            return Array.from(this.$refs.panel?.querySelectorAll(FOCUSABLE) ?? [])
                .filter((el) => el.offsetParent !== null);
        },

        trapFocus(event) {
            const items = this.focusable();

            if (items.length === 0) {
                return;
            }

            const first = items[0];
            const last = items[items.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (! event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    };
}
