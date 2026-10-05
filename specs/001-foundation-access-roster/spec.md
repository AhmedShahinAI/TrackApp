# Feature Specification: Foundation — Access Control & Academic Roster

**Feature Branch**: `001-foundation-access-roster`

**Created**: 2026-09-26

**Status**: Draft

**Input**: PRD.md v1.0 section 10 "Phase 1 — Foundation", covering features P0-F001,
P0-F002, P0-F010, P0-F003, P0-F004, the shared bilingual layout shell, and the typed
value-storage performance spike.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Staff Get Accounts And Sign In (Priority: P1)

The college initiatives office needs to hand out access. Ms. Maha (Super Admin) opens the
user administration screen and creates an account for each branch coordinator and
instructor, entering their Arabic and English names, institutional email, an initial
password, and their role. For a coordinator she picks the branches they oversee; for an
instructor she may link them to sections now or leave that to the roster import. Each
person signs in with their email and the initial password, sees a recommendation to change
it, and can change it from their profile whenever they choose. Nobody can create their own
account. When someone leaves, Ms. Maha deactivates their account and their past work stays
attributed to them.

**Why this priority**: Nothing else in the platform is reachable without accounts and
sign-in. It is the only story that delivers value entirely on its own, and every later
story is tested through an account created here.

**Independent Test**: Create one coordinator and one instructor account, sign in as each,
change a password, deactivate one account and confirm sign-in is refused while the
deactivated person's name still appears on the records they created. No other part of the
platform needs to exist.

**Acceptance Scenarios**:

1. **Given** Ms. Maha is signed in as Super Admin, **When** she submits a new instructor
   with Arabic name, English name, a unique institutional email, an 8-character password
   containing a letter and a digit, and the instructor role, **Then** the account is
   created, a confirmation appears, and an accountability entry records who created it.
2. **Given** an account exists with an email already in use by another account — active or
   deactivated — **When** Ms. Maha tries to create it, **Then** the save is refused with an
   inline message on the email field and no account is created.
3. **Given** Dr. Faisal has never signed in, **When** he signs in with correct credentials,
   **Then** he lands on his own starting page and sees a dismissable recommendation to
   change his initial password that does not block him from working.
4. **Given** Dr. Faisal has signed in before, **When** he signs in again, **Then** the
   first-sign-in recommendation does not appear.
5. **Given** Dr. Faisal is signed in, **When** he submits his current password, a new
   password meeting the policy, and a matching confirmation, **Then** the password is
   updated, every other session he has open stops working, and a confirmation appears.
6. **Given** Dr. Faisal enters a new password identical to his current one, **When** he
   submits, **Then** the change is refused with a specific reason.
7. **Given** anyone enters a wrong email or a wrong password, **When** they submit,
   **Then** they see one generic message that does not reveal which field was wrong.
8. **Given** five failed sign-in attempts for the same email from the same network address
   within one minute, **When** a sixth is attempted, **Then** attempts are refused for 60
   seconds and a countdown is shown.
9. **Given** an account has been deactivated, **When** that person attempts to sign in,
   **Then** access is refused with a message telling them to contact the administrator.
10. **Given** Ms. Maha resets someone's password, **When** she confirms, **Then** all of
    that person's sessions stop working and the reset is recorded.
11. **Given** anyone visits a self-registration address, **When** the page is requested,
    **Then** it does not exist.
12. **Given** a signed-in person is idle for 60 minutes, **When** they next act, **Then**
    they must sign in again.

---

### User Story 2 - Each Role Sees Only Its Own Data (Priority: P2)

Dr. Noura coordinates the Zulfi — Female branch. Everything she opens — lists, searches,
exports, any address she types directly — shows only her branch. Dr. Faisal sees only the
sections he currently teaches, plus any section Ms. Maha has explicitly shared with him,
which is labelled as shared access. Ms. Maha sees everything. When someone tries to reach
data outside their scope, the platform behaves as though the record does not exist, so
nobody can learn what exists in another branch by guessing addresses. Ms. Maha can change
someone's role or branches, and the change takes effect on that person's very next action.

**Why this priority**: The platform holds student personal data across six
gender-segregated branches under PDPL. Scoping must be in place before any real roster
data is loaded, so it precedes the academic structure and import stories.

**Independent Test**: With two branches and two coordinators seeded, sign in as each
coordinator and confirm that every list, search, export, and directly typed address shows
only their own branch, and that the other branch's records behave as nonexistent.

**Acceptance Scenarios**:

1. **Given** Dr. Noura coordinates Zulfi — Female only, **When** she opens any list of
   sections, students, instructors, or enrollments, **Then** only Zulfi — Female records
   appear and totals count only those records.
2. **Given** a section belongs to Majmaah — Male, **When** Dr. Noura requests that
   section's address directly, **Then** the platform responds that the page does not exist
   and the attempt is recorded.
3. **Given** Dr. Faisal teaches sections 214 and 215, **When** he opens his section list,
   **Then** exactly those sections appear and no other instructor's sections do.
4. **Given** Ms. Maha grants Dr. Faisal access to section 305, **When** he opens his
   section list, **Then** section 305 appears marked as shared access.
5. **Given** a person lacks the permission an action requires, **When** they attempt it,
   **Then** they are refused with a full-page message and a link back to their starting
   page, and the refusal is recorded.
6. **Given** Ms. Maha changes Dr. Noura's branches, **When** Dr. Noura takes her next
   action, **Then** her visible data matches the new branches and the change is recorded
   with its previous and new values.
7. **Given** a new role is needed, **When** it is introduced by defining which permissions
   it holds, **Then** it works without changing how any existing screen or rule behaves.
8. **Given** any list or export exists, **When** a coordinator or instructor uses it,
   **Then** the same scope limits apply as on screen — the export cannot contain a record
   the person cannot see on screen.

---

### User Story 3 - Every Sensitive Change Is Accountable (Priority: P3)

Ms. Maha needs to answer "who changed this, and when". Every sensitive action — creating or
editing or deactivating an account, changing a role or branch assignment, resetting a
password, creating or editing academic records, changing a section's instructor, changing a
term's state, adding or withdrawing a student, running an import, and every refused access
attempt — leaves one permanent entry recording who did it, what they did, which record was
affected, the values before and after, when, from which network address, and with which
browser. Ms. Maha can filter these entries and export the result. Each term, branch,
section, and student page shows its own activity, limited to what the viewer may see.
Entries can never be edited or removed.

**Why this priority**: The audit trail must exist before the records it protects are
created, and it is the institution's accreditation evidence. It is ranked after scoping
because a coordinator's activity view must already be scope-limited.

**Independent Test**: Perform one account creation, one role change, and one refused access
attempt, then confirm exactly three entries exist with complete before-and-after values,
that filtering by person and date range finds them, and that no available action edits or
removes them.

**Acceptance Scenarios**:

1. **Given** any sensitive action succeeds, **When** it completes, **Then** exactly one
   entry exists for it carrying actor, action, record type, record identity, previous
   values, new values, timestamp, network address, and browser.
2. **Given** the accountability entry cannot be stored, **When** an action is attempted,
   **Then** the action does not take effect at all and the person is told to retry.
3. **Given** entries exist, **When** anyone attempts to change or remove one through any
   available screen or address, **Then** no such capability exists.
4. **Given** one million entries exist, **When** Ms. Maha filters by person and date range,
   **Then** results appear within 2 seconds.
5. **Given** Dr. Noura opens a section's activity view, **When** the list loads, **Then**
   only activity she is permitted to see appears.
6. **Given** a student's record is edited, **When** the entry is inspected, **Then** both
   the previous and the new value of every changed field are present.

---

### User Story 4 - The Academic Structure Is Set Up (Priority: P4)

Ms. Maha sets up the academic world the initiatives will run in. She creates the academic
term — name, dates, and state — and marks one term as the current one. She defines the
branches, each with its campus and gender, adding or renaming or deactivating them freely
as the college changes. She adds courses, then sections, where each section belongs to a
term, a branch, and a course and carries its own section number and its current instructor.
Section 123 in Majmaah — Male and section 123 in Zulfi — Female never collide. She adds
students, each identified once by their University ID no matter how many sections they are
in, and enrolls them into sections. When a student leaves a section she withdraws them with
a reason rather than deleting them. When teaching changes hands she reassigns the section,
and the previous instructor's work stays credited to them.

**Why this priority**: Initiatives cannot exist without terms, branches, courses, sections,
students, and enrollments. It is ranked after accountability so that every structural
change made here is already recorded.

**Independent Test**: Create a term, two branches, one course, two sections with the same
section number in different branches, one student enrolled in both, then withdraw the
student from one section and reassign one section's instructor. Confirm the duplicate
section number is accepted, a genuinely duplicate section is refused, and nothing is
destroyed by the withdrawal or reassignment.

**Acceptance Scenarios**:

1. **Given** two sections differ in term, branch, or course, **When** they carry the same
   section number, **Then** both are accepted.
2. **Given** a section already exists for a given term, branch, course, and section number,
   **When** an identical one is submitted, **Then** it is refused with a message naming the
   conflict, and the refusal holds even if the check is bypassed at the screen level.
3. **Given** a student has a University ID, **When** they are enrolled into any number of
   sections across the term, **Then** exactly one student record exists for that ID.
4. **Given** a student is enrolled in one section, **When** they are enrolled in another
   section of the same term, **Then** both enrollments are active.
5. **Given** one term is marked current, **When** another is marked current, **Then** the
   previous one is no longer current and exactly one current term exists.
6. **Given** a branch is renamed, added, or deactivated, **When** the change is saved,
   **Then** it takes effect without any change to how the platform is built.
7. **Given** a branch or term holds records, **When** removal is attempted, **Then**
   permanent removal is refused and only deactivation or archiving is offered.
8. **Given** a section has an instructor and existing records, **When** the instructor is
   changed, **Then** access moves to the new instructor, the change is retained in the
   section's instructor history, every existing record keeps its original author, and the
   change is recorded.
9. **Given** a student is enrolled, **When** they are withdrawn, **Then** a date and a
   reason are required, the enrollment is marked withdrawn, and the student record survives.
10. **Given** a user, student, branch, or term is removed, **When** the removal completes,
    **Then** the record is marked removed rather than erased and its history remains
    readable.

---

### User Story 5 - A Whole Roster Loads From One File (Priority: P5)

Ms. Maha receives one spreadsheet per term from the registration unit. She uploads it and
the platform walks her through six steps. It reads the header row and proposes which column
means what, which she confirms or corrects. It then checks every row without saving
anything: rows duplicated inside the file, University IDs that do not match the expected
format, missing required values, branches it does not recognise, courses it does not
recognise — which she may choose to create — instructors who have no account yet — which
she may choose to create or skip — and existing students whose name differs from the file.
She sees a summary of what will be created versus matched, plus the first hundred rows with
each row's status. Only when she confirms does anything get written. Processing happens in
the background while she works elsewhere, and she is told when it finishes. She gets counts
and a downloadable file listing every rejected row with its row number, column, submitted
value, and the reason. Uploading the same file twice creates nothing new.

**Why this priority**: This is the largest single time saving in the release and the only
realistic way to load thousands of sections and students, but it depends on the academic
structure existing first.

**Independent Test**: Upload one file containing a deliberate mix of valid rows, in-file
duplicates, malformed University IDs, an unknown branch, an unknown course, an unregistered
instructor, and a name mismatch. Confirm nothing is written before confirmation, that every
defect type produces a row-level reason, and that re-uploading the same file afterwards
creates zero new records.

**Acceptance Scenarios**:

1. **Given** a spreadsheet or comma-separated file within the size and row limits, **When**
   it is uploaded, **Then** it is accepted, stored where it is not publicly reachable, and
   an import record is created in an uploaded state.
2. **Given** a file exceeds 20 MB, exceeds 50,000 rows, or is of an unsupported type,
   **When** upload is attempted, **Then** it is refused before storage with a message
   naming the limit or the accepted types.
3. **Given** a file is uploaded, **When** the mapping step opens, **Then** a proposed
   column mapping is shown and every required target must be mapped before continuing.
4. **Given** mapping is confirmed, **When** checking runs, **Then** it runs in the
   background, the screen stays usable, and progress is visible.
5. **Given** checking has finished, **When** the preview opens, **Then** it shows counts of
   new sections, new students, new enrollments, matched existing records, warnings, and
   errors, plus the first 100 rows each with a status.
6. **Given** checking has found problems, **When** the preview is inspected, **Then** each
   of in-file duplicates, malformed University IDs, missing required values, unknown
   branches, unknown courses, unregistered instructors, and name mismatches appears as a
   row-level error or warning with a reason written in the reader's language.
7. **Given** the preview has not been confirmed, **When** the underlying records are
   inspected, **Then** no term, branch, course, section, student, or enrollment has been
   created or changed.
8. **Given** the preview is confirmed, **When** the import runs, **Then** it processes in
   the background in chunks, links records that already exist instead of duplicating them,
   and reports processed, imported, duplicate, and error counts on completion.
9. **Given** an import has finished, **When** the error report is downloaded, **Then** it
   is a spreadsheet containing row number, column, submitted value, and reason for every
   rejected row.
10. **Given** a file has already been imported for the same term, **When** it is uploaded
    again, **Then** a warning identifies it as previously imported and, if continued, the
    result reports zero new records.
11. **Given** background processing fails partway, **When** the import record is inspected,
    **Then** it is marked failed, records committed in completed chunks remain, and the
    import can be resumed from the chunk that failed.
12. **Given** a row's section cannot be resolved, **When** the import completes, **Then**
    that row is rejected with the reason that the section could not be resolved.
13. **Given** a file of 10,000 rows, **When** it is checked and imported, **Then** both
    complete within 5 minutes with the screen remaining usable throughout.
14. **Given** any import runs, **When** it completes, **Then** an accountability entry
    records the file name, the row counts, and who ran it.

---

### User Story 6 - Instructors Load Their Own Section Lists (Priority: P6)

Dr. Faisal's section roster is missing three students who registered late. Rather than
waiting for a central import, he opens his own section, uploads a short file containing
just University IDs and names, and reviews the same check-and-preview steps before
anything is saved. Students who already exist anywhere in the platform are linked to his
section rather than duplicated; if the name in his file differs from the stored one, the
stored name is kept and he is warned. He can only do this for sections he teaches.

**Why this priority**: It removes a dependency on the central office for routine roster
corrections, but the central import must work first and instructors can be unblocked
manually in the meantime.

**Independent Test**: As an instructor, upload a two-column file to an assigned section
containing one brand-new student, one student who already exists with a different name, and
one duplicate row; then attempt the same upload against a section taught by someone else.

**Acceptance Scenarios**:

1. **Given** Dr. Faisal teaches a section, **When** he uploads a file of University IDs and
   names to it, **Then** the same six steps apply and no student is created before he
   confirms.
2. **Given** a student in his file already exists in the platform, **When** the import
   runs, **Then** the existing student is linked to his section and no second student
   record is created.
3. **Given** an existing student's name in the file differs from the stored name, **When**
   the import runs, **Then** the stored name is kept and a warning is shown.
4. **Given** the same student appears twice in his file, **When** checking runs, **Then**
   the second occurrence is rejected as a duplicate naming its row number.
5. **Given** a section is taught by another instructor, **When** Dr. Faisal attempts to
   import into it, **Then** he is refused and the attempt is recorded.
6. **Given** Dr. Faisal has no assigned sections, **When** he opens his section list,
   **Then** he sees a message explaining that no sections are assigned yet and whom to
   contact.

---

### User Story 7 - Everyone Works In Their Own Language (Priority: P7)

Dr. Noura works in Arabic; a colleague prefers English. Each chooses their language and the
entire interface follows — navigation, menus, tables, forms, messages, and error text —
laid out right-to-left for Arabic and left-to-right for English. Every screen delivered in
the stories above behaves identically in both. Status is never signalled by colour alone.
Every field tells them what is wrong as they go, every action shows it is working, and every
save confirms itself.

**Why this priority**: The primary users work in Arabic, so this is not optional — but it
is a property of the screens built in stories 1 through 6 rather than a screen of its own,
so it is delivered alongside each of them and verified last.

**Independent Test**: Switch language on each screen delivered by the earlier stories and
confirm complete translation, correct mirroring, and identical behaviour; then confirm every
status indicator carries a text label and every form validates, loads, errors, and confirms
as specified.

**Acceptance Scenarios**:

1. **Given** a person selects Arabic, **When** any screen loads, **Then** all text is
   Arabic and the layout, tables, and navigation are mirrored right-to-left.
2. **Given** a person selects English, **When** any screen loads, **Then** all text is
   English and the layout reads left-to-right.
3. **Given** any screen in either language, **When** it is inspected, **Then** no untranslated
   text appears.
4. **Given** any status is displayed, **When** it is shown, **Then** a text label accompanies
   the colour.
5. **Given** a form field loses focus or a form is submitted, **When** a value is invalid,
   **Then** the reason appears next to that field in the reader's language.
6. **Given** any action is triggered, **When** it is in progress, **Then** the control shows
   it is working and cannot be triggered again; lists and pages show placeholders while
   loading.
7. **Given** a recoverable failure occurs, **When** it surfaces, **Then** a retry is offered
   and no internal detail or trace is exposed.
8. **Given** a save, import, or other change succeeds, **When** it completes, **Then** a
   confirmation appears and the visible state changes.
9. **Given** a high-impact action such as withdrawing a student or deactivating an account,
   **When** it is triggered, **Then** a confirmation step restates the impact before it
   proceeds.
10. **Given** any screen, **When** it is used at 1280 px and at 1920 px width, **Then** it
    renders correctly in both languages; below 768 px, tabular views become stacked cards.
11. **Given** a keyboard-only user, **When** they work through any screen, **Then** every
    control is reachable and operable.
12. **Given** numbers and dates are displayed, **When** either language is active, **Then**
    digits are 0-9 and dates read DD/MM/YYYY.

---

---

### User Story 8 - Courses And Staff Load In Bulk (Priority: P8)

*Added by Amendment 1 (2026-09-27), decision C-05.*

Ms. Maha is setting up a new term and has a spreadsheet of forty courses with the programs each
belongs to, and another of thirty new instructors and coordinators. Rather than typing each one
into a form, she uploads each file through the same six steps she already knows from the roster
import — the platform proposes the column mapping, checks every row without saving anything, shows
her what will be created against what already exists, and only writes when she confirms. The
accounts it creates cannot be used until she resets each password, so nobody gains access she did
not deliberately grant.

**Why this priority**: It removes the slowest part of term setup, but it is pure convenience — every
course and account it creates can already be created by hand through User Story 1 and User Story 4.
It therefore comes last, and the release is viable without it.

**Independent Test**: Upload a course file containing one new course, one existing code, and one row
naming an unknown program; then upload a staff file containing one new instructor, one email already
in use, and one coordinator with no branch named. Confirm nothing is written before confirmation,
each defect yields a row-level reason, and every created account is refused sign-in until its
password is reset.

**Acceptance Scenarios**:

1. **Given** a course file with code, both names, and program associations, **When** Ms. Maha
   completes the six steps, **Then** new courses are created, existing codes are matched rather
   than duplicated, and each course is associated with the programs named.
2. **Given** a course row naming a program that does not exist, **When** checking runs, **Then**
   the row is flagged and Ms. Maha chooses whether to create the program or skip the row.
3. **Given** a staff file, **When** the import commits, **Then** each account exists with its role
   and, for coordinators, its branches, and each is unusable until its password is reset.
4. **Given** a staff row whose email is already held by any account, active or deactivated,
   **When** checking runs, **Then** the row is rejected as a duplicate naming the email.
5. **Given** a staff row with the coordinator role and no branch named, **When** checking runs,
   **Then** the row is rejected with the reason that a coordinator requires at least one branch.
6. **Given** an instructor attempts either import, **When** they request it, **Then** they are
   refused and the attempt is recorded.

---

---

> ### Amendment 2 (2026-09-27) — student technical support channel
>
> User Stories 9 to 13 were merged in from what was briefly a separate feature,
> `002-student-support`, at the Product Owner's direction on 2026-09-27. The source of the
> decisions is [specs/changes/CR-002-student-support.md](../changes/CR-002-student-support.md);
> the governing rules are constitution v1.1.0's "Public endpoints" subsection, added for this work.
>
> These five stories carry priorities P1 to P5 **within the support channel**, not within the
> feature as a whole. Against the feature's own ordering they sit **after** User Story 7, because
> every one of them depends on the roster, the branch scope, the audit mechanism, and the component
> library that Stories 1 to 7 build. The support channel is additive: the PRD's Phase 1 acceptance
> gate does not depend on it.

### User Story 9 - A Student Reports A Problem (Priority: P1)

Abdulrahman finds his name misspelled in a certificate the college issued through TRACK. Today he has
no route to report it: he tells his instructor, who forwards it by email, and it may or may not reach
whoever can fix it. Instead he opens the support page linked from the TRACK sign-in screen, enters his
University ID, picks the category that fits, describes the problem, and optionally leaves a phone
number or email if he wants to be reached directly. The platform checks he is a real, currently
enrolled student, then accepts the request and shows him a reference number it tells him to keep. He
never creates an account, never chooses a password, and never signs in.

**Why this priority**: This is the whole reason the feature exists, and it delivers value the moment
it works — problems get captured in one place instead of dissolving into forwarded emails. Everything
else in this feature serves requests that this story creates.

**Independent Test**: Submit one request as a genuinely enrolled student and confirm it is recorded
with a reference number; submit one as a non-existent identifier and one as a student with no active
enrollment, and confirm both are refused identically. No staff-facing work needs to exist yet.

**Acceptance Scenarios**:

1. **Given** a student whose University ID exists and who holds an active enrollment in the current
   term, **When** they submit a category and a description, **Then** the request is recorded and a
   reference number is displayed with guidance to keep it.
2. **Given** the description is empty, **When** they submit, **Then** the request is refused with the
   reason shown beside the field, and nothing is recorded.
3. **Given** a University ID that does not exist, **When** they submit, **Then** the request is
   refused with **one generic message** that does not reveal whether the identifier exists.
4. **Given** a University ID that exists but holds **no active enrollment in the current term**,
   **When** they submit, **Then** the refusal is **identical** to scenario 3 — the two cases must be
   indistinguishable from outside.
5. **Given** a University ID whose format does not match the configured pattern, **When** they submit,
   **Then** the refusal names the expected format, because format is not a secret.
6. **Given** a request is accepted, **When** the reference number is generated, **Then** it is not
   derivable from any other reference number — consecutive requests do not produce consecutive
   references.
7. **Given** a student leaves the optional contact blank, **When** they submit, **Then** the request
   is accepted.
8. **Given** many submissions arrive from one network address in a short period, **When** the limit is
   reached, **Then** further submissions from that address are refused for a stated interval.
9. **Given** many submissions arrive for one University ID from different network addresses, **When**
   the limit is reached, **Then** further submissions for that identifier are refused independently of
   the address limit.
10. **Given** an automated script attempts submission, **When** it cannot complete the
    person-versus-script challenge, **Then** the request is refused.
11. **Given** any request is accepted, **When** it is inspected, **Then** the originating network
    address is recorded against it.
12. **Given** a student attempts to attach a file, **When** they look for the control, **Then** none
    exists — attachments are deliberately absent.

---

### User Story 10 - Staff See And Answer Requests (Priority: P2)

Ms. Maha opens her support queue and sees what has come in: who sent it, which branch they belong to,
which category, how long it has been waiting, and its current state. She opens one, reads it, writes a
response, and marks it resolved. Where a request is not something TRACK can act on, she rejects it and
must say why. Where it belongs to someone else, she reassigns it. Every one of these actions is
recorded against her name.

**Why this priority**: Without this story, User Story 9 is a black hole — requests arrive and nobody
answers. It is second only because a queue with nothing in it cannot be tested.

**Independent Test**: With three requests seeded, open the queue as a Super Admin, respond to one,
reject one with a reason, reassign the third, and confirm each action is recorded and the state
changed.

**Acceptance Scenarios**:

1. **Given** requests exist, **When** a Super Admin opens the queue, **Then** each shows the student,
   their branch, the category, the current state, and how long it has been waiting.
2. **Given** the queue is open, **When** the Super Admin filters by state, category, branch, or date
   range, **Then** only matching requests appear and the totals count only those.
3. **Given** a request in the initial state, **When** a staff member opens it, **Then** its state
   becomes "in progress" and the change is recorded with their name.
4. **Given** a request is open, **When** a staff member writes a response and saves, **Then** the
   response is recorded against their name and visible to the student.
5. **Given** a staff member rejects a request, **When** they submit without a reason, **Then** the
   rejection is refused; a reason is mandatory.
6. **Given** a request has been resolved, **When** it is closed, **Then** no further response can be
   added to it.
7. **Given** a request is reassigned to another staff member, **When** the change saves, **Then** both
   the previous and the new assignee are recorded.
8. **Given** an invalid state change is attempted — for example from the initial state directly to
   closed — **Then** it is refused.
9. **Given** a request carries a contact the student typed, **When** a staff member views it, **Then**
   it is **visibly labelled as unverified**, because nobody confirmed the student owns it.
10. **Given** any of these actions completes, **When** the accountability record is inspected, **Then**
    exactly one entry exists for it, carrying who acted, what changed, and when.

---

### User Story 11 - A Student Finds Out What Happened (Priority: P3)

Abdulrahman comes back a few days later. He opens the same support page, switches to the status tab,
enters his reference number and his University ID, and sees his request, its current state, and any
response staff have written. He sees nothing else — not another student's request, not internal notes,
not who is handling it.

**Why this priority**: It closes the loop and prevents the most likely complaint, which is "I reported
it and heard nothing". It comes third because the request and the response must exist before there is
a status worth checking.

**Independent Test**: With one responded-to request, retrieve it with the correct reference and ID;
then attempt retrieval with a correct reference and the wrong ID, and with a reference that does not
exist. Both attempts must fail identically.

**Acceptance Scenarios**:

1. **Given** a correct reference number and the matching University ID, **When** the student submits
   them, **Then** the request, its state, and every staff response are shown.
2. **Given** a correct reference number and a **different** student's University ID, **When** submitted,
   **Then** nothing is shown and the refusal is generic.
3. **Given** a reference number that does not exist, **When** submitted, **Then** the refusal is
   **identical** to scenario 2.
4. **Given** repeated status attempts from one network address, **When** the limit is reached, **Then**
   further attempts are refused for a stated interval — the status check is a guessing surface and is
   limited like submission.
5. **Given** a request has no response yet, **When** the student checks, **Then** the state is shown
   with a message explaining it is still being handled.
6. **Given** the status view is open, **When** it is inspected, **Then** it shows no assignee name, no
   internal note, and nothing belonging to any other student.

---

### User Story 12 - A Coordinator Sees Only Their Own Branch (Priority: P4)

Dr. Noura coordinates the Zulfi — Female branch. Her support queue shows requests from students in her
branch and no others. A request from a Majmaah student behaves, for her, as though it does not exist —
she cannot reach it by guessing its address. Ms. Maha, as Super Admin, sees everything.

**Why this priority**: It distributes the handling load and it is the data-protection boundary for this
feature. It is fourth because the queue must exist before it can be narrowed, and because a
single-handler deployment is viable without it.

**Independent Test**: With requests from students in two branches and a coordinator assigned to one,
confirm the coordinator's queue, its totals, its filters, and any direct address attempt all show only
their own branch.

**Acceptance Scenarios**:

1. **Given** Dr. Noura coordinates one branch, **When** she opens the queue, **Then** only requests
   from students enrolled in that branch appear.
2. **Given** a request from another branch, **When** she requests its address directly, **Then** the
   platform responds as though it does not exist, and the attempt is recorded.
3. **Given** a student holds active enrollments in **more than one** branch, **When** coordinators of
   each of those branches open their queues, **Then** the request appears for **all** of them.
4. **Given** an instructor signs in, **When** they look for a support queue, **Then** they have no
   access to one.
5. **Given** Dr. Noura exports or filters her queue, **When** the result is produced, **Then** it
   contains no request she could not open individually.
6. **Given** a Super Admin opens the queue, **Then** every request appears regardless of branch.

---

### User Story 13 - Everyone Works In Their Own Language (Priority: P5)

The support page is the first part of TRACK a student ever sees, and most students read Arabic. Every
screen in this feature — the public form, the confirmation, the status view, the staff queue and the
request detail — is complete in Arabic laid out right-to-left and in English laid out left-to-right.
Status is never signalled by colour alone, every field explains its own error, and every screen works
by keyboard.

**Why this priority**: Not optional — the constitution makes Arabic and English equal and WCAG 2.1 AA
a floor. It is last because it is a property of the screens the earlier stories build, verified once
they exist.

**Independent Test**: Walk every screen in both languages and confirm complete translation, correct
mirroring, a text label beside every status colour, and full keyboard operation.

**Acceptance Scenarios**:

1. **Given** the public pages, **When** they load, **Then** they render in the language the visitor
   selects, defaulting to Arabic, laid out in the correct direction.
2. **Given** any screen in either language, **When** inspected, **Then** no untranslated text appears.
3. **Given** any state is displayed, **When** shown, **Then** a text label accompanies the colour.
4. **Given** a form field is invalid, **When** it loses focus and again on submission, **Then** the
   reason appears beside the field in the active language.
5. **Given** any screen, **When** used by keyboard alone, **Then** every control is reachable and
   operable.
6. **Given** any screen, **When** viewed at desktop and at phone width, **Then** it renders correctly
   in both languages.
7. **Given** a submission succeeds, **When** it completes, **Then** a confirmation appears and the
   visible state changes.
8. **Given** a rejection is about to be submitted, **When** the staff member confirms, **Then** a
   confirmation step restates the impact first.

---

### Edge Cases

- A University ID that does not match the configured format, entered by hand or arriving in
  a file: the entry or row is rejected and nothing is saved, with a message naming the
  expected format.
- An instructor with no assigned sections: an explanatory empty state naming whom to
  contact, not a blank page.
- A session that expires while a form is part-filled: the save is refused, the entered data
  is not lost from the page, and a sign-in prompt appears so the person can save afterwards.
- A coordinator opening another branch's record by typed address: the platform answers that
  the page does not exist, and the attempt is recorded.
- The same roster file uploaded twice: records are matched, nothing is duplicated, and the
  result reports zero new records.
- The same student listed twice within one section's file: the second occurrence is rejected
  as a duplicate naming its row.
- A section that duplicates an existing term, branch, course, and section number: refused,
  including when the screen-level check is bypassed.
- An instructor in the file with no account: the row is flagged and the Super Admin chooses
  to create the account or skip the row. A created account cannot be used until its password
  is reset.
- Deactivating a person who has history: marked inactive rather than erased, with their name
  still shown on their previous work.
- A row whose section cannot be resolved from the file's values: rejected with that reason.
- Background processing unavailable when an import is submitted: the import stays queued,
  the person is told it is waiting and will be reported when it finishes, and operations are
  alerted.
- A connection lost mid-save: the entered data is retained in the page, the save is retried
  automatically up to three times, and the outcome is reported either way.
- A file that is technically valid but contains zero data rows: accepted at upload and
  reported at preview as containing no importable rows.
- A term with no sections yet: structure screens show empty states rather than errors, and
  section creation is offered.
- An academic term that must exist before sections: section creation is unavailable, with a
  message saying a term must be created first, until at least one term exists.

Added by Amendment 1:

- A registrar export whose campus value resolves to more than one branch, or to none: the file is
  rejected before any row is processed, naming the campus value that could not be resolved.
- A registrar export whose course name matches several courses, or none: the affected rows are
  flagged and the operator chooses the course or skips them, rather than the platform guessing.
- A registrar export with no metadata block above the header row: rejected at upload with a message
  saying the file does not look like a registration-unit export and naming the other import modes.
- A student withdrawn inside TRACK whose registrar status later arrives as regular in an import:
  both values are kept and both are shown; neither overwrites the other, and the TRACK withdrawal
  and its reason survive.
- A program with no sections yet: it can be deactivated outright, since nothing depends on it.
- A section whose program is deactivated after the section was created: the section keeps its
  program and displays it, and the deactivated program stops appearing in the create and edit
  pickers.
- A course import row associating a course with a deactivated program: flagged as a warning, and
  the association is created only if the operator confirms.

Added by Amendment 2 (student support channel):

- **No current term is set**: submission is refused with a generic message, because active enrollment
  cannot be established. The condition is surfaced to administrators rather than to the student.
- **The student's only enrollment is withdrawn**: treated as no active enrollment — refused, and
  indistinguishably from a non-existent identifier.
- **A student submits the same problem twice**: both requests are accepted and recorded. No automatic
  merging, which would require judging that two free-text descriptions mean the same thing.
- **Two requests are submitted seconds apart for the same identifier**: both accepted if within the
  limit; the reference numbers reveal no relationship.
- **A description of extreme length**: refused above a stated limit, with the limit named.
- **A description containing markup or script**: stored and displayed as plain text; no markup is ever
  interpreted when shown to staff.
- **Staff responds while the student is viewing the status**: the student sees the response on their
  next retrieval. No live update is promised.
- **A request whose student is later soft-deleted**: the request survives and remains readable by
  staff, with the student shown as inactive.
- **A request whose student's enrollment later moves to another branch**: visibility follows the
  student's current active enrollments, so the new branch's coordinator gains access.
- **A closed request the student replies to**: no such action exists; the student cannot add to a
  request after submitting it. Raising a new request is the only route.
- **A request older than the retention period**: anonymised rather than deleted — the request, its
  category, and its responses survive for trend analysis with the link to the student severed.
- **The person-versus-script challenge is unavailable**: submission is refused rather than allowed
  through, because the challenge is a stated protection and failing open would remove it silently.

## Requirements *(mandatory)*

### Functional Requirements

#### Accounts and sign-in

- **FR-001**: The platform MUST NOT offer any means for a person to create their own
  account, and the conventional self-registration address MUST NOT exist.
- **FR-002**: Only a Super Admin MUST be able to create, edit, deactivate, or reset the
  password of an account.
- **FR-003**: Account creation MUST capture Arabic full name, English full name, email,
  an initial password, and a role.
- **FR-004**: When the role is branch coordinator, account creation MUST require at least
  one branch; when it is instructor, section links MUST be optional at creation and
  establishable later.
- **FR-005**: The platform MUST reject an email already held by any account, whether active
  or deactivated.
- **FR-006**: Passwords MUST be at least 8 characters and contain at least one letter and
  one digit, enforced both at the screen and on the server.
- **FR-007**: Passwords MUST be stored only in an irreversible hashed form; a plaintext
  password MUST never appear in stored data or in any log.
- **FR-008**: Sign-in MUST accept email and password and MUST verify the account is active.
- **FR-009**: A failed sign-in MUST return one message that does not disclose whether the
  email or the password was wrong.
- **FR-010**: After five failed attempts for the same email from the same network address
  within one minute, further attempts MUST be refused for 60 seconds with a visible
  countdown.
- **FR-011**: A deactivated account MUST be refused sign-in with a message directing the
  person to the administrator.
- **FR-012**: On a person's first successful sign-in the platform MUST record that fact and
  MUST show a non-blocking recommendation to change the initial password; on later sign-ins
  the recommendation MUST NOT appear.
- **FR-013**: A signed-in person MUST be able to change their own password by supplying
  their current password, a new password meeting the policy, and a matching confirmation.
- **FR-014**: A new password identical to the current password MUST be refused.
- **FR-015**: Changing or resetting a password MUST end all of that person's other active
  sessions.
- **FR-016**: A session MUST end after 60 minutes without activity.
- **FR-017**: Sign-in MUST land the person on the starting page belonging to their role.
- **FR-018**: Deactivating an account MUST preserve that person's authorship on all of
  their existing records, entries, and decisions.

#### Authorization and data scope

- **FR-019**: Permissions MUST be granular, individually named capabilities, and roles MUST
  be collections of those permissions.
- **FR-020**: The three roles Super Admin, branch coordinator, and instructor MUST be
  defined as permission collections, and introducing a further role MUST require only
  defining which permissions it holds — no change to existing screens or rules.
- **FR-021**: Every action MUST be denied unless a permission explicitly allows it.
- **FR-022**: Data visibility MUST be limited automatically for every retrieval: a
  coordinator to their assigned branches, an instructor to the sections they currently teach
  plus any explicitly shared with them, and a Super Admin to everything.
- **FR-023**: Scope limits MUST apply identically on screens, in lists, in searches, in
  exports, and when an address is typed directly.
- **FR-024**: An attempt to reach a record outside the requester's scope MUST be answered
  as though the record does not exist, so that the record's existence is not disclosed.
- **FR-025**: An attempt to perform an action the person lacks permission for MUST be
  refused with a full-page explanation and a link back to their starting page.
- **FR-026**: A Super Admin MUST be able to grant an instructor access to a specific
  additional section, and that section MUST be visibly labelled as shared access.
- **FR-027**: A change to a person's role, branches, or section access MUST take effect on
  that person's next action.

#### Accountability trail

- **FR-028**: Each of the following MUST produce exactly one accountability entry: account
  created, edited, deactivated; role, branch, or section access changed; password reset;
  password changed; term created, edited, or state-changed; branch, course, section,
  student, or enrollment created or edited; section instructor changed; student withdrawn;
  import run; refused access attempt.
- **FR-029**: Each entry MUST record the actor, the action, the record type, the record
  identity, the values before the change, the values after the change, the moment it
  happened, the originating network address, and the browser used.
- **FR-030**: An entry MUST be stored as part of the same all-or-nothing unit of work as
  the action it describes, so that if the entry cannot be stored the action does not take
  effect and the person is asked to retry.
- **FR-031**: No screen and no address MUST offer any means to change or remove an entry.
- **FR-032**: A Super Admin MUST be able to filter entries by person, action, record type,
  date range, and branch, and export the filtered result.
- **FR-033**: Term, branch, section, and student views MUST each show their own activity,
  limited to what the viewer is permitted to see.
- **FR-034**: Entries MUST be retained for at least 5 years.

#### Academic structure

- **FR-035**: A Super Admin MUST be able to create and edit academic terms with an Arabic
  name, an English name, an academic year, a semester, a start date, an end date, and a
  state of draft, active, closed, or archived.
- **FR-036**: Exactly one term MUST be markable as the current term, and marking a term
  current MUST remove that mark from the previously current term.
- **FR-037**: At least one term MUST exist before any section can be created, and the
  platform MUST say so when none does.
- **FR-038**: Branches MUST be records carrying an Arabic campus name, an English campus
  name, a gender, and an active flag, and MUST be addable, renamable, and deactivatable
  without any change to how the platform is built.
- **FR-039**: Courses MUST be records carrying a code, an Arabic name, and an English name.
- **FR-040**: A section MUST carry its own internal identity plus a term, a branch, a
  course, a section number, and optionally a current instructor. *(Its restriction of creation to the
  Super Admin is **superseded by FR-170**, Amendment 4.)*
- **FR-041**: The combination of term, branch, course, and section number MUST be unique,
  enforced both when saving and by the stored data's own constraints. *(**Superseded by FR-176**,
  Amendment 4: uniqueness now applies to **non-rejected** sections only, so a rejection releases the
  number for reuse.)*
- **FR-042**: A section number alone MUST NOT be treated as unique.
- **FR-043**: A student MUST be identified by a University ID that is unique across the
  platform, with an Arabic name and an English name, and MUST exist as exactly one record
  regardless of how many enrollments they hold.
- **FR-044**: The University ID MUST be validated against a configurable pattern whose
  default is 9 digits.
- **FR-045**: A student MUST be enrollable in more than one section within the same term.
- **FR-046**: An enrollment MUST carry a status of active or withdrawn with the relevant
  dates, and the pairing of a student and a section MUST be unique.
- **FR-047**: Withdrawing a student MUST require a reason, MUST record the date, and MUST
  NOT remove the student record.
- **FR-048**: Changing a section's instructor MUST move access to the new instructor, MUST
  add an entry to that section's instructor history, and MUST leave the original author on
  every record already created.
- **FR-049**: Removal of a user, student, branch, or term MUST mark the record removed
  rather than erase it, and permanent removal of any record holding history MUST be refused.
- **FR-050**: Section gender MUST be derived from the section's branch rather than stored
  separately.

#### Roster import

- **FR-051**: The platform MUST provide a six-step import sequence: upload, column mapping,
  checking, preview, import, and report.
- **FR-052**: Central import MUST accept term, branch, gender, course code, course name,
  section number, instructor name, instructor email, student University ID, and student
  name, and MUST create or link branch, section, instructor, student, and enrollment.
- **FR-053**: Section import MUST accept University ID and student name, and MUST attach
  students only to the one selected section.
- **FR-054**: Section import MUST be permitted only for a section the requester currently
  teaches or has been granted access to; any other target MUST be refused and the attempt
  recorded.
- **FR-055**: Upload MUST accept only spreadsheet and comma-separated files, MUST verify
  the file's type by both its name and its actual content, MUST refuse files over 20 MB or
  over 50,000 rows, and MUST store accepted files where they are not publicly reachable.
- **FR-056**: The mapping step MUST read the header row, propose a mapping for each column,
  allow correction, and require every mandatory target to be mapped before continuing.
- **FR-057**: Checking MUST identify: rows duplicated within the file; University IDs not
  matching the configured pattern; missing required values; unrecognised branches;
  unrecognised courses; instructors without an account; and existing students whose stored
  name differs from the file.
- **FR-058**: For unrecognised courses and for instructors without an account, the person
  running the import MUST choose whether to create them or skip the affected rows.
- **FR-059**: An account created from an import MUST be unusable until its password is
  reset.
- **FR-060**: The platform MUST NOT create or change any term, branch, course, section,
  student, or enrollment before the preview is confirmed.
- **FR-061**: The preview MUST show counts of records to be created, records matched to
  existing ones, warnings, and errors, plus the first 100 rows each with its own status.
- **FR-062**: Every problem found during checking MUST be reported at the level of the
  individual row, with a reason stated in the reader's language.
- **FR-063**: Checking and importing MUST run outside the request that triggers them, so
  the screen stays usable, and MUST report progress and completion.
- **FR-064**: Import MUST commit in chunks of 500 rows as all-or-nothing units, and MUST
  link records that already exist rather than creating duplicates.
- **FR-065**: If processing fails partway, the import MUST be marked failed, rows committed
  in completed chunks MUST remain, and the import MUST be resumable from the chunk that
  failed.
- **FR-066**: Duplicate detection MUST operate at four levels: University ID, the section's
  term-branch-course-number key, the student-and-section pairing, and the
  student-and-assignment pairing.
- **FR-067**: An uploaded file matching a previously imported file for the same term MUST
  produce a warning before proceeding, and proceeding MUST create zero new records.
- **FR-068**: A row whose section cannot be resolved MUST be rejected with that reason.
- **FR-069**: When an existing student's name differs from the file, the stored name MUST
  be kept and a warning raised.
- **FR-070**: The report MUST state processed, imported, duplicate, and error counts.
- **FR-071**: A spreadsheet error report MUST be downloadable containing row number,
  column, submitted value, and reason for every rejected row.
- **FR-072**: The person who ran an import MUST be told when it finishes.
- **FR-073**: Every import MUST produce an accountability entry carrying the file name, the
  row counts, and who ran it.

#### Interface, language, and shared behaviour

- **FR-074**: Every screen MUST be available in Arabic laid out right-to-left and in
  English laid out left-to-right, selectable per person and remembered.
- **FR-075**: No user-facing text MUST be fixed to a single language within the platform's
  construction; all such text MUST come from the language resources.
- **FR-076**: Layout, navigation, tables, and controls MUST mirror correctly in
  right-to-left.
- **FR-077**: Status MUST never be conveyed by colour alone; a text label MUST always
  accompany it.
- **FR-078**: Fields MUST validate when they lose focus and again on submission, with the
  reason shown next to the field, and every screen-level rule MUST be repeated on the
  server.
- **FR-079**: Every action control MUST show that it is working and MUST be unavailable
  until the outcome returns; lists and pages MUST show placeholders while loading; work
  running in the background MUST show a persistent progress indicator refreshed every 5
  seconds.
- **FR-080**: Recoverable failures MUST offer a retry; refusals for lack of permission MUST
  show a full-page message with a link back; no message MUST expose internal detail or a
  trace.
- **FR-081**: Every successful change MUST confirm itself for about 4 seconds and MUST
  update the visible state immediately.
- **FR-082**: High-impact actions — withdrawing a student, deactivating an account,
  changing a section's instructor, changing a term's state — MUST require a confirmation
  step that restates the impact.
- **FR-083**: Every role MUST reach its own work within at most two steps from its starting
  page, and drill-down views MUST show the path taken.
- **FR-084**: Digits MUST be 0-9 and dates MUST read DD/MM/YYYY in both languages.
- **FR-085**: Every screen MUST render correctly at 1280 px and 1920 px width and MUST
  become stacked cards below 768 px, and MUST meet WCAG 2.1 AA including full keyboard
  operation.
- **FR-086**: Text MUST be stored so that Arabic, including all its characters, is
  preserved exactly.

#### Value-storage capacity validation

- **FR-087**: Before this feature is accepted, the storage design intended for per-student
  initiative values MUST be demonstrated to hold 1,500,000 values and still satisfy the
  platform's stated retrieval and aggregation timings, with the measurement recorded.
- **FR-088**: If the demonstration fails to meet the stated timings, the finding and the
  proposed remedy MUST be recorded before later work depending on that design begins.

#### Amendment 1 (2026-09-27) — additions accepted from the approved UI design system

These requirements were added after the approved design system revealed functionality this
specification did not carry. Each was decided by the Product Owner on 2026-09-27; the decision
record is [ui-conflicts.md](./ui-conflicts.md). Numbering continues from FR-088 so that existing
cross-references in the plan, tasks, contracts, and quickstart remain valid.

**Academic programs** (decision C-04 — accept into this feature)

- **FR-089**: An academic program MUST be a record carrying a code unique within the institution,
  an Arabic name, an English name, a specialty unit of either administrative or technical, and an
  active flag.
- **FR-090**: Every section MUST belong to exactly one academic program.
- **FR-091**: A course MUST be associable with more than one academic program, and a program with
  more than one course.
- **FR-092**: A program holding sections MUST NOT be permanently removable; deactivation MUST be
  the only removal path, and the platform MUST say so when removal is attempted.
- **FR-093**: Creating, editing, and deactivating a program MUST each produce an accountability
  entry.
- **FR-094**: The program a section belongs to MUST be visible on the section list and the section
  detail, and MUST be available as a filter on the section list.
- **FR-095**: Only a Super Admin MUST be able to create, edit, or deactivate a program.

**Registrar-export import mode** (decision C-09 — accept as a third mode)

- **FR-096**: The platform MUST accept the registration unit's own export format as a third import
  mode, in addition to the central and section modes.
- **FR-097**: In this mode the platform MUST read the metadata block that sits above the header
  row, carrying campus, degree, course, activity, and section number, and MUST NOT require those
  values as columns.
- **FR-098**: In this mode the data columns MUST be sequence, university identifier, student name,
  and academic status, and no other column MUST be required.
- **FR-099**: The platform MUST resolve the branch from the metadata block's campus value, which
  combines campus and gender in one string, and MUST reject the file with a clear reason when the
  campus cannot be resolved to exactly one branch.
- **FR-100**: The platform MUST match the course by its Arabic name when no course code is
  present, MUST report an unambiguous match, and MUST flag the row for the operator's decision
  when the name matches more than one course or none.
- **FR-101**: The academic term and the instructor MUST be supplied by the person running the
  import rather than read from the file, because the registrar export carries neither.
- **FR-102**: Every guarantee that applies to the other import modes — nothing written before
  preview confirmation, chunked resumable commit, row-level reasons, the downloadable error report,
  duplicate detection, and the accountability entry — MUST apply identically to this mode.

**Course and staff bulk import** (decision C-05 — accept as fourth and fifth modes)

- **FR-103**: The platform MUST provide a course import mode accepting course code, Arabic name,
  English name, and the programs the course belongs to.
- **FR-104**: The platform MUST provide a staff account import mode accepting Arabic name, English
  name, email, role, and, for coordinators, the branches they oversee.
- **FR-105**: An account created through the staff import mode MUST be unusable until its password
  is reset, exactly as FR-059 requires of accounts created during roster import.
- **FR-106**: Both modes MUST run through the same six steps and MUST honour every guarantee named
  in FR-102.
- **FR-107**: Course import MUST be permitted only to a holder of the course-import permission, and
  staff import only to a holder of the staff-import permission; neither MUST be available to an
  instructor.

**Additional stored attributes** (decision C-10 — all accepted)

- **FR-108**: A section MUST carry a degree level, and the value MUST be selectable rather than
  free text.
- **FR-109**: A section MUST carry an activity type, and the value MUST be selectable rather than
  free text.
- **FR-110**: An enrollment MUST carry the registrar's academic status, distinct from and stored
  separately from the enrollment status that TRACK itself sets. The registrar value MUST be one of
  regular, withdrawn, or excused.
- **FR-111**: Withdrawing a student inside TRACK MUST NOT overwrite the registrar's academic
  status, and the registrar's academic status arriving in an import MUST NOT overwrite a
  TRACK-recorded withdrawal or its reason. Both MUST be visible on the section roster.
- **FR-112**: An enrollment MUST carry the sequence number from the registrar export where one was
  supplied, and it MUST be optional so that enrollments created by hand or by the other import
  modes remain valid.
- **FR-113**: A user record MUST carry the moment of their most recent successful sign-in, it MUST
  be updated on every successful sign-in, and it MUST be visible on the accounts list.

#### Amendment 2 (2026-09-27) — student technical support channel

Numbering continues from FR-113 so that every existing cross-reference in the plan, tasks,
contracts, and quickstart remains valid.

#### Submission

- **FR-114**: The platform MUST provide a publicly reachable page, requiring no sign-in, through which
  a student raises a technical support request, and MUST link to it from the sign-in screen.
- **FR-115**: Submission MUST require a University ID, a category, and a description.
- **FR-116**: Submission MUST accept an optional contact value that the student types themselves. The
  platform MUST NOT read a contact from any stored record.
- **FR-117**: The University ID MUST be validated against the configured pattern, and a format failure
  MUST name the expected format.
- **FR-118**: The platform MUST verify that the University ID identifies an existing student **and**
  that the student holds an active enrollment in the current term, and MUST record nothing when either
  check fails.
- **FR-119**: A verification failure MUST return one generic message that reveals neither whether the
  identifier exists, nor whether it is enrolled, nor which check failed.
- **FR-120**: The category MUST be constrained to a configurable list, and adding a category MUST NOT
  require rebuilding the platform.
- **FR-121**: The description MUST be limited to a stated maximum length, and the limit MUST be named
  when exceeded.
- **FR-122**: An accepted request MUST be given a reference number that is not derivable from any
  other reference number, and it MUST be displayed with guidance to retain it.
- **FR-123**: The platform MUST record the originating network address against every accepted request.
- **FR-124**: The platform MUST NOT create any account, credential, or session for a student under any
  circumstance.
- **FR-125**: The platform MUST NOT accept file attachments on a request.

#### Abuse protection

- **FR-126**: Submission MUST be limited per originating network address, over a configurable period.
- **FR-127**: Submission MUST be limited per University ID, over a configurable period, **independently
  of** the address limit, so that neither one network nor one identifier can be used to flood or to
  probe.
- **FR-128**: Submission MUST require completion of a challenge that distinguishes a person from an
  automated script.
- **FR-129**: When the challenge cannot be presented or evaluated, submission MUST be refused rather
  than allowed through.
- **FR-130**: A refusal caused by a limit MUST state that a limit was reached and when it may be
  retried, without revealing which limit.
- **FR-131**: Status retrieval MUST be limited per originating network address, because it is a
  guessing surface.

#### Status retrieval

- **FR-132**: A student MUST be able to retrieve their request by presenting **both** the reference
  number and the University ID.
- **FR-133**: Retrieval MUST show the request, its current state, and every staff response.
- **FR-134**: Retrieval MUST NOT show the assignee, any internal note, or anything belonging to another
  student.
- **FR-135**: A mismatched pair, and a reference number that does not exist, MUST produce **identical**
  generic refusals.
- **FR-136**: A request with no response yet MUST show its state with an explanation that it is still
  being handled.

#### Staff queue and visibility

- **FR-137**: The platform MUST provide a queue of requests to staff holding the support-view
  permission, showing for each the student, their branch, the category, the state, and the waiting time.
- **FR-138**: The queue MUST be filterable by state, category, branch, and date range.
- **FR-139**: A Super Admin MUST see every request.
- **FR-140**: A Branch Coordinator MUST see a request when its student holds an active enrollment in
  one of that coordinator's branches, and MUST NOT see any other request.
- **FR-141**: Where a student holds active enrollments in more than one branch, the request MUST be
  visible to a coordinator of **each** of those branches.
- **FR-142**: An Instructor MUST have no access to any request.
- **FR-143**: A request outside the requester's visibility MUST be answered as though it does not
  exist, so that its existence is not disclosed, and the attempt MUST be recorded.
- **FR-144**: Visibility limits MUST apply identically on screen, in filters, in exports, and when an
  address is typed directly.
- **FR-145**: A contact value supplied by a student MUST be displayed to staff with a visible marker
  that it is unverified.

#### Handling and state

- **FR-146**: A request MUST progress through the states: new, in progress, resolved, closed; and MUST
  be able to enter a rejected state from new or in progress.
- **FR-147**: Opening a new request MUST move it to in progress and record who did so.
- **FR-148**: Any state change not permitted by FR-146 MUST be refused.
- **FR-149**: Rejecting a request MUST require a written reason, and the reason MUST be visible to the
  student.
- **FR-150**: A closed request MUST accept no further response.
- **FR-151**: Staff MUST be able to write a response, which MUST be recorded against their name and
  made visible to the student.
- **FR-152**: Staff holding the assign permission MUST be able to reassign a request, and both the
  previous and the new assignee MUST be recorded.
- **FR-153**: A description or a response MUST be stored and displayed as plain text, with no markup
  interpreted at any point.
- **FR-154**: Permissions MUST be granular and separate for viewing, responding, assigning, and
  closing, granted in full to the Super Admin and limited to viewing and responding for a Branch
  Coordinator.

#### Accountability

- **FR-155**: Each of request created, response written, state changed, and request reassigned MUST
  produce exactly one accountability entry recording the actor, the action, the affected request, the
  values before and after, the moment, and the originating network address.
- **FR-156**: An accountability entry MUST be stored as part of the same all-or-nothing unit of work as
  the action it describes, so that if the entry cannot be stored the action does not take effect.
- **FR-157**: A request created by a student MUST be attributed to that student rather than to a staff
  member, and the entry MUST record that it arrived through the public channel.
- **FR-158**: A refused attempt to reach a request outside the requester's visibility MUST be recorded.

#### Retention

- **FR-159**: A request MUST be retained for two years from its creation and MUST then be anonymised:
  the request, its category, its state history, and its responses survive, while the link to the
  student and the stored contact and network address are removed.
- **FR-160**: Anonymisation MUST NOT remove the accountability entries, which follow their own
  retention.

#### Interface and language

- **FR-161**: Every screen MUST be available in Arabic laid out right-to-left and in English laid out
  left-to-right, and the public pages MUST default to Arabic and offer the choice without a sign-in.
- **FR-162**: No user-facing text MUST be fixed to a single language within the platform's construction.
- **FR-163**: The public pages MUST use a standalone layout carrying no navigation to any authenticated
  area.
- **FR-164**: State MUST never be conveyed by colour alone; a text label MUST always accompany it.
- **FR-165**: Fields MUST validate when they lose focus and again on submission, with the reason shown
  beside the field, and every screen-level rule MUST be repeated on the server.
- **FR-166**: A successful submission MUST confirm itself and MUST change the visible state.
- **FR-167**: Rejecting a request MUST require a confirmation step that restates the impact.
- **FR-168**: Every screen MUST meet WCAG 2.1 AA, MUST be fully operable by keyboard, and MUST render
  correctly at desktop and at phone width in both languages.
- **FR-169**: No message MUST expose internal detail, a trace, or an identifier the viewer cannot
  otherwise see.

#### Amendment 4 (2026-09-28) — section creation with approval by originator

Decided by the Product Owner on 2026-09-28, resolving [ui-conflicts.md](./ui-conflicts.md) C-22. The
rule: **who created the section determines whether it needs approving.** Numbering continues from
FR-169.

**This amends FR-040 and FR-041**, which gave section creation to the Super Admin alone and stated the
section key as an unconditional four-column uniqueness. Both are superseded by FR-170 and FR-176 below;
the original wording is retained in place for traceability and marked as amended.

- **FR-170**: An **instructor**, a **branch coordinator**, and a **Super Admin** MUST each be able to
  create a section. This supersedes FR-040's restriction of creation to the Super Admin.
- **FR-171**: A section created by a **Super Admin** MUST become active immediately and MUST NOT require
  approval.
- **FR-172**: A section created by an **instructor** or by a **branch coordinator** MUST require approval
  before it becomes active.
- **FR-173**: A section MUST carry a lifecycle state of `pending`, `returned`, `rejected`, or `active`.
  A section created by a Super Admin starts `active`; one created by an instructor or coordinator starts
  `pending`.
- **FR-174**: A **pending**, **returned**, or **rejected** section MUST NOT be targetable by an
  initiative, MUST NOT appear in initiative targeting previews, and MUST NOT be counted in any
  completion figure.
- **FR-175**: A pending or returned section MUST be able to hold enrollments, so that the creator can
  attach a roster at the moment of creation rather than waiting for approval.
- **FR-176**: At most **one non-rejected** section MUST exist per combination of term, branch, course,
  and section number, enforced by the stored data's own constraints. Any number of **rejected** sections
  MUST be permitted for the same combination, so that a rejection releases the section number for reuse.
  This supersedes FR-041's unconditional uniqueness.
- **FR-177**: An **instructor's** section MUST be approvable, returnable, or rejectable by a coordinator
  of that section's branch, or by a Super Admin.
- **FR-178**: A **coordinator's** section MUST be approvable, returnable, or rejectable **only by a
  Super Admin**. No person MUST be able to approve a section they created, and no coordinator MUST be
  able to approve a section created by a coordinator of the same branch.
- **FR-179**: **Returning** a section MUST require a written reason, MUST set its state to `returned`,
  and MUST make it editable again by its creator, who MUST be able to resubmit it.
- **FR-180**: **Rejecting** a section MUST require a written reason and MUST set its state to `rejected`.
  A rejected section MUST NOT be resubmittable; creating the section again is the only route.
- **FR-181**: A rejected section MUST be retained rather than erased, with its reason, its creator, and
  its decision history, and MUST be excluded from default section lists.
- **FR-182**: Approving, returning, rejecting, requesting, and resubmitting a section MUST each produce
  exactly one accountability entry recording the actor, the previous and new state, and the reason where
  one was given.
- **FR-183**: A creator MUST be able to see the state of every section they created and the reason for
  any return or rejection.
- **FR-184**: The approval queue MUST show a reviewer only the sections within their own scope: a
  coordinator sees pending sections from their branches, a Super Admin sees all.
- **FR-185**: Section **approval** and assignment **review** MUST be distinguishable in every interface
  and in every accountability entry, because they are different decisions at different levels. Section
  states MUST use the vocabulary in FR-173, and assignment states MUST keep the vocabulary specified for
  the submission and review workflow.

### Key Entities

- **Organization**: The institution, held as a hierarchy with a single college record in
  this release so that additional colleges can be introduced later without restructuring.
- **User**: A member of staff who signs in. Holds Arabic and English names, a unique email,
  a hashed password, a language preference, an active flag, whether they have signed in
  before, and a removal marker. Holds roles, may oversee branches, may teach sections.
- **Role**: A named collection of permissions.
- **Permission**: A single named capability that an action requires.
- **Coordinator Branch Assignment**: Which branches a coordinator oversees; a coordinator
  may oversee several and a branch may have several coordinators.
- **Branch**: A campus-and-gender combination with Arabic and English campus names, a
  gender, and an active flag. Holds many sections.
- **Academic Term**: Arabic and English names, academic year, semester, start and end
  dates, a state, and whether it is the current term. Holds sections.
- **Course**: A code with Arabic and English names. Holds sections.
- **Section**: A term, branch, course, section number, and current instructor, with its own
  internal identity. Unique on term, branch, course, and section number. Holds enrollments.
- **Section Instructor History**: Who taught a section, from when to when, and who changed
  it.
- **Section Access Grant**: An explicit grant of one section to one instructor, by whom and
  when.
- **Student**: A unique University ID with Arabic and English names and a removal marker.
  Holds enrollments.
- **Section Enrollment**: One student in one section, with a status of active or withdrawn,
  the relevant dates, and a withdrawal reason. Unique on student and section.
- **Import Job**: One import attempt: who ran it, its mode, its target section or term, the
  stored file, a fingerprint of the file's content, the confirmed column mapping, its state,
  and counts of processed, imported, duplicate, and error rows.
- **Import Error**: One rejected row of one import: row number, column, submitted value, and
  reason.
- **Audit Entry**: One accountability record: actor, action, record type, record identity,
  values before, values after, moment, network address, and browser. Never changed, never
  removed.
- **Setting**: A named platform configuration value, such as the University ID pattern and
  the upload size limits, changeable without rebuilding.

Added by Amendment 1:

- **Academic Program**: A named course of study with a unique code, Arabic and English names, a
  specialty unit of administrative or technical, and an active flag. Holds many sections; associated
  with many courses.
- **Course–Program Association**: Which programs a course belongs to; a course may belong to
  several and a program may hold several courses.

Added by Amendment 2:

- **Support Request**: One problem raised by one student. Carries its reference number, the student it
  belongs to, the term in which enrollment was verified, its category, the description, an optional
  unverified contact, its current state, its assignee, the originating network address, and when it was
  raised. Holds many responses.
- **Support Response**: One reply written by one staff member on one request, with its text, its author,
  and when it was written. Never edited, never removed.
- **Request Category**: A configurable label constraining what a student may choose. Adding one is a
  configuration change, not a code change.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A Super Admin creates a working account for a coordinator or an instructor in
  under 2 minutes, and that person signs in successfully on their first attempt.
- **SC-002**: A coordinator and an instructor each confirm, across every list, search,
  export, and directly typed address available to them, that they can reach no record
  outside their own branch or sections — zero cross-scope disclosures in a deliberate
  attempt to find one.
- **SC-003**: Every action that must be accountable produces exactly one complete entry,
  verified by performing all such actions once and finding the same number of entries, each
  carrying values before and after.
- **SC-004**: Filtering one million accountability entries by person and date range returns
  results within 2 seconds.
- **SC-005**: A roster file of at least 5,000 rows is loaded end to end, producing an error
  report, without any manual correction of the platform's stored data afterwards.
- **SC-006**: At least 97% of rows in a representative roster file are loaded successfully
  on the first attempt.
- **SC-007**: A 10,000-row file is checked and loaded within 5 minutes, and the person who
  started it can keep working on other screens throughout.
- **SC-008**: Re-loading an identical roster file creates zero additional students,
  sections, or enrollments.
- **SC-009**: Every rejected row appears in a downloadable report naming its row number,
  column, submitted value, and reason, with no rejected row missing.
- **SC-010**: An instructor adds three late-registering students to their own section in
  under 5 minutes without assistance from the central office.
- **SC-011**: Sections carrying the same section number in different branches, courses, or
  terms coexist, while a genuine duplicate is refused — verified for both cases.
- **SC-012**: A student enrolled in several sections exists exactly once, verified by
  searching the platform for their University ID.
- **SC-013**: Every screen is complete in both Arabic and English with no untranslated text
  and correct mirroring, verified screen by screen.
- **SC-014**: Every screen is fully operable by keyboard alone and meets WCAG 2.1 AA.
- **SC-015**: Any screen reaches first visible content within 2 seconds for 95 of every 100
  loads on the college network.
- **SC-016**: Every available action has been exercised by both a permitted and a forbidden
  role, with the forbidden role refused in every case.
- **SC-017**: The value-storage design holds 1,500,000 values and still meets the stated
  retrieval and aggregation timings, with the measurement recorded and reviewed.
- **SC-018**: Changing a section's instructor leaves every previously created record still
  credited to its original author, verified on a section holding existing records.

Added by Amendment 1:

- **SC-019**: Every section in the platform resolves to exactly one academic program, verified by
  finding zero sections with no program after the roster import completes.
- **SC-020**: The registration unit's own export file, unmodified, loads end to end through the
  registrar mode and produces an error report — verified with the actual file already in hand, not a
  reconstruction of it.
- **SC-021**: A program holding sections cannot be permanently removed through any available path,
  while a program holding none can be deactivated.
- **SC-022**: A student carrying both a registrar academic status and a TRACK withdrawal displays
  both on the section roster, and neither value overwrites the other through any import or any
  withdrawal.
- **SC-023**: Forty courses and thirty staff accounts load in under 10 minutes total through the
  bulk modes, against a baseline of typing each into a form.
- **SC-024**: Every account created by the staff import mode is refused sign-in until its password
  is reset, verified for each created account.

Added by Amendment 2 (student support channel):

- **SC-025**: A student raises a support request in under 2 minutes from opening the page, without
  assistance and without creating anything.
- **SC-026**: A non-existent identifier, an identifier with no active enrollment, and a withdrawn-only
  enrollment produce **byte-identical** refusals — verified by comparing the three responses.
- **SC-027**: Across 1,000 generated reference numbers, none can be derived from another, verified by
  checking for sequence, timestamp, or identifier patterns.
- **SC-028**: A coordinator confirms, across the queue, its filters, its exports, and directly typed
  addresses, that they can reach no request from outside their branches — zero disclosures in a
  deliberate attempt to find one.
- **SC-029**: A correct reference with the wrong University ID, and a reference that does not exist,
  produce identical refusals — verified by comparing the two responses.
- **SC-030**: Every action requiring accountability produces exactly one complete entry, verified by
  performing each once and counting.
- **SC-031**: Sustained automated submission from one network address is refused after the configured
  threshold, and the authenticated platform's response times are unaffected throughout.
- **SC-032**: Sustained submission for one University ID from many network addresses is refused after
  the configured threshold, demonstrating the two limits act independently.
- **SC-033**: Staff respond to a request in under 3 minutes from opening the queue.
- **SC-034**: A student retrieves their request's state and response on the first attempt using only
  the reference number and their University ID.
- **SC-035**: Every screen is complete in both Arabic and English with no untranslated text and correct
  mirroring, verified screen by screen.
- **SC-036**: Every screen is fully operable by keyboard alone and meets WCAG 2.1 AA with zero
  violations.
- **SC-037**: A request older than the retention period retains its category, state history, and
  responses while carrying no link to a student, no contact, and no network address.
- **SC-038**: An instructor has no route to any request, verified through the interface, filters,
  exports, and directly typed addresses.

Added by Amendment 4 (section creation with approval):

- **SC-039**: A section created by a Super Admin is usable immediately; a section created by an
  instructor or a coordinator cannot be targeted by an initiative until it is approved — verified for all
  three creator roles.
- **SC-040**: No person can approve a section they created, and no coordinator can approve a section
  created by a coordinator of the same branch — verified by attempting both.
- **SC-041**: After a section is rejected, the same term, branch, course, and section number can be used
  to create a new section, while two non-rejected sections with that combination are still refused.
- **SC-042**: An instructor creates a section with a roster attached and sees its state and any return
  reason without contacting anyone.
- **SC-043**: Section approval and assignment review are distinguishable in every interface and every
  accountability entry — verified by a reader who is shown one of each and asked which is which.

## Assumptions

- The Super Admin can obtain section, instructor, and student rosters as spreadsheet or
  comma-separated exports each term, and sample files are available in the first week so
  the column mapping can be tested against real data.
- Each branch is one campus and one gender, for example "Zulfi — Female", and a section's
  gender therefore follows from its branch rather than being stored on the section.
- University IDs are unique across the university and follow a configurable pattern whose
  default is 9 digits.
- Instructors and coordinators hold institutional email addresses, which serve as their
  sign-in identity.
- Per-term volume is up to 500 staff, 3,000 sections, 30,000 students, and 50 initiatives,
  and the design must tolerate ten times that without restructuring.
- Desktop is the primary usage context; tablet and mobile are supported but not optimised
  for.
- Two-factor authentication and self-service password recovery by email are deliberately
  excluded from this release; the Super Admin performs all password resets.
- Academic term states of closed and archived are stored and settable in this feature, but
  the enforcement that makes a closed term's data read-only is a later feature. Until then,
  terms are expected to remain in draft or active state, and no term will be closed during
  development.
- The notification centre is a later feature. In this feature, import completion is
  communicated by the import screen's own progress and result view, and a notification
  record is stored so that the later notification centre can surface it without rework.
- The storage design for per-student initiative values is created and measured in this
  feature so that its capacity is proven before later work depends on it, but no screen for
  defining or entering initiative data is included here.
- The configurable University ID pattern and upload size limits are stored as platform
  settings from this feature onward; the full settings management screen is a later feature,
  so these values are seeded and changeable by the Super Admin without a general settings
  interface.
- A provisioned environment with background processing available is a prerequisite for the
  import stories; without background processing, imports will queue and report that they are
  waiting rather than fail.
- Branch coordinator nominations for all six branches are not required for this feature;
  coordinator accounts can be created as nominations arrive.

Amended and added on 2026-09-27 (Amendment 1):

- **Superseded**: the earlier assumption that roster exports would arrive in a single flat
  ten-column shape. The registration unit's actual export carries four data columns plus a metadata
  block above the header row, and the platform now supports that shape directly (FR-096 to FR-102).
  The ten-column central mode is retained for files that do arrive in it.
- Every section belongs to exactly one academic program, and the college's programs are known and
  stable enough to be set up before the roster import runs — the import resolves a section's
  program, so programs must exist first.
- A campus value in a registrar export, such as "المجمعة (طلاب)", identifies exactly one branch by
  combining campus and gender. Where a campus label is ambiguous, the file is rejected rather than
  guessed at.
- Courses can be matched by Arabic name when the registrar export carries no course code. This
  assumes course names are distinct within the institution; where they are not, the operator
  resolves the ambiguity per row.
- The degree levels and activity types the platform offers are a known, closed list that the Super
  Admin can extend later through platform settings rather than a code change.
- The registrar's academic status and TRACK's own enrollment status answer different questions and
  are therefore stored and displayed separately, never reconciled into one value.

Added by Amendment 2 (student support channel):

- **The reply reaches the student through the status page, not by message.** The roster holds no
  contact details and outbound mail is a later dependency. A student returns with their reference
  number. Where a student supplied a contact, staff may use it manually and outside the platform. When
  outbound messaging becomes available it can use the stored contact with no change to what is stored.
  *This is the one assumption the Product Owner marked for confirmation.*
- The initial category list is: incorrect data · sign-in problem · section problem · general enquiry ·
  other. It is configuration, so it can be changed after launch without a release.
- A support request concerns the TRACK platform, not academic advice. Requests about grades, teaching,
  or registration are expected to be rejected with a reason pointing elsewhere; the platform does not
  attempt to route them automatically.
- Volume is low — the PRD targets fewer than 1 request per 10 active users per month — so the public
  pages are sized against burst abuse rather than sustained load.
- Students reach the page on a desktop or phone browser. No application is installed.
- A student belongs to one campus and one gender and therefore normally to one branch; the multi-branch
  rule in FR-141 exists for correctness rather than because it is expected to be common.
- The two-year retention period starts at creation rather than at closure, because a request abandoned
  without closure must not be retained indefinitely.
- Staff are notified of arriving requests within the platform. Outbound notification waits for the same
  dependency as student replies.

## Accepted Risks

Recorded here because it is a deliberate decision rather than an oversight, and because the
implementation must not silently "fix" it in a way that changes the design.

- **A person who knows a valid University ID can raise a request in that student's name.** The roster
  holds no second identifying factor — no date of birth, no national identifier, no contact — and
  University IDs are nine digits that run in sequence within a cohort, so knowing one reveals
  neighbours. A password would not have prevented this, because there is nothing to verify a claimant
  against; it would only have added a credential store and an activation flow.
  - **Why it is acceptable**: a request exposes no data, so the consequence is a falsely attributed
    request rather than a disclosure.
  - **Mitigations in place**: limits on both network address and identifier, the person-versus-script
    challenge, the recorded network address, and the absence of anything readable behind the channel.
  - **Revisit when**: university single sign-on becomes available, which would make genuine student
    authentication inexpensive.

Added by Amendment 4:

- A coordinator creating a section is an ordinary case, not an exception, so it goes through the same
  approval as an instructor's — with the Super Admin as the approver, because nobody approves their own
  work and a same-branch peer approval would be circular. **This was inferred from the decision rather
  than stated in it**; if a coordinator's section should instead be approvable by another coordinator, or
  should skip approval entirely, FR-178 is the requirement to change.
- A pending section may already hold enrollments, because the creator attaches the roster when they
  create it. Enrollments on a rejected section are retained with the section rather than removed, since
  the students themselves are never deleted.
- The section number of a rejected section returns to the pool. This is why FR-176 narrows uniqueness to
  non-rejected sections, and it is the one schema consequence of this amendment that is not obvious.
