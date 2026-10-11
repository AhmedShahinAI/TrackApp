/**
 * T086 — client-side sort and filter for the data table, without a reload.
 *
 * Both operate on the rendered DOM rather than on a JavaScript copy of the data. That is
 * deliberate: the table's cells may have been produced by Blade slots — a status badge, a formatted
 * date, a link — and a parallel JS dataset would have to reproduce all of that to sort or filter on
 * it. Sorting the rows that are actually there keeps one source of truth.
 *
 * Sort values come from `data-sort-value`, written by the Blade component from the raw value, so a
 * date sorts chronologically rather than by its rendered DD/MM/YYYY string and a number sorts
 * numerically rather than lexically ("10" before "9").
 */
export default function trackDataTable() {
    return {
        query: '',
        sortKey: null,
        sortAsc: true,
        visibleCount: 0,

        init() {
            this.visibleCount = this.rows().length;
        },

        // The sort indicator's icon. A method rather than a template literal in the markup, which the
        // CSP build of Alpine cannot evaluate; the sprite's URL comes from the table's data-sprite.
        sortIcon(key) {
            const name = this.sortKey === key ? (this.sortAsc ? 'arrow-up' : 'arrow-down') : 'arrow-up-down';

            return `${this.$root.dataset.sprite}#${name}`;
        },

        // A clickable row. `window` is out of reach of the CSP build's expressions.
        visit(url) {
            window.location.assign(url);
        },

        rows() {
            return Array.from(this.$refs.table?.querySelectorAll('tbody tr') ?? []);
        },

        filter() {
            const needle = this.query.trim().toLowerCase();
            let shown = 0;

            for (const row of this.rows()) {
                const match = needle === '' || row.textContent.toLowerCase().includes(needle);
                row.hidden = ! match;

                if (match) {
                    shown++;
                }
            }

            this.visibleCount = shown;
        },

        sort(key) {
            if (this.sortKey === key) {
                this.sortAsc = ! this.sortAsc;
            } else {
                this.sortKey = key;
                this.sortAsc = true;
            }

            const body = this.$refs.table.querySelector('tbody');
            const index = this.columnIndex(key);

            if (index < 0) {
                return;
            }

            const direction = this.sortAsc ? 1 : -1;

            const sorted = this.rows().sort((a, b) => {
                const av = this.sortValue(a, index);
                const bv = this.sortValue(b, index);

                // Numeric when both sides are numeric, so 9 sorts before 10. A mixed column falls
                // back to a locale-aware string compare, which is what Arabic names need.
                if (typeof av === 'number' && typeof bv === 'number') {
                    return (av - bv) * direction;
                }

                return String(av).localeCompare(String(bv), document.documentElement.lang || 'ar') * direction;
            });

            // Re-appending moves rather than clones, so event listeners on the rows survive.
            for (const row of sorted) {
                body.appendChild(row);
            }
        },

        columnIndex(key) {
            const headers = Array.from(this.$refs.table.querySelectorAll('thead th'));

            return headers.findIndex((th) => {
                const button = th.querySelector('.track-table__sort');

                return button?.getAttribute('x-on:click')?.includes(key)
                    || th.textContent.trim() === key;
            });
        },

        sortValue(row, index) {
            const cell = row.children[index];

            if (! cell) {
                return '';
            }

            const raw = cell.dataset.sortValue ?? cell.textContent.trim();

            if (raw !== '' && ! Number.isNaN(Number(raw))) {
                return Number(raw);
            }

            return raw;
        },
    };
}
