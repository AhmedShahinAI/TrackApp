/**
 * T089 — tab switching with the ARIA keyboard pattern.
 *
 * Arrow keys move between tabs, Home and End jump to the ends, and only the selected tab is in the
 * document tab order (roving tabindex). The alternative — every tab tabbable — means a six-tab strip
 * costs six Tab presses to walk past, which is why the pattern exists.
 */
export default function trackTabs(initial = null) {
    return {
        current: initial,

        select(key) {
            this.current = key;
            this.$dispatch('track-tab-changed', { tab: key });
        },

        move(event) {
            const keys = ['ArrowRight', 'ArrowLeft', 'Home', 'End'];

            if (! keys.includes(event.key)) {
                return;
            }

            event.preventDefault();

            const tabs = Array.from(this.$el.parentElement.querySelectorAll('[role="tab"]'));
            const index = tabs.indexOf(this.$el);

            // The arrow keys follow the writing direction: in Arabic, ArrowRight moves to the tab
            // that is visually next, which is the one earlier in the DOM.
            const rtl = document.documentElement.dir === 'rtl';
            const forward = rtl ? 'ArrowLeft' : 'ArrowRight';

            let next = index;

            if (event.key === 'Home') {
                next = 0;
            } else if (event.key === 'End') {
                next = tabs.length - 1;
            } else if (event.key === forward) {
                next = (index + 1) % tabs.length;
            } else {
                next = (index - 1 + tabs.length) % tabs.length;
            }

            tabs[next].focus();
            tabs[next].click();
        },
    };
}
