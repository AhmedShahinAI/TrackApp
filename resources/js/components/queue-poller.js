/**
 * T097 — the 5-second poller for queued work (FR-079).
 *
 * Only runs when the page says there is something to watch, via a
 * `<div data-track-poll="/url" data-track-poll-target="#id">` element. A poller that runs on every
 * page would be 720 requests an hour per open tab for nothing, and at 10,000 users that is the
 * kind of load nobody notices until it is the whole problem.
 *
 * Stops on its own when the server reports a terminal state, and pauses while the tab is hidden —
 * a background tab watching an import nobody is looking at is pure cost.
 */
const INTERVAL = 5000;
const TERMINAL = ['completed', 'failed'];

export default function registerQueuePoller() {
    const nodes = document.querySelectorAll('[data-track-poll]');

    for (const node of nodes) {
        poll(node);
    }
}

function poll(node) {
    const url = node.dataset.trackPoll;
    const target = node.dataset.trackPollTarget;

    let timer = null;

    const tick = async () => {
        if (document.hidden) {
            return;
        }

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (! response.ok) {
                return;
            }

            const payload = await response.json();

            node.dispatchEvent(new CustomEvent('track-poll', { detail: payload, bubbles: true }));

            if (target && payload.html) {
                document.querySelector(target)?.replaceChildren(
                    document.createRange().createContextualFragment(payload.html),
                );
            }

            if (TERMINAL.includes(payload.status)) {
                clearInterval(timer);
            }
        } catch {
            // A failed poll is not an error worth showing. The next tick tries again, and if the
            // network is genuinely gone the user has larger problems than this widget.
        }
    };

    timer = setInterval(tick, INTERVAL);
    tick();
}
