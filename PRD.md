# TRACK Platform — Product Requirements Document

> **Academic Initiative Operations & Monitoring Platform**
> A central platform for creating, running, and monitoring academic initiatives within course sections across all college branches.

---

## 1. Project Identity

| Field | Value |
|---|---|
| **Project Name** | TRACK Platform |
| **Project Type** | Web Application — Internal Institutional Tool (authenticated staff; plus an unauthenticated student support form) |
| **Project ID** | PRD-001 |
| **Version** | v1.2 |
| **Status** | Draft |
| **Priority** | High |
| **Created Date** | 2026-09-25 |
| **Last Updated** | 2026-09-27 |
| **Owner** | Dr. Ahmed Ismail Shahin (Product Owner) |
| **Team Members** | Backend Developer (Laravel), Frontend Developer, UI/UX Designer, QA Engineer |
| **Tech Stack** | PHP 8.3+ · Laravel · MySQL 8 / MariaDB 10.11+ · Redis · Blade + Bootstrap 5 (RTL/LTR) + Alpine.js |
| **Repository** | [TBD] |
| **Git Branch Prefix** | NNN-feature-name |
| **PRD File Path** | docs/PRD.md |

---

## 2. Problem & Purpose

### 2.1 Problem Statement
Academic initiatives (professional certifications, pre/post assessments, training attendance, and similar programs) are currently tracked manually through separate Excel files for each section, branch, and instructor. This produces:

- Duplicated and inconsistent student data across files and terms.
- Mixing of data between academic terms and between initiatives.
- No real-time visibility of completion rates at the section, branch, gender, or initiative level.
- No reliable way to identify sections that have not started, are delayed, or are incomplete.
- Repeated data requests to instructors for information that already exists.
- No audit trail of who changed which student record and when.
- Reports for leadership, quality, and accreditation purposes that take days of manual consolidation.

### 2.2 Project Purpose
TRACK provides a single system that manages the full lifecycle of an academic initiative. It starts with the Super Admin creating the initiative and defining its data fields. The Super Admin then targets branches and sections. Instructors execute the initiative and enter results, and branch coordinators review and approve submissions. Completion is measured automatically and reported at every organizational level.

### 2.3 Business Value
- **Time saved:** College-wide initiative reports are produced in minutes instead of days of manual consolidation.
- **Error reduction:** Structured imports, validation, and duplicate detection replace free-form spreadsheets.
- **Accountability:** Every sensitive action is recorded in an immutable audit log.
- **Institutional evidence:** A historical record of initiatives per term is kept, suitable for quality assurance and accreditation evidence.
- **Reusability:** Any future initiative can be configured through dynamic fields without developer involvement.

### 2.4 Opportunity
The number of initiatives run by the college grows every term, and they span six gender-segregated branches (Zulfi, Majmaah, and Rumah; male and female campuses). A configurable, term-aware platform built now avoids compounding manual effort. It also establishes a data foundation that can later extend to other colleges within the university without a rebuild.

---

## 3. Goals & Objectives

### 3.1 Primary Goal
Deliver a working platform that is operational for the Second Semester of 2026/2027. It must allow three things:
1. The Super Admin can create an initiative with dynamic fields and assign it to sections.
2. Instructors can enter student initiative data and submit it for review.
3. Branch coordinators can review, return, or approve submissions, while completion indicators update automatically on dashboards.

### 3.2 Objectives
1. Manage 100% of the college's academic initiatives for the Second Semester 2026/2027 through TRACK.
2. Provide a six-step Import Wizard that imports section and student data at a first-attempt row success rate of at least 97%.
3. Calculate completion automatically at the student, section, branch, and initiative levels, with no manually entered percentages.
4. Give each role a landing dashboard showing only the data within its scope, with drill-down from branch to student record.
5. Keep complete historical records per academic term, with closed terms read-only by default.

### 3.3 Success Definition
v1 is successful when at least one full initiative cycle completes end to end in TRACK across all six branches:
- The Super Admin creates the initiative.
- The Super Admin imports the section and student data.
- Instructors enter the student data.
- Coordinators approve the submissions.
- The initiative report is exported.

In addition, the metrics in Section 12 must reach their targets within 30 days of launch.

### 3.4 Non-Goals
- TRACK is not a Student Information System and does not replace the university registration system.
- TRACK does not grade students or calculate course marks.
- TRACK does not deliver learning content or host training (it is not an LMS).
- TRACK does not provide self-registration for any user type.

---

## 4. Scope

### 4.1 In Scope (v1)
- Simple authentication using accounts created by the Super Admin (name, email, password). Users can change their password after first login. No two-factor authentication.
- RBAC with three roles (SUPER_ADMIN, BRANCH_COORDINATOR, INSTRUCTOR), with the permission model designed for additional roles.
- Academic structure management:
  - Academic Terms with lifecycle states.
  - Dynamic Branches.
  - Courses.
  - Sections with an internal unique ID.
  - Students and Section Enrollments.
- Import Wizard: Upload, Mapping, Validation, Preview, Import, and Report. It runs as a background queue and supports a downloadable error report.
- Section-level student import and manual student management by instructors.
- Initiative management with dynamic custom fields (15 field types) and schema versioning.
- Initiative targeting by branch, gender, course, section numbers, or an uploaded list, with a pre-launch impact summary and a target snapshot at launch.
- Instructor data entry workspace with draft saving and submission.
- Review workflow: Submitted → Approved, or Returned for Correction with a reason.
- Automatic completion calculation.
- Role-based dashboards with drill-down, initiative dashboards, and analysis by branch, gender, and term.
- Predefined reports with advanced filters, and export to Excel, CSV, PDF, and Print.
- Notification center and manual reminders.
- Global search, scoped by permissions.
- Audit log of all sensitive actions.
- Term closure and archiving (read-only).
- Platform settings: institution name, logo, and system messages.
- Full bilingual interface: Arabic RTL and English LTR.
- **Student technical support channel (added v1.1):** a public, unauthenticated form through which a
  student raises a technical support request about the platform. It verifies the University ID against
  the roster and the student's active enrollment in the current term, is rate-limited and
  CAPTCHA-protected, and issues a reference number. Students receive **no account, no password, and no
  session**, and can see nothing beyond the status of their own request, retrieved by reference number
  and University ID.
- **Support request queue (added v1.1):** the staff side of the channel. The Super Admin sees every
  request; a Branch Coordinator sees a request when the student holds an active enrollment in one of
  their branches. Requests are listed, filtered, opened, responded to, reassigned, and closed, with a
  written reason required to reject. Instructors have no access.

### 4.2 Out of Scope (deferred to v2)
- Native or cross-platform mobile application.
- Integration with university systems (registration, SSO, HR).
- Activation of multi-college or multi-university tenancy. The data model is designed for it, but it is not enabled.
- Drag-and-drop custom Report Builder.
- Heatmap visualizations.
- Two-factor authentication.
- Self-service "forgot password" by email. In v1, the Super Admin resets passwords.
- VIEWER, QUALITY_OFFICER, and INITIATIVE_MANAGER roles. The architecture supports them; they are not configured in v1.

### 4.3 Assumptions
- The Super Admin can obtain section, instructor, and student rosters as Excel or CSV exports each term.
- Each branch is defined by a campus and a gender (for example, "Zulfi — Female"). Section gender is derived from its branch.
- Student University IDs are unique across the university and follow a configurable pattern. The default is 9 digits.
- Instructors and coordinators have institutional email addresses, which serve as login identifiers.
- Estimated volume per term: up to 500 staff users, 3,000 sections, 30,000 students, and 50 initiatives.
- The university provides or approves a hosting environment located within the Kingdom of Saudi Arabia.
- Desktop use is the primary usage context.

### 4.4 Constraints
- The backend must be built in PHP using the Laravel framework, per the owner's decision.
- The database must be MySQL or MariaDB.
- The development timeline is 8 weeks from kickoff to public launch.
- Student personal data must be handled in accordance with the Saudi Personal Data Protection Law (PDPL) and university information security policy.
- Arabic must render correctly in all interfaces and in PDF exports.

### 4.5 Dependencies
- Availability of accurate roster exports from the registration unit.
- A provisioned production server (PHP 8.3+, MySQL/MariaDB, Redis, Supervisor) and a domain or subdomain with a TLS certificate.
- An SMTP relay for system emails, used by notifications and reminders.
- Branch coordinator nominations for all six branches before beta.
- Owner availability for acceptance reviews at the end of each phase.

---

## 5. Users & Personas

### 5.1 Primary Users
**Instructors.** Faculty members who execute initiatives inside their assigned sections. They enter student data and submit it for review. They use TRACK weekly during active initiative periods.

### 5.2 Secondary Users
- **Branch Coordinators.** One or more per branch. They monitor execution, follow up with instructors, and review, approve, or return submissions for their branch only.
- **Super Admin.** The central college administration. They configure terms, branches, initiatives, and users, run imports, and consume college-wide analytics.
- **College Leadership (indirect).** They consume dashboards and exported reports through the Super Admin in v1.

### 5.3 User Roles & Permissions

| Role | Access Level | Key Permissions |
|---|---|---|
| SUPER_ADMIN | Full — all branches, all terms | Manage users, roles, branches, terms, courses, sections, students, and initiatives; define dynamic fields; run central imports; target and launch initiatives; review any submission; view all dashboards and reports; export; view the audit log; close and archive terms; edit closed-term data (special permission); manage platform settings |
| BRANCH_COORDINATOR | Scoped to assigned branch(es) | View initiatives active in the branch; view branch sections, instructors, and students; view student initiative data (read-only); approve or return section submissions; send reminders to branch instructors; view branch dashboards; export branch reports |
| INSTRUCTOR | Scoped to assigned sections | View assigned initiatives and sections; import students to own sections; add, edit, or withdraw students in own sections; enter and edit initiative data where the field is editable by the instructor; save drafts; submit for review; view own dashboard; export own section data |

Permissions are stored as granular permission records (for example, `initiative.create`, `assignment.approve`). Roles are collections of permissions, so new roles can be added without code changes.

### 5.4 Personas

#### Persona 1 — Dr. Faisal (Instructor) — Primary
| Attribute | Detail |
|---|---|
| **Name** | Dr. Faisal |
| **Role** | Instructor, Majmaah — Male branch; teaches 4 sections (about 160 students) |
| **Goal** | Enter initiative data for all of his sections quickly and submit it once, without errors or repeated requests |
| **Pain Point** | Receives multiple Excel templates per term, re-types the same student lists, and does not know what is still missing or whether his submission was accepted |
| **Tech Level** | Medium — comfortable with Excel and web forms |
| **Frequency** | Weekly during active initiatives; daily near deadlines |
| **Success** | Completes a 40-student section in under 30 minutes and sees the "Approved" status without follow-up emails |

#### Persona 2 — Dr. Noura (Branch Coordinator) — Secondary
| Attribute | Detail |
|---|---|
| **Name** | Dr. Noura |
| **Role** | Branch Coordinator, Zulfi — Female |
| **Goal** | Know at any moment which sections in her branch have not started, are delayed, or are ready for review |
| **Pain Point** | Collects status by phone and email and has no single view of branch progress |
| **Tech Level** | Low to Medium |
| **Frequency** | 2–3 times per week; daily during review windows |
| **Success** | Identifies all delayed sections from one dashboard and reviews a submission in under 5 minutes |

#### Persona 3 — Ms. Maha (Super Admin) — Secondary
| Attribute | Detail |
|---|---|
| **Name** | Ms. Maha |
| **Role** | Super Admin, College Initiatives Office |
| **Goal** | Launch initiatives across the college and report accurate completion figures to leadership |
| **Pain Point** | Consolidating dozens of spreadsheets by hand, inconsistent section numbering across branches, and no historical record per term |
| **Tech Level** | Medium to High |
| **Frequency** | Daily |
| **Success** | Launches an initiative targeting 120 sections in under 20 minutes and exports a college-wide report in under 5 minutes |

---

## 6. MoSCoW Feature Prioritization

### Must Have — P0

| ID | Feature | Status | Description | Assigned To | Sprint |
|---|---|---|---|---|---|
| P0-F001 | Authentication & Account Management | TODO | Super Admin creates accounts (name, email, password, role); email and password login; users change their password after first login; Super Admin resets passwords and deactivates accounts; no 2FA | Backend Dev | S1 |
| P0-F002 | Role-Based Access Control & Data Scoping | TODO | Permission-based roles (SUPER_ADMIN, BRANCH_COORDINATOR, INSTRUCTOR) with branch and section data scoping enforced on every query | Backend Dev | S1 |
| P0-F003 | Academic Structure Management | TODO | CRUD for Academic Terms (Draft/Active/Closed/Archived, current-term flag), dynamic Branches, Courses, Sections with an internal unique ID, Students, and Section Enrollments | Backend Dev, Frontend Dev | S1 |
| P0-F004 | Import Wizard | TODO | Six-step queued import (Upload, Mapping, Validation, Preview, Import, Report) for central rosters, plus instructor section-level student import and an error report download | Backend Dev, Frontend Dev | S1 |
| P0-F005 | Initiative Management & Dynamic Fields | TODO | Create initiatives per term with lifecycle statuses and define dynamic fields (15 types, with required, default, options, order, visibility, editability, reporting, and completion flags), with schema versioning | Backend Dev, Frontend Dev | S2 |
| P0-F006 | Initiative Targeting & Assignment | TODO | Select target sections by branch, gender, course, section numbers, or an uploaded file; show a pre-launch impact summary; create one Assignment per Initiative + Section; snapshot targets at launch | Backend Dev | S2 |
| P0-F007 | Instructor Data Entry Workspace | TODO | Editable student grid per assignment with search, sort, filter, draft saving, Excel upload of values, manual add, and withdrawal of students | Frontend Dev, Backend Dev | S2 |
| P0-F008 | Submission & Review Workflow | TODO | Assignment flow: Not Started → In Progress → Submitted → Approved, or Returned for Correction with a mandatory reason | Backend Dev, Frontend Dev | S2 |
| P0-F009 | Automatic Completion Calculation | TODO | Student completion derived from fields flagged Required for Completion, aggregated to section, branch, and initiative levels | Backend Dev | S2 |
| P0-F010 | Audit Log | TODO | Immutable log of sensitive actions with user, action, entity, old and new values, timestamp, and IP address | Backend Dev | S1 |

### Should Have — P1

| ID | Feature | Status | Description | Assigned To | Sprint |
|---|---|---|---|---|---|
| P1-F001 | Role-Based Dashboards | TODO | Landing dashboards: College Overview (Super Admin), Branch Overview (Coordinator), My Work (Instructor), each with KPI cards and status lists | Frontend Dev, Backend Dev | S3 |
| P1-F002 | Initiative Dashboard & Drill-Down Analytics | TODO | Per-initiative dashboard with breakdown by branch, gender, course, instructor, and section; interactive drill-down from branch to student record; term selector | Frontend Dev, Backend Dev | S3 |
| P1-F003 | Reports & Advanced Filters | TODO | Predefined reports (initiative, branch, section, instructor, students, incomplete students, incomplete sections, branch comparison) using combinable filters | Backend Dev, Frontend Dev | S3 |
| P1-F004 | Export | TODO | Export any report or list to Excel, CSV, or PDF (Arabic-safe), or print it, always respecting the active filters and the user's scope | Backend Dev | S3 |
| P1-F005 | Notification Center & Reminders | TODO | In-app and email notifications for assignment, deadline, return, and approval events; manual reminders to an instructor, a branch, or incomplete sections | Backend Dev, Frontend Dev | S3 |
| P1-F006 | Global Search | TODO | Search by University ID, student name, section number, instructor, course, or initiative, filtered by user scope | Backend Dev, Frontend Dev | S3 |
| P1-F007 | Term Closure & Archiving | TODO | Close a term to make all its data read-only; only holders of a special permission can edit closed data; archived terms are hidden from default views | Backend Dev | S4 |
| P1-F008 | Platform Settings & Content Management | TODO | Manage institution name (AR/EN), logo, system messages, University ID pattern, and upload limits | Backend Dev, Frontend Dev | S4 |

### Could Have — P2

| ID | Feature | Status | Description | Assigned To | Sprint |
|---|---|---|---|---|---|
| P2-F001 | Actionable Insights Panel | TODO | Automatic alerts such as "12 sections have not started", "3 initiatives end within 7 days", or "47 students have missing required fields" | Backend Dev | S4 |
| P2-F002 | Bulk Edit | TODO | Apply one value to a field for multiple selected students in the instructor grid | Frontend Dev | S4 |
| P2-F003 | Cross-Term Comparison | TODO | Compare completion metrics of the same initiative type across previous terms | Frontend Dev | S4 |
| P2-F004 | Dark Mode | TODO | Optional dark theme for the full interface | Frontend Dev | S4 |

### Won't Have — P3

| ID | Feature | Status | Description | Assigned To | Sprint |
|---|---|---|---|---|---|
| P3-F001 | Mobile Application | SKIPPED | Native or cross-platform app; the API-friendly backend keeps this possible in v2 | — | v2 |
| P3-F002 | University Systems Integration | SKIPPED | Automatic sync with registration, SSO, or HR systems | — | v2 |
| P3-F003 | Multi-College Tenancy Activation | SKIPPED | University → College → Campus hierarchy activation; schema prepared in v1 | — | v2 |
| P3-F004 | Drag-and-Drop Report Builder | SKIPPED | Free-form custom report designer | — | v2 |
| P3-F005 | Heatmaps | SKIPPED | Heatmap visualizations for analytics | — | v2 |

---

## 7. Functional Requirements

> **Global UX states (apply to every feature below):**
> - **Validation:** Inline, on blur and on submit.
> - **Loading:** Skeleton or spinner on the triggering control, with the control disabled.
> - **Error:** Inline field error, or a toast with a retry action for system errors.
> - **Success:** A toast confirmation plus a visible state change.
>
> Feature-specific behavior is listed per block.

---

### P0-F001 — Authentication & Account Management

**Description:** Users cannot self-register. The Super Admin creates each Instructor or Coordinator account through the administration panel by entering full name (AR/EN), email, initial password, role, and branch or section scope. Users log in with email and password. After logging in, users can change their password at any time. On first login the system shows a non-blocking recommendation to change the initial password. Two-factor authentication is not included.

**User Story:** As Ms. Maha (Super Admin), I want to create accounts for instructors and coordinators with an initial password, so that they can access TRACK immediately without a registration process. As Dr. Faisal (Instructor), I want to change my initial password after my first login, so that only I know my credentials.

**Trigger:** The Super Admin selects "Create User", or a user opens the login page.

**Pre-conditions:**
- The Super Admin is authenticated.
- The email is not already used by an active or deactivated account.

**Post-conditions:**
- The account exists with a hashed password and an assigned role and scope.
- An audit log entry is written.
- On login, an authenticated session is created and the user lands on their role's dashboard.

**Main Flow (Account Creation):**
1. The Super Admin opens Users → Create User.
2. The Super Admin enters full name (AR), full name (EN), email, initial password, and role.
3. If the role is BRANCH_COORDINATOR, the Super Admin selects one or more branches.
4. If the role is INSTRUCTOR, the Super Admin optionally links the account to existing sections. Linking can also happen later through import.
5. The system validates the input: email format, email uniqueness, password of at least 8 characters with at least one letter and one digit.
6. The system hashes the password with Argon2id (bcrypt as fallback) and stores the account with `first_login_at = null`.
7. The system logs `user.created` and shows the confirmation "User created".

**Main Flow (Login and Password Change):**
1. The user enters email and password.
2. The system validates the credentials and checks that the account is active.
3. The system regenerates the session ID, sets `first_login_at` if it is empty, and redirects the user to their role dashboard.
4. On the first login, a banner recommends changing the password and links to Profile → Change Password.
5. The user enters the current password, the new password, and a confirmation.
6. The system validates the input: the current password must be correct, the new password must meet the policy, and it must differ from the current password.
7. The system updates the hash, invalidates the user's other sessions, logs `user.password_changed`, and shows "Password updated".

**Alternate Flows:**
- **A1 — Invalid credentials:** The system shows the generic message "Invalid email or password" without revealing which field is wrong, and increments the attempt counter.
- **A2 — Rate limit exceeded:** After 5 failed attempts per minute for the same email and IP, login is blocked for 60 seconds and a countdown is shown.
- **A3 — Deactivated account:** The login is rejected with "Your account is inactive. Contact the administrator."
- **A4 — Super Admin resets a password:** The Super Admin enters a new password, the system invalidates all of the user's sessions, and the event is logged as `user.password_reset`.
- **A5 — Deactivation:** The Super Admin deactivates a user (soft delete). The user's historical records, entries, and approvals remain attributed to them.

**Acceptance Criteria:**
- [ ] No public registration route exists; `/register` returns 404.
- [ ] Only SUPER_ADMIN can create, edit, deactivate, or reset users.
- [ ] Passwords are stored only as Argon2id or bcrypt hashes; plaintext never appears in the database or logs.
- [ ] A user with correct credentials lands on their role dashboard within 2 seconds.
- [ ] The first-login banner appears only when `first_login_at` was empty before the current login.
- [ ] A password change requires the correct current password and rejects a new password identical to the current one.
- [ ] Changing or resetting a password terminates all other active sessions of that user.
- [ ] 5 failed logins within one minute block further attempts for 60 seconds.
- [ ] A deactivated user cannot log in, and their historical entries still display their name.
- [ ] Sessions expire after 60 minutes of inactivity.

---

### P0-F002 — Role-Based Access Control & Data Scoping

**Description:** Permissions are granular (for example, `section.view`, `assignment.approve`, `term.edit_closed`) and grouped into roles. Every data query is filtered by scope:
- A BRANCH_COORDINATOR sees only their assigned branch(es).
- An INSTRUCTOR sees only sections where they are the current instructor.
- The Super Admin can grant an instructor additional section access explicitly.

**User Story:** As Dr. Noura (Coordinator), I want to see only my branch's data, so that I can focus on my responsibilities and other branches' data stays protected.

**Trigger:** Any authenticated request.

**Pre-conditions:**
- The user is authenticated and has a role.

**Post-conditions:**
- The response contains only records within the user's scope.
- Unauthorized access attempts are rejected and logged.

**Main Flow:**
1. A request reaches a controller.
2. The authorization policy checks the required permission.
3. The query applies a global scope filter: branch IDs for Coordinators, section IDs for Instructors, or no filter for the Super Admin.
4. The data is returned.

**Alternate Flows:**
- **A1 — Missing permission:** The request returns 403, the page shows "You do not have access to this page", and an `access.denied` audit entry is written.
- **A2 — Out-of-scope entity by direct URL** (for example, `/sections/9981` belonging to another branch): The request returns 404, so the entity's existence is not revealed, and the attempt is logged.
- **A3 — Special section access granted:** The Super Admin grants Instructor X access to Section Y. The instructor then sees Section Y with a "Shared access" label.
- **A4 — Role change:** When the Super Admin changes a user's role or scope, the change takes effect on the user's next request and is audit-logged with old and new values.

**Acceptance Criteria:**
- [ ] A Coordinator cannot retrieve any section, student, or record from another branch through the UI, API, search, export, or direct URL.
- [ ] An Instructor cannot retrieve sections not assigned to them unless explicitly granted.
- [ ] Every API endpoint has an authorization test covering allowed and denied roles.
- [ ] New roles can be created by seeding permissions, with no code change to existing policies.
- [ ] Scope filtering applies to dashboards, reports, exports, and global search.

---

### P0-F003 — Academic Structure Management

**Description:** The Super Admin manages the core academic entities:
- **Academic Terms:** Name (for example, "2026/2027 — First Semester"), start and end dates, status (Draft, Active, Closed, Archived), and a single "current term" flag.
- **Branches:** Name AR/EN, campus, gender, and active flag. All branches are dynamic and none are hard-coded.
- **Courses:** Code, name AR/EN.
- **Sections:** Internal unique ID; section number; course; branch; term; current instructor.
- **Students:** University ID as the unique academic identifier; name AR/EN.
- **Section Enrollments:** Student ↔ Section, with status (Active or Withdrawn) and dates.

**User Story:** As Ms. Maha (Super Admin), I want to define terms, branches, and sections with a unique internal identity, so that Section 123 in Majmaah Male and Section 123 in Zulfi Female never conflict.

**Trigger:** The Super Admin opens the Academic Structure module.

**Pre-conditions:**
- The Super Admin is authenticated.
- At least one term exists before sections can be created.

**Post-conditions:**
- The entities are stored with enforced uniqueness constraints.
- Changes are audit-logged.

**Main Flow (Create Section):**
1. The Super Admin opens Sections → Create.
2. The Super Admin selects the term, branch, and course, and enters the section number.
3. The Super Admin optionally selects an instructor.
4. The system validates uniqueness of (term, branch, course, section number).
5. The system generates the internal section ID, stores the section, and logs `section.created`.

**Main Flow (Change Section Instructor):**
1. The Super Admin opens the section and selects "Change Instructor".
2. The Super Admin selects the new instructor.
3. The system updates `sections.instructor_id` and records an instructor-history row.
4. Access moves to the new instructor, and existing data entries keep their original author.
5. The system logs `section.instructor_changed` with the old and new instructor.

**Alternate Flows:**
- **A1 — Duplicate section key:** The save is blocked with "A section with this number already exists for this course, branch, and term."
- **A2 — Setting the current term:** Setting a term as current removes the flag from the previous current term.
- **A3 — Deleting a branch or term with data:** Hard delete is blocked. Only deactivation or archiving is allowed.
- **A4 — Withdrawing a student:** The enrollment status becomes Withdrawn with a date and reason. The student record is never deleted.

**Acceptance Criteria:**
- [ ] Two sections with the same number can coexist when term, branch, or course differs.
- [ ] Creating a duplicate (term, branch, course, section number) is rejected at both application and database level through a unique index.
- [ ] A student with a given University ID exists only once in the system, regardless of the number of enrollments.
- [ ] A student can be enrolled in multiple sections within the same term.
- [ ] Only one term can be flagged as current at any time.
- [ ] Branches can be added, renamed, or deactivated without code changes.
- [ ] Changing a section's instructor preserves the authorship of all previous entries.
- [ ] Branch, term, user, and student deletion is soft delete only.

---

### P0-F004 — Import Wizard

**Description:** A six-step wizard imports roster data from Excel (.xlsx) or CSV files. Files are validated and processed by background queue jobs, never inside the HTTP request.

There are two modes:
- **Central Import (Super Admin):** Columns are Term, Branch, Gender, Course Code, Course Name, Section Number, Instructor Name, Instructor Email, Student University ID, and Student Name. The system creates or links Branch → Section → Instructor → Student → Enrollment.
- **Section Import (Instructor):** Columns are University ID and Student Name. Students are linked only to the selected section, which must be assigned to the instructor.

**User Story:** As Ms. Maha (Super Admin), I want to upload one roster file and review errors before anything is saved, so that bad data never pollutes the system.

**Trigger:** The user selects "Import" from Sections (central) or from a section page (instructor).

**Pre-conditions:**
- For central import, the target term exists.
- For section import, the section is assigned to the instructor.
- The file is .xlsx or .csv, at most 20 MB and at most 50,000 rows.

**Post-conditions:**
- Valid rows are imported.
- An Import Job record stores counts and status.
- Import Errors are stored per row and downloadable.
- The user is notified when processing finishes.

**Main Flow:**
1. **Upload:** The user uploads the file. The system checks the extension, MIME type, and size, stores the file privately, and creates an Import Job with status `uploaded`.
2. **Column Mapping:** The system reads the header row and suggests a mapping (for example, Student_ID → University ID). The user confirms or adjusts it. Required target fields must be mapped.
3. **Validation (queued):** The system detects:
   - Duplicate rows within the file.
   - University IDs that do not match the configured pattern.
   - Missing required values.
   - Unknown branches.
   - Unknown courses. These are flagged, and the user chooses whether to auto-create them.
   - Instructors not registered in the system. These are flagged, and the user chooses whether to create the account, which then needs a password reset before first use, or to skip.
   - Existing student IDs whose name differs.
4. **Preview:** The system shows a summary (new sections, new students, new enrollments, existing matches, warnings, and errors) and the first 100 rows with row-level status.
5. **Import (queued):** The user confirms. The job processes valid rows in chunks of 500 inside transactions, and existing records are linked rather than duplicated.
6. **Import Report:** The system shows processed, imported, duplicate, and error counts. For example: "950 processed, 930 imported, 12 duplicates, 8 errors". It offers an error report download (.xlsx with row number, column, value, and reason) and sends an in-app notification.

**Alternate Flows:**
- **A1 — Same file imported twice:** The system detects a matching file hash for the same term and warns the user. If the user continues, existing records are matched and not duplicated, so the report shows 0 new records.
- **A2 — Instructor imports a student who already exists globally:** The existing student is linked to the section. If the name differs, the existing name is kept and a warning is shown.
- **A3 — Row with a section that cannot be resolved:** The row is rejected with the reason "Section could not be resolved".
- **A4 — Queue failure mid-import:** The job status becomes `failed`, rows already committed in completed chunks are kept, and the user can resume from the failed chunk.
- **A5 — Import after initiative launch:** Newly enrolled students in targeted sections are added to the assignment targets and marked `added_after_launch`.
- **A6 — Instructor attempts to import into an unassigned section:** The request is rejected with 403.

**Acceptance Criteria:**
- [ ] No data is written to core tables before the user confirms at the Preview step.
- [ ] Every validation rule listed in step 3 produces a row-level error or warning with a human-readable reason in the user's interface language.
- [ ] A 10,000-row central import completes validation and import in under 5 minutes, with the UI remaining responsive during processing.
- [ ] Re-importing an identical file creates zero duplicate students, sections, or enrollments.
- [ ] The error report downloads as .xlsx and contains row number, column, submitted value, and reason for every rejected row.
- [ ] Instructor imports can only target sections assigned to that instructor.
- [ ] Every import is audit-logged with file name, row counts, and user.
- [ ] Files larger than 20 MB or with unsupported types are rejected at upload with a clear message.

---

### P0-F005 — Initiative Management & Dynamic Fields

**Description:** The Super Admin creates any number of initiatives within a term.

Each initiative has:
- Name (AR/EN) and short name.
- Description, objective, and execution instructions.
- Start and end dates.
- Status: Draft, Ready, Active, Paused, Completed, or Archived.
- An ordered set of dynamic fields.

The fixed student columns (serial number, University ID, student name) are always present.

Supported field types: Text, Long Text, Integer, Decimal, Percentage, Date, Time, Yes/No, Checkbox, Dropdown, Multiple Choice, URL, Email, File Upload, and Image Upload.

Each field defines:
- Label (AR/EN), type, and whether it is required.
- Default value, options (for choice types), and display order.
- Visible to Instructor, Editable by Instructor, Show in Reports, and Required for Completion flags.

Field definitions belong to a **schema version**. Editing fields after launch creates a new version without losing existing values.

**User Story:** As Ms. Maha (Super Admin), I want to define different data fields for each initiative (for example, Certificate Name, Certificate Status, and Verification URL), so that every initiative collects exactly the data it needs without developer work.

**Trigger:** The Super Admin selects "Create Initiative", or opens the Fields tab of an initiative.

**Pre-conditions:**
- The target term exists and is not Closed or Archived.

**Post-conditions:**
- The initiative is stored with schema version V1.
- Every later field change after launch produces version V(n+1).
- All changes are audit-logged.

**Main Flow:**
1. The Super Admin opens Initiatives → Create.
2. The Super Admin enters the basic data: name AR/EN, short name, description, objective, term, start and end dates, and instructions.
3. The Super Admin adds fields: selects a type, enters labels, and sets the flags and options.
4. The Super Admin reorders fields by drag handle or up/down controls.
5. A live preview shows the instructor grid with the fixed columns followed by the dynamic fields.
6. The Super Admin saves. The initiative status is Draft with schema V1.
7. The Super Admin sets the status to Ready when the fields are final, which enables targeting and launch.

**Alternate Flows:**
- **A1 — Adding a field after launch:** A new schema version is created. Existing records keep their values, and the new field is empty for all students. If the field is Required for Completion, completion is recalculated and a warning shows the projected impact (for example, "Completion will drop from 75% to 0% for 1,500 students") before the user confirms.
- **A2 — Removing a field that has data:** The field is marked inactive in the new version and hidden from the grid, but its values are retained and remain visible in the version history.
- **A3 — Changing the type of a field that has data:** This is blocked with "Field type cannot be changed once data exists. Create a new field instead."
- **A4 — Dropdown with no options:** The save is blocked with the inline error "At least one option is required."
- **A5 — Pausing an initiative:** Instructors see the initiative as read-only with the label "Paused". Resuming restores edit access.
- **A6 — Reopening a completed initiative:** Only the Super Admin can reopen it. The status returns to Active and the action is audit-logged.

**Acceptance Criteria:**
- [ ] All 15 field types render the correct input control and apply type-specific validation in the instructor grid.
- [ ] Fields with "Visible to Instructor = No" never appear in instructor views or instructor exports.
- [ ] Fields with "Editable by Instructor = No" are read-only for instructors.
- [ ] Adding, removing, or editing a field on an Active initiative creates a new schema version, and no existing value is lost.
- [ ] Field type changes are blocked when any value exists for that field.
- [ ] An unlimited number of initiatives can exist per term; creation performance is unaffected with 50 or more initiatives in a term.
- [ ] Initiative data is never stored in the students table.

---

### P0-F006 — Initiative Targeting & Assignment

**Description:** The Super Admin selects the sections that an initiative applies to. Targets can be selected by all sections, specific branches, gender, one or more courses, specific section numbers, or an uploaded Excel list of sections.

Before launch, the system shows the impact: number of branches, sections, instructors, and students. On launch, the system:
- Creates one **Assignment** per Initiative + Section, with status Not Started.
- Takes a **target snapshot** of all actively enrolled students.

**User Story:** As Ms. Maha (Super Admin), I want to target all Female sections of specific courses and see exactly how many students will be included before launching, so that I launch with the correct scope.

**Trigger:** The Super Admin opens the Targeting tab of an initiative with status Ready.

**Pre-conditions:**
- The initiative status is Ready.
- The term is Active.
- At least one matching section exists.

**Post-conditions:**
- Assignments are created.
- Snapshot rows record the original targets.
- The initiative status becomes Active.
- Assigned instructors are notified.

**Main Flow:**
1. The Super Admin combines selectors (for example, Branch in [Zulfi-F, Majmaah-F] and Course in [ENG101]).
2. The system shows a live preview: for example, "2 branches · 38 sections · 21 instructors · 1,412 students".
3. The Super Admin reviews the section list and deselects individual sections if needed.
4. The Super Admin selects "Launch Initiative" and confirms in a modal that restates the counts.
5. The system creates the assignments and snapshot rows in a queued job and updates the initiative status to Active.
6. The system notifies each assigned instructor: "A new initiative has been assigned to you."

**Alternate Flows:**
- **A1 — Section without an instructor:** The section is listed with a warning. Launch is allowed, but the assignment is flagged "No instructor" on the dashboards.
- **A2 — Section list upload with unknown sections:** Unmatched rows are listed, and matched sections are added to the selection.
- **A3 — Student enrolled after launch:** A target is created with origin `added_after_launch` and the date.
- **A4 — Student withdrawn after launch:** The target is marked `removed_after_launch` with the date and reason. The target is excluded from the completion denominator but kept in "Original Target" reporting.
- **A5 — Adding sections to an Active initiative:** New assignments are created with their own snapshot, and the initiative's original target count is preserved separately.

**Acceptance Criteria:**
- [ ] The preview counts match the actual assignments and targets created, with 0 discrepancy.
- [ ] Each Initiative + Section pair has at most one assignment, enforced by a unique index.
- [ ] Original target count remains unchanged when enrollments change after launch.
- [ ] Added-after-launch and removed-after-launch targets are visible in the initiative's Students tab with dates.
- [ ] Launch notifications reach every assigned instructor within 5 minutes.
- [ ] A launch targeting 3,000 sections and 30,000 students completes in under 3 minutes.

---

### P0-F007 — Instructor Data Entry Workspace

**Description:** When an instructor opens an assignment, they see a grid with the columns #, University ID, Student Name, the visible dynamic fields, and Status.

In the grid, the instructor can:
- Edit cells and save as a draft.
- Search, sort, and filter.
- Upload an Excel file of values, matched by University ID.
- Add a student manually, or withdraw a student with a reason.
- Upload files or images for file-type fields.

**User Story:** As Dr. Faisal (Instructor), I want to fill initiative data for my 40 students in one grid and save as I go, so that I can finish without losing work.

**Trigger:** The instructor opens My Work → Initiative → Section.

**Pre-conditions:**
- The assignment exists for a section assigned to the instructor.
- The initiative is Active.
- The assignment status is Not Started, In Progress, or Returned for Correction.

**Post-conditions:**
- Values are stored per student per field, linked to the current schema version.
- Student completion is recalculated.
- The assignment status becomes In Progress when the first value is saved.

**Main Flow:**
1. The instructor opens the assignment. The grid loads with the initiative instructions shown in a collapsible panel.
2. The instructor edits cells. Type validation runs immediately, and invalid cells are highlighted with the reason shown on hover or focus.
3. The instructor selects "Save Draft". Changed rows only are sent in one batch request.
4. The system validates each value server-side, stores valid values, and returns per-row results.
5. The grid updates the row status (Complete or Incomplete) and the header progress bar (for example, "28 / 40 complete — 70%").
6. The system auto-saves every 60 seconds when there are unsaved changes, and the grid displays the last saved time.

**Alternate Flows:**
- **A1 — Excel value upload:** The system provides a template download pre-filled with the students and field columns. Uploaded rows are matched by University ID and pass through validation and preview before they are applied. Unknown IDs are rejected.
- **A2 — Manual add student:** The instructor enters the University ID and name. An existing global student is linked, otherwise a new student is created. An enrollment and a target (`added_after_launch`) are created, and the action is audit-logged.
- **A3 — Withdraw student:** The instructor selects Withdraw and enters a mandatory reason. The student is excluded from the denominator, the row is shown greyed, and the action is audit-logged.
- **A4 — Leaving with unsaved changes:** A browser confirmation dialog warns before navigation.
- **A5 — Assignment Submitted or Approved:** The grid is read-only, with a status banner.
- **A6 — Concurrent edit** (the Super Admin edits the same record): The last save wins at field level, and the overwritten previous value is recorded in the audit log.

**Acceptance Criteria:**
- [ ] The grid loads 200 students with 15 fields in under 2 seconds.
- [ ] Only fields flagged "Visible to Instructor" are displayed, and only "Editable by Instructor" fields accept input.
- [ ] Invalid values (for example, text in an Integer field or a malformed URL) are rejected client-side and server-side with a specific message.
- [ ] Save Draft persists only changed rows and reports per-row success or failure.
- [ ] Auto-save runs every 60 seconds when changes exist and shows "Last saved at HH:MM".
- [ ] Uploaded files are validated by type and size (default 10 MB) and are only downloadable by authorized users through an authenticated route.
- [ ] Search, sort, and filter work on all visible columns without a page reload.
- [ ] Every add, edit, and withdraw action on student data writes an audit entry with old and new values.

---

### P0-F008 — Submission & Review Workflow

**Description:** Each assignment follows this workflow:

Not Started → In Progress → Submitted → Approved
                                      ↘ Returned for Correction → In Progress → Submitted …

Coordinators review submissions for sections in their branch. The Super Admin can review any submission. Returning an assignment requires a written reason.

**User Story:** As Dr. Noura (Coordinator), I want to return a submission with a clear reason (for example, "5 students have no data"), so that the instructor knows exactly what to fix.

**Trigger:** The instructor selects "Submit for Review", or the coordinator opens the Review queue.

**Pre-conditions:**
- To submit: the assignment is In Progress or Returned for Correction.
- To review: the assignment is Submitted and within the reviewer's scope.

**Post-conditions:**
- The status is updated with a timestamp and the actor.
- A notification is sent to the other party.
- An audit entry is written.

**Main Flow:**
1. The instructor selects "Submit for Review".
2. The system shows a summary: completed and incomplete student counts, with a list of incomplete students.
3. If any active student is incomplete, the instructor must confirm "Submit with incomplete records".
4. The status becomes Submitted and the grid is locked for the instructor.
5. The branch coordinator receives the notification "Section 214 submitted for review".
6. The coordinator opens the assignment in review mode, which is read-only and highlights incomplete rows and required-field gaps.
7. The coordinator selects Approve. The status becomes Approved and the instructor is notified: "Section 214 approved".

**Alternate Flows:**
- **A1 — Return for correction:** The coordinator selects "Return" and must enter a reason of at least 10 characters. The status becomes Returned for Correction, the grid unlocks for the instructor, and the reason is displayed in a banner on the grid.
- **A2 — Resubmission:** Resubmission follows the same flow. The review history lists all submissions, returns, and reasons in chronological order.
- **A3 — Initiative end date passed without submission:** The assignment is flagged Delayed on the dashboards. Submission remains possible until the initiative is set to Completed.
- **A4 — Super Admin reopens an Approved assignment:** The status returns to In Progress with a mandatory reason, and the action is audit-logged.

**Acceptance Criteria:**
- [ ] Only the assigned instructor, or an instructor with explicitly granted access, can submit an assignment.
- [ ] Only a coordinator of the section's branch, or the Super Admin, can approve or return it.
- [ ] Return without a reason (fewer than 10 characters) is blocked.
- [ ] Submitted and Approved assignments are read-only for instructors.
- [ ] Each status transition is stored in an assignment history with actor, timestamp, and reason.
- [ ] Invalid transitions (for example, Not Started → Approved) are rejected by the API.
- [ ] Status change notifications are delivered in-app immediately and by email within 5 minutes.

---

### P0-F009 — Automatic Completion Calculation

**Description:** Completion is calculated from data and is never entered manually.

- A **student** is Complete for an assignment when every active field flagged Required for Completion has a valid value.
  - For Yes/No and Checkbox fields, the value must be Yes or checked.
  - For all other types, the value must be non-empty and valid.
- **Section completion** = complete active targets ÷ active targets.
- **Branch completion** and **Initiative completion** are student-weighted: total complete students ÷ total active targets. They are not an average of section percentages.

**User Story:** As Ms. Maha (Super Admin), I want completion percentages to be calculated from actual data, so that reported figures are trustworthy.

**Trigger:**
- A value is saved or imported.
- A target is added or withdrawn.
- The initiative schema changes.

**Pre-conditions:**
- The initiative has at least one field flagged Required for Completion. Otherwise, completion shows "N/A — no completion criteria defined".

**Post-conditions:**
- `student_initiative_records.is_complete` and `completed_at` are updated.
- The aggregates in the summary tables are updated.

**Main Flow:**
1. A value save commits.
2. The system recalculates `is_complete` for the affected records within the same transaction.
3. The system dispatches a queued job that refreshes the aggregates for the section, branch, and initiative.
4. The dashboards read from the aggregate tables.

**Alternate Flows:**
- **A1 — Schema change adds a required field:** A queued job recalculates all records of the initiative, and the dashboards show "Recalculating…" until it finishes.
- **A2 — Withdrawn student:** The student is removed from the numerator and the denominator. The original target figure is still reported separately.
- **A3 — Section with 0 active targets:** Completion shows "—" and the section is excluded from aggregates.

**Acceptance Criteria:**
- [ ] For a section with 200 active targets and 150 complete, the section displays "150 / 200 — 75%".
- [ ] Branch and initiative completion equal total complete ÷ total active targets. A unit test verifies that they differ from the mean of section percentages when section sizes differ.
- [ ] No UI or API allows manual entry of a completion percentage.
- [ ] Aggregates reflect a saved change within 60 seconds.
- [ ] A full recalculation for 30,000 records completes in under 2 minutes.

---

### P0-F010 — Audit Log

**Description:** The system keeps an append-only log of sensitive actions:
- Initiative create, edit, archive, and status changes.
- Field and schema changes.
- Student add, edit, and withdraw.
- Value edits.
- Assignment status changes.
- Imports.
- User create, edit, deactivate, role or scope changes, and password resets.
- Instructor changes on sections.
- Term status changes.
- Denied access attempts.

Each entry stores user, action, entity type, entity ID, old value (JSON), new value (JSON), timestamp, IP address, and user agent.

**User Story:** As Ms. Maha (Super Admin), I want to see who removed Student 441234567 from Section 120 and when, so that every change is accountable.

**Trigger:** Any audited action commits.

**Pre-conditions:** None.

**Post-conditions:**
- An immutable entry exists.
- No UI or API supports updating or deleting entries.

**Main Flow:**
1. An audited action commits in a transaction.
2. The system writes the audit entry in the same transaction.
3. The Super Admin views Audit Log with filters (user, action, entity, date range, branch) and exports the results.

**Alternate Flows:**
- **A1 — Entity-level activity:** Initiative, section, and student pages each show an Activity Log tab filtered to that entity, within the viewer's scope.
- **A2 — Audit write failure:** The whole transaction rolls back, so the action does not complete, and the user sees "Action could not be completed. Please retry."

**Acceptance Criteria:**
- [ ] Every action listed above produces exactly one audit entry containing all required attributes.
- [ ] Audit entries cannot be modified or deleted through any UI or API route, and the database user for the application lacks UPDATE/DELETE on the audit table.
- [ ] Old and new values are stored for every edit of a student initiative value.
- [ ] The audit log view filters 1,000,000 entries by user and date range in under 2 seconds.
- [ ] Audit entries are retained for at least 5 years.

---

### P1-F001 — Role-Based Dashboards

**Description:** Each role lands on a dashboard after login.

- **College Overview (Super Admin):**
  - KPI cards: Active Initiatives, Branches, Sections, Faculty, Students, Completed Students, Completion Rate, and Pending Sections.
  - Panels: Initiatives Progress, Branch Performance, Male vs Female, Section Status, Recent Activity, and Alerts.
- **Branch Overview (Coordinator):** Initiatives, Sections, Instructors, Students, Completion %, and lists of Completed, Delayed, Not Started, and Awaiting Review sections.
- **My Work (Instructor):** My Initiatives, My Sections, Students, Completed, Pending, and a table of Initiative → Section → Progress → Action.

All dashboards default to the current term and include a term selector.

**User Story:** As Dr. Noura (Coordinator), I want my landing page to list delayed and not-started sections, so that I know whom to follow up with today.

**Trigger:** Login, or selecting "Dashboard".

**Pre-conditions:**
- A current term is set.

**Post-conditions:** None (read-only).

**Main Flow:**
1. The user logs in and the role dashboard loads with KPI cards first.
2. The charts load asynchronously.
3. The user clicks any KPI card or list item to open the filtered detail view.

**Alternate Flows:**
- **A1 — No active initiatives:** An empty state shows "No active initiatives in this term". The Super Admin also sees a "Create Initiative" button.
- **A2 — Term switch:** All widgets reload for the selected term.
- **A3 — Aggregates recalculating:** Affected widgets show a "Recalculating…" badge.

**Acceptance Criteria:**
- [ ] Each role sees only its own dashboard and only in-scope data.
- [ ] KPI cards render in under 2 seconds, and all charts render in under 3 seconds for a term with 30,000 students.
- [ ] Every KPI card and chart segment is clickable and opens a correctly filtered list.
- [ ] A section is labeled Delayed when either condition holds:
  - The initiative end date has passed and the assignment is not Submitted or Approved.
  - The end date is within 7 days and completion is below 50%.
- [ ] The dashboard renders correctly in RTL and LTR at 1280 px and 1920 px widths.

---

### P1-F002 — Initiative Dashboard & Drill-Down Analytics

**Description:** Each initiative has a dashboard showing:
- Target Students (original and current), Participating Students, Completed Students, and Completion %.
- Number of Sections, Completed Sections, and Pending Sections.
- Distribution by Branch, Gender, Course, Instructor, and Section.

The drill-down path is Branch → Sections → Instructor → Students → Student Initiative Data. The Super Admin can also analyze by Academic Year → Semester.

**User Story:** As Ms. Maha (Super Admin), I want to click "Zulfi Female" on a chart and reach individual student records, so that I can investigate low completion directly.

**Trigger:** The user opens Initiative → Overview, or clicks an initiative on a dashboard.

**Pre-conditions:**
- The initiative has at least one assignment.

**Post-conditions:** None (read-only).

**Main Flow:**
1. The dashboard loads KPIs and the charts: bar chart by branch, donut chart by gender, and progress bars by course.
2. The user clicks a branch bar and the view filters to that branch's sections.
3. The user clicks a section and the section detail opens with instructor and student progress.
4. The user clicks a student and the student's initiative record opens (read-only for the Super Admin and Coordinators).
5. Breadcrumbs show the drill path, and each breadcrumb is clickable to go back up.

**Alternate Flows:**
- **A1 — Coordinator opens an initiative dashboard:** The data is restricted to their branch, and branch comparison is hidden.
- **A2 — Filter combination returns no data:** An empty state shows "No data matches these filters" with a Clear filters action.

**Acceptance Criteria:**
- [ ] Drill-down reaches the student record in at most 4 clicks from the initiative dashboard.
- [ ] Drill-down filters persist in the URL, so the view can be bookmarked and shared within the user's scope.
- [ ] Gender, branch, and course breakdowns sum exactly to the initiative totals.
- [ ] Original Target and Current Target are displayed separately when they differ.

---

### P1-F003 — Reports & Advanced Filters

**Description:** Predefined reports:
- Initiative Report, Branch Report, Section Report, Instructor Report, and Student Report.
- Incomplete Students, Incomplete Sections, and Branch Comparison.

Filters available on reports and most list screens: Academic Term, Initiative, Branch, Gender, Course, Instructor, Section, Assignment Status, and Completion Status. Filters are combinable.

**User Story:** As Ms. Maha (Super Admin), I want a report of incomplete female students in Zulfi for the Professional Certificates initiative this term, so that I can target follow-up.

**Trigger:** The user opens Reports → selects a report type.

**Pre-conditions:**
- The user has the report view permission.

**Post-conditions:** None (read-only).

**Main Flow:**
1. The user selects the report type and applies filters.
2. The system displays a paginated table (50 rows per page) with totals.
3. The user sorts columns and exports the report (P1-F004).

**Alternate Flows:**
- **A1 — Report includes dynamic fields:** Only fields flagged "Show in Reports" are included as columns.
- **A2 — Result set over 10,000 rows:** The on-screen view paginates, and the export runs as a queued job with a notification when the file is ready.

**Acceptance Criteria:**
- [ ] All 8 report types are available, and each respects the user's scope.
- [ ] Any combination of the 9 filters returns correct results.
- [ ] Report totals match the dashboard figures for the same filters.
- [ ] A report over 30,000 students returns its first page in under 3 seconds.

---

### P1-F004 — Export

**Description:** Reports, lists, and assignment grids can be exported to Excel (.xlsx), CSV (UTF-8 with BOM for Arabic compatibility), and PDF with correct Arabic shaping and RTL layout, or printed. Exports always respect the active filters and the user's scope and field visibility.

**User Story:** As Dr. Noura (Coordinator), I want to export my branch's incomplete sections to PDF, so that I can share them in a meeting.

**Trigger:** The user selects Export → format.

**Pre-conditions:**
- The user has the export permission for the dataset.

**Post-conditions:**
- A file is generated and downloaded, or delivered through a notification for large exports.
- An audit entry `export.generated` is written.

**Main Flow:**
1. The user selects the format.
2. For fewer than 10,000 rows, the file is generated synchronously and downloads immediately.
3. The file header includes the institution name, logo, report title, applied filters, generation date and time, and the user's name.

**Alternate Flows:**
- **A1 — Large export:** A queued job generates the file, and the user receives the notification "Your export is ready" with a download link valid for 24 hours.
- **A2 — Print:** A print-optimized view opens with the browser print dialog.

**Acceptance Criteria:**
- [ ] Arabic text displays correctly (connected letters, RTL order) in Excel, CSV opened in Excel, and PDF.
- [ ] Exported rows match the on-screen filtered result count exactly.
- [ ] Instructor exports exclude fields not visible to instructors.
- [ ] Export download links require authentication and expire after 24 hours.

---

### P1-F005 — Notification Center & Reminders

**Description:** An in-app notification center (bell icon with unread count) and email notifications cover these events:
- New initiative assigned.
- 3 days left before the initiative end date.
- Section has not started 7 days after launch (sent to the coordinator).
- Section returned for correction.
- Section approved.
- Import finished.
- Export ready.

The Super Admin and Coordinators can send manual reminders to a specific instructor, to all instructors of a branch, or to instructors of incomplete sections.

**User Story:** As Dr. Noura (Coordinator), I want to remind all instructors with incomplete sections in one action, so that I do not email each one separately.

**Trigger:** A system event occurs, or the user selects "Send Reminder".

**Pre-conditions:**
- For manual reminders, the recipients are within the sender's scope.

**Post-conditions:**
- Notifications are stored and marked unread.
- Emails are queued.

**Main Flow (Manual Reminder):**
1. The coordinator opens the branch dashboard and selects "Remind incomplete sections".
2. The system shows the recipient count and an editable message pre-filled with the initiative name and deadline.
3. The coordinator confirms.
4. The system creates in-app notifications and queues emails, then logs `reminder.sent` with the recipient count.

**Alternate Flows:**
- **A1 — Email delivery failure:** The system retries 3 times with backoff. The in-app notification is still delivered, and the failure is logged.
- **A2 — Duplicate reminder:** The same sender sending the same reminder to the same recipient within 24 hours triggers the warning "A reminder was already sent today" and requires confirmation.

**Acceptance Criteria:**
- [ ] All seven system events generate notifications to the correct recipients only.
- [ ] Deadline notifications are sent once, 3 days before the end date, by a scheduled job.
- [ ] Unread counts update without a page reload within 60 seconds.
- [ ] Coordinators cannot send reminders to instructors outside their branch.
- [ ] Notification text is shown in the recipient's interface language.

---

### P1-F006 — Global Search

**Description:** A single search bar in the header searches University ID (exact and prefix), student name (partial, Arabic and English), section number, instructor name, course code or name, and initiative name. Results are grouped by entity type and filtered by the user's scope.

**User Story:** As Ms. Maha (Super Admin), I want to type a University ID and immediately see the student's enrollments and initiative history.

**Trigger:** The user types at least 2 characters in the search bar.

**Pre-conditions:**
- The user is authenticated.

**Post-conditions:** None.

**Main Flow:**
1. The user types a query. Results are debounced by 300 ms.
2. The system returns up to 5 results per entity type.
3. The user selects a result and the entity page opens.

**Alternate Flows:**
- **A1 — No results:** The dropdown shows "No results for '…' within your access".
- **A2 — Arabic normalization:** Queries match regardless of Alef variants (أ/إ/آ/ا), Taa Marbuta and Haa (ة/ه), and Yaa and Alef Maqsura (ي/ى).

**Acceptance Criteria:**
- [ ] Search returns results in under 500 ms for 30,000 students.
- [ ] Out-of-scope entities never appear in results.
- [ ] Arabic letter variants are normalized as specified in A2.

---

### P1-F007 — Term Closure & Archiving

**Description:** The Super Admin can close a term.
- **Closed:** All data of the term (initiatives, sections, enrollments, records) becomes read-only. Only users with the special permission `term.edit_closed` can edit it, and every such edit is audit-logged.
- **Archived:** The term is hidden from default selectors but remains accessible in historical reports.

**User Story:** As Ms. Maha (Super Admin), I want to lock a finished term, so that historical academic records cannot be changed accidentally.

**Trigger:** The Super Admin selects "Close Term".

**Pre-conditions:**
- The term status is Active.

**Post-conditions:**
- The term is Closed, write operations are blocked, and the action is audit-logged.

**Main Flow:**
1. The Super Admin selects "Close Term".
2. The system shows a summary: active initiatives, unsubmitted assignments, and assignments pending review.
3. The Super Admin confirms by typing the term name.
4. The system sets the term to Closed, sets every Active initiative in it to Completed, and blocks writes.
5. A database backup snapshot is triggered.

**Alternate Flows:**
- **A1 — Edit attempt on a closed term:** Users without `term.edit_closed` see read-only views and a "Term closed" banner.
- **A2 — Reopen term:** Only the Super Admin can reopen a term, a reason is mandatory, and the action is audit-logged.

**Acceptance Criteria:**
- [ ] After closure, every write API for the term's entities returns 423 (Locked) for users without the special permission.
- [ ] Closed and Archived term data remains available in reports, dashboards (via the term selector), and the student Initiative History.
- [ ] Closure requires typing the exact term name to confirm.

---

### P1-F008 — Platform Settings & Content Management

**Description:** The Super Admin manages:
- Institution name (AR/EN) and logo (PNG/SVG, at most 1 MB).
- System messages: login page notice and dashboard announcement banner.
- Default interface language.
- University ID validation pattern.
- File upload size limits.
- Session timeout.

**User Story:** As Ms. Maha (Super Admin), I want to change the dashboard announcement without a developer, so that I can communicate deadlines to all users.

**Trigger:** The Super Admin opens Settings.

**Pre-conditions:**
- The user is the Super Admin.

**Post-conditions:**
- The settings are stored, the cache is cleared, and the change is audit-logged.

**Main Flow:**
1. The Super Admin edits the settings.
2. The system validates the values; the ID pattern must be a valid regular expression and is tested against a sample value.
3. The Super Admin saves, and the change applies to all users on their next page load.

**Alternate Flows:**
- **A1 — Invalid logo file:** The upload is rejected with the accepted formats and size shown.
- **A2 — ID pattern change that invalidates existing IDs:** The system warns with the count of existing non-matching IDs. Existing IDs are not modified.

**Acceptance Criteria:**
- [ ] Logo and institution name appear in the header, on the login page, and on all PDF exports.
- [ ] Every settings change is audit-logged with old and new values.
- [ ] A University ID pattern change applies to new imports and entries only.

---

## 8. Non-Functional Requirements

### 8.1 Performance
- Page load (first contentful render) under 2 s at p95 on the college network.
- API response under 500 ms at p95 for non-aggregate endpoints.
- Dashboard aggregates under 3 s at p95 for a term with 30,000 students.
- Instructor grid: 200 students × 15 fields loads in under 2 s.
- Import: 10,000 rows validated and imported in under 5 minutes.
- Global search under 500 ms.

### 8.2 Security
- Session-based authentication with regenerated session IDs on login, and HttpOnly, Secure, SameSite=Lax cookies.
- Argon2id password hashing (bcrypt as fallback) and a minimum password policy of 8 characters with a letter and a digit.
- CSRF tokens on all state-changing requests (Laravel default).
- XSS protection: escaped Blade output everywhere and a strict Content Security Policy.
- SQL injection protection: Eloquent and parameterized queries only; no raw string concatenation.
- Authorization policy on every route plus scope filtering (P0-F002).
- Rate limiting: login at 5 attempts per minute per email and IP; API at 120 requests per minute per user.
- **Public endpoint rules (added v1.1).** A public, unauthenticated endpoint is permitted only where it
  creates no account and exposes no record. The student support form and its status check are the only
  such endpoints, and they carry:
  - Rate limiting on two axes independently: per originating IP address and per University ID, so that
    neither a single network nor a single identifier can be used to flood or to probe.
  - A CAPTCHA on submission.
  - **No enumeration.** A failed verification returns one generic message that never reveals whether
    the University ID exists, whether it is enrolled, or which of the two checks failed — the same
    discipline applied to staff sign-in.
  - The originating IP address recorded on every request for the audit trail.
  - No student account, password, or session is created by either endpoint.
  - Accepted residual risk: a person who knows a valid University ID can submit a request in that
    student's name, because the roster holds no second identifying factor. The request exposes no data,
    so the impact is a falsely attributed request rather than a disclosure. Revisit if university SSO
    becomes available in v2.
- File validation:
  - Checks on extension, MIME type, and content signature.
  - Size limits: 10 MB for attachments and 20 MB for import files.
  - Files stored outside the public web root and served only through authenticated, authorized download routes.
- AES-256 encryption at rest for database and backups (disk-level), and TLS 1.2+ in transit (TLS 1.3 preferred).
- No two-factor authentication in v1, per the owner's decision.

### 8.3 Availability
- 99.9% uptime during active academic terms.
- Daily automated backups with 30-day retention, plus a snapshot on every term closure.
- Recovery Point Objective: 24 hours. Recovery Time Objective: 4 hours.

### 8.4 Scalability
- Traffic profile: TRACK is an internal platform for authenticated staff, with **two public endpoints**
  added in v1.1 — the student support form and its status check. Public traffic is therefore no longer
  N/A, but is expected to stay low: the target in Section 12.4 is fewer than 1 support request per 10
  active users per month, and the endpoints are rate-limited per IP and per University ID. They must be
  sized for burst abuse rather than for sustained volume, and they must not be able to degrade the
  authenticated platform — the rate limiter, not capacity, is the primary control.
- Sizing per term: up to 500 staff users, 3,000 sections, 30,000 students, 50 initiatives, and 1,500,000 field values.
- Concurrency: 50 concurrent users typical and 200 at deadline peaks.
- The architecture must support 10× this volume and a University → College → Campus hierarchy without schema redesign. The organization hierarchy tables exist in v1 with a single college record.
- Imports, launches, recalculations, large exports, and notifications run on queue workers that scale horizontally.

### 8.5 Accessibility
- WCAG 2.1 AA compliance.
- Full keyboard navigation in the instructor grid.
- Color is never the only status indicator; text labels accompany all status colors.

### 8.6 Compatibility
- Browsers: Chrome 100+, Edge 100+, Safari 15+, Firefox 100+.
- Responsive layout: desktop first (1280 px and up) with tablet and mobile support. On screens under 768 px, grids switch to stacked cards.
- Full Arabic RTL and English LTR interfaces, switchable per user.
- All text stored as UTF-8 (utf8mb4).

### 8.7 Data and Compliance
- Compliance with the Saudi Personal Data Protection Law (PDPL) and university information security policy.
- Data hosted within the Kingdom of Saudi Arabia.
- Student data limited to academic identifiers and initiative data; no unnecessary personal fields.
- Soft delete for users, students, branches, and terms. Hard delete is prohibited for records with history.
- Audit logs retained for at least 5 years, and academic term data retained indefinitely.

### 8.8 Observability
- Structured JSON application logs with request ID, user ID, and route.
- Error tracking through Sentry (self-hosted or university-approved instance) [TBD].
- Alerts when the error rate exceeds 1% over 5 minutes, a queue job fails, the queue backlog exceeds 1,000 jobs, or disk usage exceeds 80%.
- A health-check endpoint (`/health`) that monitors the database, Redis, queue workers, and storage.

---

## 9. Technical Architecture

### 9.1 Frontend Stack
- Laravel Blade server-rendered views with Bootstrap 5.3, using the RTL build for Arabic and the LTR build for English.
- Alpine.js for interactivity: modals, inline validation, and auto-save.
- Tabulator for the editable instructor data grid (virtual DOM, inline editing, keyboard navigation).
- Apache ECharts or Chart.js for KPI charts with click-through drill-down events.
- Localization through Laravel language files (`lang/ar`, `lang/en`), with a per-user language preference.
- Web fonts: IBM Plex Sans Arabic for Arabic and Inter for Latin.

### 9.2 Backend Stack
- PHP 8.3+ with Laravel (latest supported major version).
- Modular architecture using domain modules under `app/Modules`:
  - Authentication, User Management, Academic Structure, Academic Terms, Students, Faculty, Sections.
  - Initiatives, Dynamic Fields, Imports, Workflow, Dashboards, Reports, Notifications, Audit, Settings.
- Libraries:
  - `spatie/laravel-permission` for RBAC.
  - `maatwebsite/excel` for Excel import and export.
  - `mpdf/mpdf` for Arabic-capable PDF generation.
- A versioned REST API (`/api/v1/`) backs all interactive screens. It is authenticated with Laravel Sanctum on the same session, which keeps the backend ready for a future mobile app or integrations.
- Laravel Queues on Redis, with Supervisor-managed workers for imports, launches, recalculations, exports, and notifications.
- Laravel Scheduler for deadline notifications, delayed-section detection, and aggregate refresh.

### 9.3 Database Architecture
- MySQL 8.0+ or MariaDB 10.11+ with InnoDB and the utf8mb4 character set.
- Initiative data uses a **typed EAV model**. `student_initiative_values` stores typed columns (`value_text`, `value_number`, `value_date`, `value_bool`, `value_json`, `attachment_id`) linked to a field definition within a schema version.
- The students table stores only core academic identity. No initiative data is ever stored there.
- Aggregate summary tables (`assignment_stats`, `initiative_branch_stats`) are refreshed by queued jobs for dashboard performance.
- Unique indexes enforce the duplicate-detection rules in Section 11.4.
- Redis provides the cache, sessions, and queues.

### 9.4 Authentication and Authorization Design
- Accounts are created only by the Super Admin, with email and an initial password. There is no self-registration.
- Laravel session guard authentication, and Sanctum for API calls from the same origin.
- Users change their password after first login through their profile; a first-login banner recommends it without enforcing it.
- Password reset in v1 is performed only by the Super Admin; self-service email reset is deferred.
- Two-factor authentication is not included in v1.
- Authorization combines permission checks (Laravel Policies with spatie permissions) and data scoping (global Eloquent scopes by branch or section assignment).
- The special permissions `section.access_override` and `term.edit_closed` are assignable by the Super Admin.

### 9.5 Infrastructure and Deployment
- Hosting: university-provided server or a KSA-region VPS [TBD], running Ubuntu 24.04 LTS, Nginx, PHP-FPM 8.3, MySQL/MariaDB, Redis, and Supervisor.
- Environments: Local, Staging, and Production.
- CI/CD with GitHub Actions: lint (Pint), static analysis (Larastan), tests (Pest), build assets (Vite), and a zero-downtime deploy (Deployer or Envoy).
- Private storage disk for uploads and import files.
- Backups: daily database dumps plus storage sync to a separate storage location, with 30-day retention.

### 9.6 External Integrations
- SMTP relay (university mail server or an approved provider) for notification and reminder emails [TBD].
- Sentry for error tracking [TBD].
- No other external integrations in v1. University systems integration is deferred to v2 (P3-F002).

---

## 10. Implementation Phases

### Phase 1 — Foundation
**Goal:** Secure access, RBAC scoping, audit logging, and the complete academic structure with import, so that real roster data exists in the system.
**Duration:** 2 weeks (2026-09-27 → 2026-10-08) · Sprint S1

- [ ] Initialize the Laravel project, modular structure, CI pipeline, and staging environment
- [ ] Implement P0-F001 Authentication & Account Management
- [ ] Implement P0-F002 RBAC with scope filtering and authorization tests
- [ ] Implement P0-F010 Audit Log infrastructure (used by all later features)
- [ ] Implement P0-F003 Academic Structure (terms, branches, courses, sections, students, enrollments)
- [ ] Implement P0-F004 Import Wizard (central and instructor modes, queued)
- [ ] Build the bilingual layout shell (RTL/LTR), navigation, and design tokens
- [ ] Technical spike: validate typed EAV performance with 1.5M seeded values
- [ ] Academic programs, the registrar import mode, and course and staff bulk import *(added v1.1)*
- [ ] Student technical support channel: the public form, the status check, and the staff queue *(added v1.1 — see the scope trade below)*

**Validation:**
- The Super Admin creates coordinators and instructors.
- A real roster file of at least 5,000 rows imports with an error report.
- The Coordinator and Instructor see only their scoped data.
- All authorization tests pass.

**Scope trade recorded 2026-09-27** (required by the constitution's scope-discipline rule, which states
that "Adding scope mid-phase requires an explicit trade recorded in the phase review"):

The student technical support channel was merged into Phase 1 rather than scheduled after launch, at the
Product Owner's decision. What was traded:

| | |
|---|---|
| **Added to this phase** | 110 tasks (Phases 18–25 of the feature task list), none of which appear in the §6 MoSCoW table — this is entirely v1.1 scope |
| **Window unchanged** | 2026-09-27 → 2026-10-08 remains the stated duration |
| **Risk accepted** | The added work competes for the same two weeks as authentication, RBAC, audit logging, academic structure, and import — the items without which no other phase can begin |
| **Recommendation on record** | Scheduling it as a separate post-launch phase was recommended and declined |
| **Containment applied** | Phases 18–25 are strictly additive and sequenced **last**; they depend on Phases 1–17 and nothing in Phases 1–17 depends on them; they touch exactly one pre-existing file, behind a feature flag defaulting to off. They can therefore be **deferred wholesale at any point** without unpicking delivered work |
| **Decision rule if the window tightens** | Drop Phases 18–25 first. The four validation checks above are unaffected by their absence — none of them concerns students |

The four Phase 1 validation checks above are **deliberately unchanged**: the support channel is not part
of the Phase 1 acceptance gate, so "Phase 1 complete" keeps its original meaning.

### Phase 2 — Core Initiative Loop
**Goal:** The complete initiative lifecycle, from definition and launch through data entry, submission, and approval, with automatic completion.
**Duration:** 2 weeks (2026-10-11 → 2026-10-22) · Sprint S2

- [ ] Implement P0-F005 Initiative Management & Dynamic Fields (15 types, schema versioning)
- [ ] Implement P0-F006 Targeting, Assignment creation, and target snapshot
- [ ] Implement P0-F007 Instructor Data Entry Workspace (grid, draft, auto-save, Excel values upload, add or withdraw)
- [ ] Implement P0-F008 Submission & Review Workflow with history
- [ ] Implement P0-F009 Completion calculation and aggregate tables
- [ ] Implement secure attachment storage and download for file and image fields

**Validation:**
- An end-to-end test covers the full cycle: create initiative with 5 field types → launch to 10 sections → instructor enters data → submit → coordinator returns with reason → resubmit → approve.
- Completion figures are verified against a manual calculation.

### Phase 3 — Visibility & Communication
**Goal:** Dashboards, drill-down, reports, exports, notifications, and search.
**Duration:** 2 weeks (2026-10-25 → 2026-11-05) · Sprint S3

- [ ] Implement P1-F001 Role-Based Dashboards
- [ ] Implement P1-F002 Initiative Dashboard & Drill-Down
- [ ] Implement P1-F003 Reports & Advanced Filters
- [ ] Implement P1-F004 Export (Excel, CSV, Arabic PDF, Print)
- [ ] Implement P1-F005 Notification Center, email notifications, scheduled deadline alerts, and reminders
- [ ] Implement P1-F006 Global Search with Arabic normalization

**Validation:**
- Dashboard, report, and export totals match for identical filters.
- An Arabic PDF export is reviewed and approved by the owner.
- Notifications are received for all seven events.
- Performance targets are met with a seeded dataset of 30,000 students.

### Phase 4 — Launch Hardening
**Goal:** Archiving, settings, P2 features as capacity allows, security hardening, pilot, and production launch.
**Duration:** 2 weeks (2026-11-08 → 2026-11-19) · Sprint S4

- [ ] Implement P1-F007 Term Closure & Archiving
- [ ] Implement P1-F008 Platform Settings & Content Management
- [ ] Implement P2 features in priority order as capacity allows (P2-F001 → P2-F004)
- [ ] Security review: OWASP Top 10 checklist, authorization penetration tests, file upload tests
- [ ] Load test: 200 concurrent users on dashboards and the instructor grid
- [ ] Beta pilot with one branch (male and female) on a real initiative
- [ ] Prepare Arabic user guides for each role (one page per role)
- [ ] Configure production environment, backups, monitoring, and alerts
- [ ] Import production roster for the upcoming term

**Validation:**
- The pilot branch completes one initiative cycle with no P0 defects open.
- Security and load tests pass.
- Backup restore is tested successfully.
- The owner signs off on go-live.

---

## 11. Execution Blueprint

### 11.1 User Flows

**Flow A — Super Admin launches an initiative (Ms. Maha)**
1. Ms. Maha logs in at the TRACK login page and lands on College Overview (current term: 2026/2027 — Second Semester).
2. She opens Initiatives and selects "Create Initiative".
3. She enters "Professional Certificates 2026-2" (AR/EN), its dates, and its instructions.
4. She adds the fields Certificate Name (Text, required for completion), Certificate Status (Dropdown: Enrolled / Passed / Failed; required for completion), and Verification URL (URL, optional).
5. She checks the live grid preview and saves. The initiative status is Draft.
6. She sets the status to Ready and opens the Targeting tab.
7. She selects the branches Zulfi-F and Majmaah-F and the course ENG101. The preview shows "2 branches · 38 sections · 21 instructors · 1,412 students".
8. She selects "Launch Initiative" and confirms. The status becomes Active, and 21 instructors receive the notification "A new initiative has been assigned to you".

**Flow B — Instructor enters data and submits (Dr. Faisal)**
1. Dr. Faisal logs in with the account the Super Admin created and sees the first-login banner. He changes his password under Profile.
2. He lands on My Work: 1 initiative, 4 sections, 160 students, 0 completed.
3. He selects the row Professional Certificates → Section 214 → 0% → "Enter Data".
4. The grid loads 40 students with the columns Certificate Name, Certificate Status, and Verification URL.
5. He downloads the pre-filled template, fills it in Excel, and uploads it. The preview shows 38 valid rows and 2 rows with invalid URLs.
6. He applies the valid rows and corrects the 2 URLs inline in the grid.
7. He selects "Save Draft". The progress bar shows "40 / 40 complete — 100%".
8. He selects "Submit for Review". The status becomes Submitted and the grid locks.

**Flow C — Coordinator reviews (Dr. Noura)**
1. Dr. Noura logs in and lands on Branch Overview for Zulfi — Female.
2. The Awaiting Review list shows Section 305 (submitted, 35 / 40 complete).
3. She opens the section in review mode. Five rows are highlighted as missing Certificate Status.
4. She selects "Return" and writes "5 students have no certificate status."
5. The status becomes Returned for Correction and the instructor is notified.
6. The instructor corrects the data and resubmits. Dr. Noura approves, and the status becomes Approved.
7. Branch completion updates on the dashboard within 60 seconds.

### 11.2 Edge Cases

| # | Condition | System Behavior | User Feedback |
|---|---|---|---|
| E01 | Invalid University ID format during import or manual entry | Row or entry rejected; nothing saved | "University ID must match the configured format (e.g., 9 digits)." |
| E02 | Required dynamic field left empty on save | Draft saves; student stays Incomplete | Cell highlighted, with "Required for completion" tooltip |
| E03 | Text entered in an Integer, Decimal, or Percentage field | Value rejected client-side and server-side | "Enter a valid number." / "Enter a value between 0 and 100." |
| E04 | Uploaded file exceeds size limit or has a disallowed type | Upload rejected before storage | "File must be PDF, JPG, or PNG and under 10 MB." |
| E05 | Instructor has no assigned sections | Empty state rendered | "No sections are assigned to you yet. Contact your branch coordinator." |
| E06 | Initiative has no fields flagged Required for Completion | Completion shown as N/A; launch allowed with a warning | "No completion criteria defined — completion cannot be measured." |
| E07 | Report filters return no data | Empty table with a reset option | "No data matches these filters." with a Clear filters button |
| E08 | Network failure during Save Draft | Unsaved changes kept locally in grid memory; retried automatically 3 times | "Connection lost. Your changes are not saved yet — retrying…" then "Saved" or "Save failed — Retry" |
| E09 | Queue worker down during import | Job stays `queued`; alert fires to operations | "Your import is waiting to be processed. You will be notified when it finishes." |
| E10 | Session expired while editing the grid | Save returns 401; unsaved changes preserved in the page | "Your session has expired. Log in again to save your changes." (re-login modal) |
| E11 | Coordinator opens a section URL from another branch | 404 returned; attempt audit-logged | "Page not found." |
| E12 | Instructor tries to edit a Submitted or Approved assignment | Write blocked (API returns 409) | Banner: "This section is under review / approved and cannot be edited." |
| E13 | Edit attempt on a Closed term | Write blocked (API returns 423) | Banner: "This term is closed. Data is read-only." |
| E14 | Same roster file imported twice | Records matched; no duplicates created | "This file appears to have been imported before. 0 new records will be created." |
| E15 | Same student appears twice in one section file | Second row rejected as duplicate | Error report: "Duplicate student in this section (row N)." |
| E16 | Section key duplicate (term + branch + course + number) | Save rejected | "This section already exists for this course, branch, and term." |
| E17 | Instructor in import file not registered | Row flagged; Super Admin chooses to create the account or skip | "Instructor not found: [email]. Create account or skip?" |
| E18 | Section instructor changed mid-initiative | Access moves to the new instructor; entries keep their author | New instructor notified: "Section 214 has been assigned to you." |
| E19 | Student withdrawn after launch | Excluded from denominator; kept in Original Target | Row greyed with "Withdrawn on [date] — [reason]" |
| E20 | Field type change requested on a field with data | Change blocked | "Field type cannot be changed once data exists. Create a new field instead." |
| E21 | Deactivating a user with historical entries | Soft delete; history preserved | "User deactivated. Their previous entries remain on record." |
| E22 | Student in import file with a section that cannot be resolved | Row rejected | Error report: "Section could not be resolved for this row." |

### 11.3 API Design (High Level)

All endpoints are prefixed with `/api/v1`, and each requires authentication and a permission check.

| Feature | Method | Endpoint | Purpose |
|---|---|---|---|
| P0-F001 | POST | /auth/login | Log in with email and password |
| P0-F001 | POST | /auth/logout | End the session |
| P0-F001 | GET | /auth/me | Current user, role, scope, language |
| P0-F001 | PUT | /auth/password | Change own password |
| P0-F001 | GET | /users | List users (Super Admin) |
| P0-F001 | POST | /users | Create a user with an initial password |
| P0-F001 | PUT | /users/{id} | Edit a user |
| P0-F001 | PATCH | /users/{id}/deactivate | Soft-deactivate a user |
| P0-F001 | POST | /users/{id}/reset-password | Super Admin password reset |
| P0-F002 | PUT | /users/{id}/roles | Assign roles |
| P0-F002 | PUT | /users/{id}/branches | Assign coordinator branches |
| P0-F002 | POST | /sections/{id}/access-grants | Grant an instructor special section access |
| P0-F003 | GET, POST | /terms | List or create terms |
| P0-F003 | PUT | /terms/{id} | Edit a term |
| P0-F003 | PATCH | /terms/{id}/status | Change term status (Draft, Active, Closed, Archived) |
| P0-F003 | PATCH | /terms/{id}/set-current | Set the current term |
| P0-F003 | GET, POST | /branches | List or create branches |
| P0-F003 | PUT | /branches/{id} | Edit or deactivate a branch |
| P0-F003 | GET, POST | /courses | List or create courses |
| P0-F003 | GET, POST | /sections | List (filterable) or create sections |
| P0-F003 | PUT | /sections/{id} | Edit a section |
| P0-F003 | PATCH | /sections/{id}/instructor | Change the section instructor |
| P0-F003 | GET | /students | List students within scope |
| P0-F003 | GET | /students/{id} | Student profile with enrollments and initiative history |
| P0-F003 | POST | /sections/{id}/enrollments | Add a student to a section |
| P0-F003 | PATCH | /sections/{id}/enrollments/{studentId}/withdraw | Withdraw a student with a reason |
| P0-F004 | POST | /imports | Upload a central import file |
| P0-F004 | POST | /sections/{id}/imports | Upload an instructor section import |
| P0-F004 | PUT | /imports/{id}/mapping | Save the column mapping |
| P0-F004 | POST | /imports/{id}/validate | Queue validation |
| P0-F004 | GET | /imports/{id}/preview | Preview the validated rows |
| P0-F004 | POST | /imports/{id}/commit | Queue the import |
| P0-F004 | GET | /imports/{id} | Job status and counts |
| P0-F004 | GET | /imports/{id}/errors/download | Download the error report |
| P0-F005 | GET, POST | /initiatives | List or create initiatives |
| P0-F005 | PUT | /initiatives/{id} | Edit initiative basic data |
| P0-F005 | PATCH | /initiatives/{id}/status | Change initiative status |
| P0-F005 | GET | /initiatives/{id}/schema-versions | List schema versions |
| P0-F005 | POST | /initiatives/{id}/fields | Add a field (draft version) |
| P0-F005 | PUT | /initiatives/{id}/fields/{fieldId} | Edit a field |
| P0-F005 | DELETE | /initiatives/{id}/fields/{fieldId} | Deactivate a field in the next version |
| P0-F005 | POST | /initiatives/{id}/schema-versions/publish | Publish a new schema version |
| P0-F006 | POST | /initiatives/{id}/targets/preview | Calculate the targeting impact |
| P0-F006 | POST | /initiatives/{id}/targets | Save the section selection |
| P0-F006 | POST | /initiatives/{id}/launch | Create assignments and snapshot |
| P0-F007 | GET | /assignments | My or scoped assignments |
| P0-F007 | GET | /assignments/{id} | Assignment detail |
| P0-F007 | GET | /assignments/{id}/records | Student grid data |
| P0-F007 | PATCH | /assignments/{id}/records | Batch save changed values (draft) |
| P0-F007 | POST | /assignments/{id}/records/import | Upload values from Excel |
| P0-F007 | POST | /attachments | Upload a file for a file or image field |
| P0-F007 | GET | /attachments/{id} | Authorized file download |
| P0-F008 | POST | /assignments/{id}/submit | Submit for review |
| P0-F008 | POST | /assignments/{id}/approve | Approve |
| P0-F008 | POST | /assignments/{id}/return | Return with a reason |
| P0-F008 | POST | /assignments/{id}/reopen | Super Admin reopen with a reason |
| P0-F008 | GET | /assignments/{id}/history | Workflow history |
| P0-F009 | GET | /initiatives/{id}/completion | Completion aggregates |
| P0-F010 | GET | /audit-logs | Filterable audit log |
| P1-F001 | GET | /dashboards/overview | College Overview KPIs |
| P1-F001 | GET | /dashboards/branch/{branchId} | Branch Overview |
| P1-F001 | GET | /dashboards/my-work | Instructor dashboard |
| P1-F002 | GET | /initiatives/{id}/dashboard | Initiative KPIs and breakdowns |
| P1-F003 | GET | /reports/{type} | Filtered report data |
| P1-F004 | POST | /exports | Create an export (sync or queued) |
| P1-F004 | GET | /exports/{id}/download | Download the export file |
| P1-F005 | GET | /notifications | List notifications |
| P1-F005 | PATCH | /notifications/{id}/read | Mark as read |
| P1-F005 | POST | /reminders | Send a manual reminder |
| P1-F006 | GET | /search | Global scoped search |
| P1-F007 | POST | /terms/{id}/close | Close a term |
| P1-F007 | POST | /terms/{id}/reopen | Reopen a term with a reason |
| P1-F008 | GET, PUT | /settings | Read or update platform settings |

### 11.4 Data Model

| Entity | Key Fields | Relationships |
|---|---|---|
| organizations | id, type (university/college), name_ar, name_en, parent_id | Hierarchy root for future multi-college support; one college record in v1 |
| users | id, organization_id, name_ar, name_en, email (unique), password_hash, locale, is_active, first_login_at, deleted_at | Has roles; has coordinator branches; teaches sections |
| roles / permissions / role_has_permissions / model_has_roles | spatie standard fields | User ↔ Role ↔ Permission |
| coordinator_branches | user_id, branch_id | Coordinator ↔ Branch (many-to-many) |
| branches | id, organization_id, campus_name_ar, campus_name_en, gender (male/female), is_active, deleted_at | Has many sections |
| academic_terms | id, name_ar, name_en, academic_year, semester, start_date, end_date, status, is_current | Has many initiatives and sections |
| courses | id, organization_id, code, name_ar, name_en | Has many sections |
| sections | id (internal unique), term_id, branch_id, course_id, section_number, instructor_id; UNIQUE(term_id, branch_id, course_id, section_number) | Belongs to term, branch, course, instructor; has enrollments |
| section_instructor_history | section_id, instructor_id, assigned_at, unassigned_at, changed_by | Instructor change trail |
| section_access_grants | section_id, user_id, granted_by, granted_at | Special instructor access |
| students | id, university_id (unique), name_ar, name_en, deleted_at | Has enrollments; has initiative records |
| section_enrollments | id, student_id, section_id, status (active/withdrawn), enrolled_at, withdrawn_at, withdraw_reason; UNIQUE(student_id, section_id) | Student ↔ Section |
| initiatives | id, term_id, name_ar, name_en, short_name, description, objective, instructions, start_date, end_date, status, current_schema_version_id, created_by | Has schema versions and assignments |
| initiative_schema_versions | id, initiative_id, version_number, published_at, published_by | Has fields |
| initiative_fields | id, schema_version_id, field_key, label_ar, label_en, type, is_required, default_value, options (JSON), sort_order, visible_to_instructor, editable_by_instructor, show_in_reports, required_for_completion, is_active | Belongs to schema version |
| initiative_sections (Assignments) | id, initiative_id, section_id, status, submitted_at, submitted_by, reviewed_at, reviewed_by, return_reason; UNIQUE(initiative_id, section_id) | Initiative ↔ Section; has targets and records |
| assignment_status_history | assignment_id, from_status, to_status, actor_id, reason, created_at | Workflow trail |
| initiative_targets (Snapshot) | id, assignment_id, student_id, origin (original/added_after_launch), removed_at, removal_reason; UNIQUE(assignment_id, student_id) | Target snapshot per assignment |
| student_initiative_records | id, assignment_id, student_id, is_complete, completed_at, updated_by; UNIQUE(assignment_id, student_id) | Has values |
| student_initiative_values | id, record_id, field_id, value_text, value_number, value_date, value_bool, value_json, attachment_id, updated_by, updated_at; UNIQUE(record_id, field_id) | Typed EAV value |
| assignment_stats / initiative_branch_stats | aggregate keys, target_count, original_target_count, complete_count, completion_pct, refreshed_at | Dashboard aggregates |
| import_jobs | id, user_id, mode (central/section), section_id, term_id, file_path, file_hash, mapping (JSON), status, processed, imported, duplicates, errors | Has import errors |
| import_errors | id, import_job_id, row_number, column, value, reason | Belongs to import job |
| notifications | Laravel standard (id, notifiable, type, data, read_at) | Belongs to user |
| reminders | id, sender_id, scope (JSON), message, recipient_count, sent_at | Created by Super Admin or Coordinator |
| audit_logs | id, user_id, action, entity_type, entity_id, old_values (JSON), new_values (JSON), ip_address, user_agent, created_at | Append-only |
| attachments | id, disk, path, original_name, mime_type, size, uploaded_by | Linked from values or settings |
| settings | key, value (JSON), updated_by | Platform configuration |

**Key data rules:**
- Initiative data is never stored in `students`.
- One student is one row in `students`, regardless of the number of enrollments or initiatives.
- Duplicate detection operates at four levels: University ID, section key, Student + Section, and Student + Assignment.
- Section Number alone is never a unique key.

### 11.5 Build Order
- **Phase 1:** Authentication, RBAC and scoping, and Audit Log first, because every later feature depends on access control and logging. Then the Academic Structure and the Import Wizard, which initiatives require as prerequisite data.
- **Phase 2:** Initiatives and Dynamic Fields, then Targeting and Assignment, then the Instructor Workspace, then the Workflow, then Completion. This order follows the data dependency chain of the core loop.
- **Phase 3:** Dashboards and Drill-Down, Reports, Export, Notifications, and Search. These consume the data produced in Phase 2.
- **Phase 4:** Term Closure, Settings, P2 enhancements, hardening, and pilot.

### 11.6 UX Rules (System-Wide)
- **Validation:**
  - Fields validate on blur and on submit.
  - Errors appear inline beneath the field (or on the cell in grids) in the interface language.
  - Server-side validation always repeats client-side rules.
- **Loading:**
  - Every action button shows a spinner and is disabled until the response arrives.
  - Lists and dashboards show skeleton placeholders.
  - Queued operations show a persistent progress indicator with status polling every 5 seconds.
- **Error:**
  - Recoverable system errors show a toast with a Retry action.
  - Permission errors show a full-page message with a link back to the dashboard.
  - Messages never expose stack traces or internal IDs.
- **Success:**
  - A toast confirms every save, submit, approval, import, and export for 4 seconds.
  - Status badges update immediately.
- **Confirmation:**
  - Destructive or high-impact actions (launch, withdraw, return, close term, deactivate user) require a confirmation modal that restates the impact.
- **Navigation:**
  - Each role reaches its section or initiative work in at most 2 clicks from the landing dashboard.
  - Breadcrumbs appear on all drill-down pages.
- **Direction and language:**
  - Layout, icons, tables, and charts mirror correctly in RTL.
  - Numbers use Western Arabic digits (0–9) in both languages for data consistency.
  - Dates use the Gregorian format DD/MM/YYYY.
- **Status colors:**
  - Not Started (grey), In Progress (blue), Submitted (amber), Returned (red), Approved (green), Delayed (red outline).
  - Every color is always accompanied by a text label.

---

## 12. Success Metrics and KPIs

### 12.1 Business Metrics
| Metric | Target | Measurement Method |
|---|---|---|
| Initiative coverage | 100% of Second Semester 2026/2027 initiatives managed in TRACK | Count of initiatives in TRACK ÷ initiatives in the college initiatives register |
| On-time submission | ≥ 90% of assignments Submitted or Approved by the initiative end date | `assignment_status_history` timestamps vs `initiatives.end_date` |
| Report production time | College-wide initiative report produced in < 5 minutes (baseline: multiple days) | Time from report open to export completion, logged by export events |

### 12.2 Product Metrics
| Metric | Target | Measurement Method |
|---|---|---|
| Instructor activation | ≥ 85% of assigned instructors log in within 7 days of assignment | First login event after the assignment notification |
| Section completion time | Median < 30 minutes for a 40-student section | Time between first save and submission per assignment |
| Import first-attempt success | ≥ 97% of rows imported without error | `import_jobs.imported ÷ processed` |
| Return rate | < 15% of submissions returned for correction | Returned transitions ÷ Submitted transitions |
| Coordinator review turnaround | Median < 48 hours from submission to decision | `assignment_status_history` timestamps |

### 12.3 Technical Metrics
| Metric | Target | Measurement Method |
|---|---|---|
| API latency | p95 < 500 ms | Application performance logs |
| Dashboard load | p95 < 3 s at 30,000 students | Synthetic monitoring on staging and production |
| Error rate | < 0.1% of requests return 5xx | Nginx and application logs, Sentry |
| Uptime | ≥ 99.9% during active terms | External uptime monitor on `/health` |
| Import throughput | 10,000 rows in < 5 minutes | `import_jobs` start and end timestamps |

### 12.4 User Satisfaction Metrics
| Metric | Target | Measurement Method |
|---|---|---|
| System Usability Scale | SUS ≥ 70 | In-app survey after the first initiative cycle (instructors and coordinators) |
| Coordinator satisfaction | CSAT ≥ 4.0 / 5 | Short survey at term closure |
| Support requests | < 1 support request per 10 active users per month | TRACK's own `support_requests` table — the student support channel added in v1.1. No external support tool is used |

**Tracking tool:** Self-hosted PostHog (to keep data within approved infrastructure) for product analytics, plus TRACK's own audit and event tables for workflow metrics.
**Review frequency:** 1 week, 2 weeks, and 30 days after launch, then at each term closure.
**Metrics owner:** Dr. Ahmed Ismail Shahin.

---

## 13. Timeline and Milestones

- **Start Date:** 2026-09-27
- **Target Launch:** 2026-11-22
- **Total Duration:** 8 weeks

| Milestone | Description | Due Date | Status | Owner |
|---|---|---|---|---|
| M1 — Kickoff | Team onboarding, environments, repository, CI setup | 2026-09-27 | TODO | Dr. Ahmed Ismail Shahin |
| M2 — PRD Approved | PRD v1.0 signed off by Product Owner and Engineering Lead | 2026-10-01 | TODO | Dr. Ahmed Ismail Shahin |
| M3 — Phase 1 Done | Auth, RBAC, Audit, Academic Structure, Import Wizard complete | 2026-10-08 | TODO | Engineering Lead [TBD] |
| M4 — Phase 2 Done | Full initiative loop working end to end | 2026-10-22 | TODO | Engineering Lead [TBD] |
| M5 — Phase 3 Done | Dashboards, reports, exports, notifications, search complete | 2026-11-05 | TODO | Engineering Lead [TBD] |
| M6 — Beta Launch | Pilot with one branch (male and female campuses) on a real initiative | 2026-11-08 | TODO | Dr. Ahmed Ismail Shahin |
| M7 — Phase 4 Done | Archiving, settings, security and load tests, production ready | 2026-11-19 | TODO | Engineering Lead [TBD] |
| M8 — Public Launch | All six branches onboarded; production live for the upcoming term | 2026-11-22 | TODO | Dr. Ahmed Ismail Shahin |

---

## 14. Risk Register

Score = Likelihood × Impact, where High = 3, Medium = 2, Low = 1.

| ID | Description | Likelihood | Impact | Score | Mitigation | Owner |
|---|---|---|---|---|---|---|
| R-01 | 18 P0 and P1 features within 8 weeks cause timeline overrun | High | High | 9 | Enforce MoSCoW strictly; P2 only if capacity remains; weekly scope review; P1-F007 and P1-F008 are the first candidates for a post-launch patch if needed | Dr. Ahmed Ismail Shahin |
| R-02 | Roster exports from the registration unit are inconsistent (column names, missing instructor emails, mixed ID formats) | High | Medium | 6 | Flexible column mapping; detailed error reports; obtain sample files in week 1 and test against them | Backend Developer |
| R-03 | Typed EAV model degrades report and dashboard performance at scale | Medium | High | 6 | Phase 1 performance spike with 1.5M values; aggregate summary tables; composite indexes | Backend Developer |
| R-04 | Low instructor adoption due to habit of emailing Excel files | Medium | High | 6 | Excel template upload in the grid; one-page Arabic guides; coordinator reminders; leadership announcement at launch | Dr. Ahmed Ismail Shahin |
| R-05 | Key person dependency on a single developer | Medium | High | 6 | Code reviews; documented architecture decisions in the repository; runbooks for deploy and restore | Engineering Lead [TBD] |
| R-06 | Arabic PDF rendering defects (shaping, RTL tables) | Medium | Medium | 4 | Use mPDF with Arabic fonts; owner review of sample PDFs in Phase 3 | Backend Developer |
| R-07 | Production hosting not provisioned in time by university IT | Medium | Medium | 4 | Submit the hosting request in week 1; prepare a KSA-region VPS fallback | Dr. Ahmed Ismail Shahin |
| R-08 | Unauthorized access to student data across branches | Low | High | 3 | Global scopes, per-endpoint authorization tests, security review in Phase 4, audit logging of denied access | Backend Developer |
| R-09 | Simple password-only authentication without 2FA exposes accounts to credential misuse | Low | Medium | 2 | Strong hashing, login rate limiting, session expiry, first-login password change banner; revisit 2FA in v2 | Dr. Ahmed Ismail Shahin |

**External dependencies:**
- Availability and quality of registration roster exports.
- University IT provisioning of hosting, domain, TLS, and SMTP.
- Readiness of design assets (logo, institutional identity).
- Stakeholder availability for phase-end reviews and nomination of coordinators before beta.

---

## 15. Stakeholders and Approvals

### 15.1 Stakeholders
| Name | Role | Involvement | Contact |
|---|---|---|---|
| Dr. Ahmed Ismail Shahin | Product Owner / PM | Owns requirements, priorities, acceptance, launch decision | a.shahin@mu.edu.sa |
| [TBD] | Engineering Lead | Technical decisions, code quality, delivery | [TBD] |
| [TBD] | College Leadership (Dean / Deputy Dean) | Sponsor; consumes college-wide reports | [TBD] |
| [TBD] | Super Admin (College Initiatives Office) | Primary system administrator; UAT participant | [TBD] |
| [TBD] | Branch Coordinators (6 branches) | Pilot users; review workflow validation | [TBD] |
| [TBD] | Instructor Representatives | UAT participants for the data entry workspace | [TBD] |
| [TBD] | University IT / Information Security | Hosting, SMTP, security approval | [TBD] |
| [TBD] | Registration Unit | Source of roster exports | [TBD] |

### 15.2 Approval Gates
| Gate | Approver | Required By | Status |
|---|---|---|---|
| PRD v1.0 Approval | Product Owner + Engineering Lead | 2026-10-01 | Pending |
| Phase 1 Acceptance | Product Owner | 2026-10-08 | Pending |
| Phase 2 Acceptance (core loop) | Product Owner | 2026-10-22 | Pending |
| Phase 3 Acceptance (dashboards and exports) | Product Owner | 2026-11-05 | Pending |
| Security and Hosting Approval | University IT / Information Security | 2026-11-12 | Pending |
| Beta Pilot Sign-off | Product Owner + Pilot Branch Coordinators | 2026-11-17 | Pending |
| Go-Live Approval | Product Owner + Engineering Lead | 2026-11-19 | Pending |

---

## 16. References and Links

| Reference | Link |
|---|---|
| Source Concept Document | TRACK Platform concept specification (Arabic), 2026-09-25 |
| Design Files | [TBD] |
| Repository | [TBD] |
| API Documentation | [TBD] |
| Architecture Diagrams | [TBD] |
| Staging Environment | [TBD] |
| Production Environment | [TBD] |
| CI/CD Pipeline | [TBD] |
| Monitoring Dashboard | [TBD] |
| Related PRDs | [TBD] |
| Slack / Team Channel | [TBD] |
| Meeting Notes | [TBD] |

---

## 17. Revision History

| Version | Date | Author | Changes |
|---|---|---|---|
| v1.0 | 2026-09-25 | Dr. Ahmed Ismail Shahin | Initial PRD created |
| v1.2 | 2026-09-27 | Dr. Ahmed Ismail Shahin | Student technical support channel **merged into Phase 1** rather than scheduled separately, at the Product Owner's decision. §10 Phase 1 gains two deliverables and carries the **explicit scope trade** the constitution requires; the four Phase 1 validation checks are deliberately unchanged, so the acceptance gate keeps its original meaning. The recommendation to schedule it separately is recorded as declined. |
| v1.1 | 2026-09-27 | Dr. Ahmed Ismail Shahin | Student technical support channel added, per `specs/changes/CR-002-student-support.md`. Six sections amended: §1 project type and version · §4.1 the public support form and the staff-side queue added to scope · §8.2 public endpoint rules (dual-axis rate limiting, CAPTCHA, no enumeration, accepted residual risk) · §8.4 traffic profile no longer N/A, two public endpoints at low volume · §12.4 the support channel `[TBD]` resolved to TRACK's own `support_requests` table · §17 this entry. **§3.4 was deliberately left unchanged** — the non-goal "does not provide self-registration for any user type" remains literally true, because students receive no account. Students gain exactly one capability: raising a support request |
