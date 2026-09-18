# Details & Design: Doctor Dashboard (sophisticated)

## Source analysis (what we are porting)

Legacy doctor experience is a full React SPA (`resources/js/admin/doctor`, Mingle-mounted in the
Filament Doctor panel) whose `HomePage.tsx` composes **10 widgets**:

| # | React widget | What it does |
|---|---|---|
| 1 | `CurrentPatientWidget` (TOP PRIORITY) | Current/next patient card: who is in the chair now, one-click jump to file |
| 2 | `StatsCardsGrid` | KPI cards: today's appointments, completed, pending, week load |
| 3 | `TodayProgressWidget` | Day progress: done / remaining appointments bar |
| 4 | `MiniCalendarWidget` | Month mini-calendar with busy-day dots → jumps to day |
| 5 | `UpcomingAppointmentsWidget` | Next appointments list with patient links |
| 6 | `RecentPatientsWidget` | Recently seen patients with return shortcuts |
| 7 | `QuickAddPatientWidget` | Inline quick-create patient (CNP scan modal, patient search modal) |
| 8 | `QuickMessagesWidget` | Latest internal messages / chats preview |
| 9 | `AlertsWidget` | Unconfirmed items, abnormal labs, pending signatures |
| 10 | `ShortcutsWidget` (+ Calendar/Counter) | One-click actions: appointment wizard, prescription, referral dialog, schedule |

Supporting React pages mirrored as Orchid actions/screens: Calendar, Appointments, Schedule /
Availability, Prescription, Diagnostic, Referral, Lab orders, Medications, Messages, Billing,
Reports, Current-patient dashboard.

## Design (Orchid mapping — no React, same information density)

- **Route**: `platform/dashboard` for role `doctor` (role→dashboard redirect in Step 0).
- **Row 1 — Now bar** (`Layout::view`, pinned): current + next appointment cards (patient, time,
  type, status) with buttons: Open file (→ ★ Patient detail), Start visit (creates visit → ★ Visit
  detail). The `CurrentPatientWidget` equivalent.
- **Row 2 — KPI metrics** (`Layout::metrics`, 6 cards = `StatsCardsGrid` + `TodayProgressWidget`):
  today's total, completed, remaining, unconfirmed count, open visits, Rx awaiting signature.
  Done/remaining progress bar rendered in the header view.
- **Row 3 — two columns** (`Layout::columns`):
  - **Left**: Today's agenda table (`UpcomingAppointmentsWidget`: time, patient link, type, status,
    inline Start visit) + Week strip (7-day mini list = simplified `MiniCalendarWidget`: day, count,
    busy dot; click filters agenda by day).
  - **Right**: Alerts table (`AlertsWidget`: unconfirmed appointments, unsigned prescriptions,
    abnormal vitals/labs flags, time-off conflicts) + Recent patients table
    (`RecentPatientsWidget`: last 8 seen, link to file) + Messages preview (`QuickMessagesWidget`:
    latest 5 internal messages/chats, link to inbox).
- **Command bar**: Quick add patient (modal: name/CNP/phone → creates + opens file),
  New appointment (wizard prefilled), New prescription, Order labs, Request referral
  (= `ShortcutsWidget`).
- **Scope**: assigned clinics only (`clinic_doctor_assignments`); all counts doctor-scoped.
- **Performance**: each widget = one `query()` key / async block; agenda + alerts first,
  messages/recent async.
- **i18n**: `en` + `ro` (React strings are RO-first; reuse legacy `lang/` keys where present).
