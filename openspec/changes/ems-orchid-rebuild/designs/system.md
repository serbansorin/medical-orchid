# Details & Design: System (Menus mirror, Imports, Articles)

## Details
- **Menu**: Sistem → Menus & Permissions (read-only), Imports, Articles · **Tables**: `menus`,
  `menu_role_permissions`, `imports`, `articles`, plus pathways (`pathways`, `pathway_schedulers`,
  `pathway_treatment`) if kept.
- **Purpose**: transparency of role→menu mapping + operational imports + content.

## Design
- **Menus mirror**: read-only table — slug, label, URL, sort order, roles allowed (from
  `menu_role_permissions`). No editing here (Orchid menu is code-defined in the panel provider);
  used to verify Orchid menu matches legacy entitlements during walkthroughs.
- **Imports**: table — file, type, status, rows ok/fail, started by/at. Upload form (file + type);
  processing reuses legacy import jobs; failures downloadable (legacy `error_import.log` pattern).
- **Articles**: simple CRUD (title, slug, body rich, published flag) for in-panel help content.
- **Roles**: super-admin only.
