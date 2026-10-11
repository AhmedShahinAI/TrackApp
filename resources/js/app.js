/**
 * T097 — the single JavaScript entry.
 *
 * Every Alpine component is registered here by name, so a Blade template can say `x-data="trackToasts"`
 * and nothing has to be imported per page. One bundle, one registration point: the alternative is
 * per-page scripts, and then a component works on the page where someone remembered the import.
 *
 * The drawer's open state is a global Alpine store, reached as `$store.shell.drawerOpen`, because
 * two sibling components share it: the topbar toggles it and the sidebar reads it.
 */
// The CSP build (security review R-01): no `new Function`, so the Content Security Policy needs no
// 'unsafe-eval'. It parses each expression itself — one expression, no statements, arrows, template
// literals or globals — which is why logic lives in the components below, not in the templates.
import Alpine from '@alpinejs/csp';

import trackToasts from './components/toast';
import trackDialog from './components/dialog';
import trackTabs from './components/tabs';
import trackDataTable from './components/data-table';
import trackImportWizard from './components/import-wizard';
import registerFieldValidation from './components/field-validation';
import registerQueuePoller from './components/queue-poller';
import { trackLoginForm, trackMappingForm, trackProgramDialog, trackUserDialog, trackUserTarget, trackWithdrawDialog } from './components/forms';

Alpine.data('trackToasts', trackToasts);
Alpine.data('trackDialog', trackDialog);
Alpine.data('trackTabs', trackTabs);
Alpine.data('trackDataTable', trackDataTable);
Alpine.data('trackImportWizard', trackImportWizard);
Alpine.data('trackLoginForm', trackLoginForm);
Alpine.data('trackMappingForm', trackMappingForm);
Alpine.data('trackProgramDialog', trackProgramDialog);
Alpine.data('trackUserDialog', trackUserDialog);
Alpine.data('trackUserTarget', trackUserTarget);
Alpine.data('trackWithdrawDialog', trackWithdrawDialog);

// Shared by the topbar toggle and the sidebar itself. A store rather than a magic property,
// because the topbar ASSIGNS to it and a magic is read-only — and rather than component state,
// because the two are siblings in the layout, not nested.
Alpine.store('shell', { drawerOpen: false });

// Registered before Alpine starts, so a form submitted during page load is still validated.
registerFieldValidation();

document.addEventListener('DOMContentLoaded', () => {
    registerQueuePoller();
});

window.Alpine = Alpine;
Alpine.start();
