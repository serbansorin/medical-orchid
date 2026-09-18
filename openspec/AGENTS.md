# openspec/ — Spec Source of Truth

Planning-stage store. No application code here — specs only. Read before any step.

## Read order (always)

1. `MASTER_PLAN.md` — status roll-up, 26-row menu→step→design index, key flows, Step 0 runbook.
2. `changes/ems-orchid-rebuild/tasks.md` — ordered Step 0–7 checklist. One step = one menu link.
3. The page's `designs/<page>.md` — only then implement that page.

## Structure map

| Path | Holds |
|---|---|
| `MASTER_PLAN.md` | Change map (status markers ✅ Planned / ⬜ Next / ⬜ Queued), menu→step→design index, lineage flows, scaffold runbook |
| `changes/ems-orchid-rebuild/proposal.md` | Why/what/scope: Filament-to-Orchid rebuild, same DB, out-of-scope list |
| `changes/ems-orchid-rebuild/specs/requirements.md` | FR-1..18 + NFR-1..4; NFR-3 is canonical pagination (server-side, 25/page, natural-key search) |
| `changes/ems-orchid-rebuild/design.md` | Architecture decisions, panel/menu map, per-role data-scope rules, screen anatomy |
| `changes/ems-orchid-rebuild/tasks.md` | Checkboxes Steps 0–7; done rule in lines 3–5: Pint clean + browser click-through at https://med-orch.test |
| `changes/ems-orchid-rebuild/designs/` | Per-page specs, 28 files (MASTER_PLAN's "24 page designs" line is stale, trust the count) |
| `specs/` + `changes/archive/` | Empty OpenSpec placeholder dirs, leave alone |

## Update workflow

- Work steps in order; never skip ahead. One step = one menu link.
- Per-step done = Pint clean + browser click-through at https://med-orch.test (tasks.md lines 3–5).
- On completion update BOTH ledgers: tasks.md checkboxes AND MASTER_PLAN change-map status.
- Status-sync obligation comes from the root file; this dir just executes it.

## Design-doc template contract

- H1: `# Details & Design: <Page>`.
- Simple pages: `## Details` (menu path, models, legacy ref) then `## Design` (List / Form / Roles).
- Star pages add `## Relationship inventory` + a Detail Screen block: Header view, Command bar, numbered Tabs, route `platform/<entity>/{id}?tab=`.
- Star set (exactly 4): Patient, Appointment, Medical Visit, Billing Invoice.
- Quoting the markers ✅/⬜/📋/★ above is file data, not decoration.

## Anti-patterns

- No page implementation without reading its design file first.
- No reordering or renumbering steps; index order is the build order.
- No marking done without Pint + browser evidence.
- No hand-editing design files' analysis sections; they encode legacy source analysis.
- No ledger skew: MASTER_PLAN and tasks.md must always agree.
- No new AGENTS.md inside designs/, specs/, or the change dir; this file is the only child.
