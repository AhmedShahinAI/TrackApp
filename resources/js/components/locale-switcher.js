/**
 * T097 — the AR/EN switch in the topbar's `end` slot.
 *
 * Posts rather than links. The locale is stored on the user's record (users.locale, FR-074), so
 * switching it is a state change and a GET that changes state is a GET that a prefetcher or a
 * crawler can trigger. The form carries the CSRF token like any other write.
 */
export default function registerLocaleSwitcher() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-track-locale]');

        if (! trigger) {
            return;
        }

        event.preventDefault();

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = trigger.dataset.trackLocaleAction;
        form.hidden = true;

        const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        for (const [name, value] of [['_token', token], ['locale', trigger.dataset.trackLocale]]) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    });
}
