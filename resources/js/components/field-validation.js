/**
 * T072 — validate a field on blur, and again on submit.
 *
 * Blur-only lets someone submit a form containing a field they never entered. Submit-only hands
 * them six errors at once after they thought they were finished. FR-078 wants both, which is also
 * what people expect from every other form they use.
 *
 * This checks only what the browser can check — required, type, pattern, length. Anything that
 * needs the database (a duplicate email, a section number already taken) is the server's answer,
 * and the server's answer is authoritative either way. This exists to save a round trip on the
 * obvious cases, not to duplicate the rules.
 */
export default function registerFieldValidation() {
    document.addEventListener(
        'blur',
        (event) => {
            const field = event.target;

            if (! (field instanceof HTMLElement) || ! field.matches('.track-input')) {
                return;
            }

            // A field the user has not filled in yet is not an error. Marking it one the first time
            // they Tab past means a form that starts out red.
            if (field.value === '' && ! field.required) {
                return;
            }

            applyValidity(field);
        },
        true, // capture: blur does not bubble
    );

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (! (form instanceof HTMLFormElement)) {
            return;
        }

        let firstInvalid = null;

        for (const field of form.querySelectorAll('.track-input')) {
            if (! applyValidity(field) && firstInvalid === null) {
                firstInvalid = field;
            }
        }

        if (firstInvalid) {
            event.preventDefault();
            firstInvalid.focus();
        }
    });
}

function applyValidity(field) {
    const valid = field.checkValidity();

    field.classList.toggle('track-input--error', ! valid);
    field.setAttribute('aria-invalid', valid ? 'false' : 'true');

    const wrapper = field.closest('.track-field');
    const existing = wrapper?.querySelector('[data-client-error]');

    if (valid) {
        existing?.remove();

        return true;
    }

    if (! wrapper) {
        return false;
    }

    // role="alert" so the message is announced when it appears. A visual-only message is no message
    // to a screen-reader user, which is the whole point of validating on blur.
    const message = existing ?? Object.assign(document.createElement('p'), {
        className: 'track-field__error',
        role: 'alert',
    });

    message.dataset.clientError = 'true';
    message.textContent = field.validationMessage;
    wrapper.appendChild(message);

    return false;
}
