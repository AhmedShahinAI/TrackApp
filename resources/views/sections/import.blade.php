{{--
    T257 — an instructor's student list for one section, step 1 of the same six-step wizard.

    The section is fixed in the header and is the job's for the rest of the import (SectionMode): the
    file carries two columns, university id and student name, and cannot name another section. The
    template download gives exactly those two headers, in the reader's language, so the mapping step
    suggests both without a correction.

    The reference's `SectionFilePicker` that tasks.md names is the initiatives screen's section picker,
    not this; the approved design for this entry is the section page's "استيراد كشف" action opening
    ImportWizard in section mode (SectionsScreens.jsx), and that is what this page is built to.
--}}

<x-app-layout :title="__('imports.modes.section')">
    @include('imports._wizard', ['job' => null, 'step' => 1, 'section' => $section, 'terms' => []])
</x-app-layout>
