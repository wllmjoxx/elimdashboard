# GGP Elim website

Laravel 13 + Inertia 3 (React 19, TypeScript) + Tailwind 4. Two developers share
this repo; follow CONTRIBUTING.md (Bahasa Indonesia) to avoid merge conflicts.

## Rules

- Every feature is a module in `app/Modules/<Name>` (key = kebab-case name).
  Create one with `php artisan make:module <Name> --title="..." --icon=<LucideIcon>`.
- Keep module code inside its folders: `app/Modules/<Name>/**`,
  `resources/js/pages/<key>/**`, `resources/js/modules/<key>/**`,
  `tests/Modules/<Name>/**`.
- Register routes, migrations, permissions, sidebar items and seeders through
  the module's ServiceProvider. Never add them to shared files
  (`routes/web.php`, `app-sidebar.tsx`, `DatabaseSeeder.php`, `bootstrap/providers.php`).
- Route and permission names start with the module key (`prayer.index`, `prayer.view`).
- Never edit a migration that is already on `main`; add a new one in the
  module's `Database/Migrations`.
- Shared files (`app/Support`, `app/Providers`, `bootstrap`, `config`,
  `resources/js/components`, `resources/js/layouts`, `resources/css`,
  `composer.json`, `package.json`, `.github`) change only in small, separate PRs.
- Public (visitor) pages go in `resources/js/pages/<key>/public/` and get
  `PublicLayout` automatically. Their look follows the church's event
  slides: headings `font-display` (heavy, tight), labels `font-condensed`
  (uppercase, wide tracking), dark bands `bg-ember grain`, square buttons
  (`publicButton` from the layout), hairline `Panel`s instead of glass.
  Brand colors are `flame`, `gold`, `cross`, `sky`, `iris`.
- UI text is in Bahasa Indonesia; code and identifiers are in English.
- Product requirements: PRD "Website GGP Elim – Semua Tahap" (Tahap 1 is in scope).

## Commands

- Checks (same as CI): `composer ci:check`
- Format: `composer lint` (Pint) and `npm run check:fix`
- Tests: `php artisan test` or `php artisan test tests/Modules/<Name>`
- Build: `npm run build` (tests render pages from the Vite manifest)
