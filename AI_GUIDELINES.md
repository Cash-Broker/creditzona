# AI Guidelines

## Project Overview

CreditZona is a Bulgarian lead-generation website plus an internal CRM/admin panel for credit consultants.

Main domains:

- Public marketing website with SEO-focused pages.
- Lead intake and assignment workflow.
- Contact message intake and email notification.
- Blog and FAQ content management.
- Internal Filament-based admin panel for staff.

Current architecture style:

- Laravel MVC with a thin service layer.
- Hybrid server-rendered shell plus Vue SPA frontend.
- Filament 5 for the internal admin panel.

Important structural folders:

- `app/Http/Controllers`: public and API controllers.
- `app/Http/Requests`: request validation and normalization.
- `app/Services`: business workflows. Currently used mainly for lead creation/assignment.
- `app/Support/Seo`: centralized SEO metadata, schema, and browser payload generation.
- `app/Filament`: admin CRM resources, schemas, tables, pages, widgets.
- `app/Models`: Eloquent models, scopes, and relationships.
- `resources/views/layouts/app.blade.php`: Laravel shell that bootstraps the Vue app.
- `resources/js`: Vue SPA pages, components, composables, router, SEO helpers.
- `database/migrations` and `database/seeders`: schema and demo content/users.

Main public flow:

1. Laravel serves a public route and injects `window.appConfig`.
2. Vue renders the matching page component.
3. User submits a lead or contact form.
4. Laravel validates the request.
5. Business rules run in service/controller boundary code.
6. Data is persisted and exposed to staff via Filament.

## Tech Stack

- Backend: PHP 8.2, Laravel 12, Eloquent ORM, FormRequest, Mailables, Artisan commands.
- Admin panel: Filament 5.
- Frontend: Vue 3, Vue Router 4, Vite 7, Tailwind CSS 4, FontAwesome.
- Testing: PHPUnit 11, Laravel Feature and Unit tests, in-memory SQLite for tests.
- Tooling: Laravel Pint available, `.editorconfig`, Vite, queue support in the dev workflow.
- Installed but not meaningfully adopted yet: `spatie/laravel-permission`, Axios bootstrap.

## Architecture Rules (VERY IMPORTANT)

### 1. Overall Architecture

- Treat this project as Laravel MVC plus a thin service layer, not as repository-driven or DTO-driven architecture.
- Do not introduce repositories, CQRS, action classes, large DTO hierarchies, or a new state-management framework unless the change is explicitly justified and applied consistently across the module.
- Use Eloquent models and model scopes for simple reads.
- Use service classes for business workflows, side effects, and reusable write logic.

### 2. Controllers Must Stay Thin

- Controllers may accept requests, call services, and return responses.
- Controllers must not own business rules, assignment algorithms, normalization rules, or multi-step side effects.
- If a controller starts persisting data, sending mail, dispatching jobs, or branching on domain rules, extract a service.

### 3. Validation Rules

- Every write endpoint must use a dedicated `FormRequest`.
- Normalize inbound data in `prepareForValidation()`.
- Keep validation rules and validation messages in the `FormRequest`, not inline in controllers.
- If a validation rule is reused, extract a reusable rule/helper instead of duplicating closures and messages.

### 4. Service Layer Rules

- Service methods should receive validated data and return domain models or explicit result objects.
- Services own business rules.
- Services should become transaction-aware when multiple writes or side effects must succeed together.
- Future CRM workflows must follow the `LeadService` direction, not grow inside controllers or Filament callbacks.

### 5. Model Rules

- Models may contain relationships, casts, scopes, and small domain helpers.
- Models must not contain HTTP logic, request parsing, or UI concerns.
- Prefer reusable scopes like `published()`, `ordered()`, `latestPublished()` for repeated read filters.

### 6. Filament Rules

- Follow the existing Filament 5 split:
  - `Resource`
  - `Schemas`
  - `Tables`
  - `Pages`
  - `Widgets`
- Keep admin labels and user-facing admin copy in Bulgarian.
- Non-trivial admin actions must call services rather than embed domain logic inside resource/page callbacks.
- Always import referenced model classes explicitly in Filament closures, infolists, tables, and forms.

### 7. Public Page Rules

- Every public page is dual-layered and must stay in sync across Laravel and Vue.
- Any new or changed public page must update all applicable layers in the same change:
  - `routes/web.php`
  - `PageController`
  - `config/seo.php`
  - `SeoManager::browserPayload()` if JS route exposure changes
  - `resources/js/router/index.js`
  - the Vue page/component
  - sitemap generation if the page is indexable
  - tests

### 8. SEO Is First-Class

- SEO is not optional or decorative in this project.
- Use the centralized SEO system:
  - `config/seo.php`
  - `App\Support\Seo\SeoManager`
  - `resources/js/seo/index.js`
- Do not hardcode ad-hoc meta tags or structured data in random files when the centralized SEO pipeline should be extended instead.
- New public pages require title, description, canonical, robots, breadcrumbs, and structured-data consideration.

### 9. Authorization and Roles

- Staff/admin authorization must be explicit.
- Never assume that any authenticated user may access Filament or staff data.
- Public users must never gain access to the admin panel.
- Until there is a deliberate migration, do not mix raw `role` column checks with Spatie permission APIs inside the same feature.
- If authorization is touched, policies and tests must be updated together.

### 10. Domain Constants

- Do not spread raw strings for lead status, credit type, property type, or user role across new code.
- Reuse centralized mappings/constants where they already exist.
- If expanding these domains, prefer a full refactor toward backed enums or dedicated domain classes instead of partial one-off constants.

## Coding Standards

- Follow PSR-12 and Laravel conventions. Format PHP with Laravel Pint.
- Respect `.editorconfig`: UTF-8, LF, 4 spaces.
- Keep code identifiers in English.
- Keep user-facing copy, validation messages, and admin labels in Bulgarian.
- Use clear, descriptive names such as `createLead`, `latestPublished`, `StoreLeadRequest`.
- Keep methods focused on one responsibility.
- Prefer constructor or method injection for real dependencies.
- Keep response formatting at the boundary layer.
- Do not leave `console.log`, `console.warn`, `dd()`, temporary comments, or commented-out dead routes/components in production code.
- Do not copy-paste query fragments, response parsing, validation logic, or route path logic once duplication is visible.
- Catch broad exceptions only at true application boundaries, log enough context, and return safe user-facing messages.

## Database Rules

- Every schema change must come with a migration, model updates, and tests.
- Index columns that are queried, filtered, or sorted frequently.
- Use foreign keys with explicit delete behavior.
- Do not add loosely defined free-form state columns when a constrained domain value is more appropriate.
- Use casts for booleans, datetimes, encrypted values, and structured data.
- If future work introduces sensitive personal data such as EGN, document numbers, or financial identifiers:
  - encrypt at rest
  - mask in UI
  - exclude from public APIs
  - guard with policies
  - test access rules
- When extending lead data, update all of these together:
  - migration
  - `Lead` model
  - `StoreLeadRequest`
  - `LeadService`
  - Filament resource schema/table/infolist
  - Vue composable/form
  - tests

## API Rules

- All write endpoints must validate through `FormRequest`.
- Existing endpoint response shapes must be preserved unless an API contract refactor is explicitly part of the task.
- New endpoints should prefer a consistent JSON contract:
  - success: `data`, optional `message`, optional `meta`
  - validation failure: Laravel-standard `message` plus `errors`
  - server failure: safe message only, no raw exception leakage
- Never return entire models blindly. Select only the fields that are intentionally public.
- If a page controller and an API controller need the same query or payload shape, extract shared query/transform logic instead of duplicating it.
- Public APIs must never expose internal admin-only fields, role data, or future sensitive applicant data.
- Side effects such as mail, notifications, and assignments belong in services/jobs, not inline controller code.

## Frontend Rules

- Treat the frontend as a Vue SPA hydrated by Laravel.
- Before adding a fetch call, check whether the data should be seeded through `window.appConfig.initialData`.
- Keep page-level orchestration in `resources/js/pages`.
- Keep reusable async/state logic in composables.
- Keep presentational UI in components.
- Do not duplicate business rules across multiple components.
- Use the centralized SEO utilities and `appConfig` helpers.
- Reuse existing design tokens and component utility classes from `resources/css/app.css`.
- Maintain the current folder split:
  - `pages`
  - `components`
  - `composables`
  - `data`
  - `constants`
  - `seo`
  - `utils`
- Do not introduce Pinia, Vuex, or another global state tool unless the current composable-based approach clearly stops being sufficient.
- Do not add more raw `fetch` duplication. Prefer a shared API helper/composable when a new request repeats headers, JSON parsing, and error handling.
- Never leave debug logging in client code.
- If a route is public and crawlable, it must exist in both Laravel and Vue and must have matching SEO configuration.
- Do not build on dormant public pages such as `ConsumerPage`, `MortgagePage`, `RefinancePage`, or `DebtBuyoutPage` unless the routes, controller actions, SEO entries, and tests are wired in the same change.

## Testing Rules

- Any behavior change requires tests.
- Prefer Feature tests for Laravel behavior, especially:
  - HTTP endpoints
  - validation
  - service workflows that touch the database
  - SEO/meta rendering
  - artisan commands
- Use Unit tests for small pure mappings, option lists, or isolated helpers.
- Prefer realistic Laravel integration tests over heavily mocked tests.
- When changing lead rules, assignment behavior, admin permissions, or API contracts, add regression tests.
- Add authorization tests whenever policies, roles, or Filament access rules change.
- Add response-shape tests when introducing or refactoring endpoints.
- New work should close current testing gaps around contact-message flow, blog/FAQ API behavior, and admin authorization.

## DO / DON'T Rules (CRITICAL)

### DO

- Follow the existing Laravel + Vue + Filament structure.
- Keep controllers thin and move business logic into services.
- Use `FormRequest` classes for every write endpoint.
- Reuse model scopes for repeated read filters.
- Update Laravel routes, Vue routes, SEO config, and tests together for public-page changes.
- Keep all user-facing copy and validation messages in Bulgarian.
- Select explicit public fields in queries and API responses.
- Centralize repeated domain options and mappings.
- Queue mail, notifications, and jobs when the user response does not need to block.
- Write or update tests with every meaningful behavior change.
- Import model classes explicitly in Filament and closure-heavy files.
- Verify README or docs against code before assuming a feature already exists.

### DON'T

- Don't put business logic, assignment logic, or side effects in controllers, Blade views, or Vue templates.
- Don't introduce repositories, DTO suites, CQRS, or new frontend state architecture just because it is fashionable.
- Don't mix raw `role` checks and Spatie permission APIs ad hoc.
- Don't change existing API contracts without deliberate coordination and tests.
- Don't hardcode SEO metadata or structured data outside the centralized SEO system.
- Don't duplicate validation rules/messages or response parsing unnecessarily.
- Don't expose internal admin fields or future sensitive applicant data in public APIs.
- Don't leave debug logging, dead comments, or temporary routes in committed code.
- Don't add new lead fields in only one layer.
- Don't assume planned CRM features from the README are already implemented.
- Don't allow any authenticated user to access staff-only admin functionality.

## Refactoring Suggestions

Current weaknesses that should be improved gradually:

- Extract lead statuses, credit types, property types, and similar repeated domain values into PHP enums or dedicated domain classes and mirror them cleanly in JS.
- Unify API response conventions across leads, blogs, FAQs, and contact messages without breaking current consumers.
- Extract repeated frontend request/error parsing into a shared API client utility.
- Remove production debug logs from `useContactForm` and similar code.
- Move contact-message persistence plus email dispatch into a dedicated service and queue the mail send.
- Strengthen admin authorization:
  - restrict Filament access to staff roles only
  - add policies/permissions per resource
  - stop relying on broad authenticated access
- Decide whether Spatie Permission will become the real authorization system. If yes, migrate fully. If not, remove unused package complexity.
- Reduce duplication between page controllers and API controllers for blog/FAQ queries and payload shaping.
- Audit Filament files for explicit imports and closure type-hint correctness.
- Add automated lint/static-analysis tooling for frontend and PHP beyond the current test suite.
- Add tests for contact-message flow, blog/FAQ API payloads, and Filament access rules.
- Resolve documentation drift between `README.md` and the actual implementation, especially around unimplemented CRM features and sensitive-data handling.
- Either fully wire or remove dormant service pages so the codebase does not keep half-integrated public flows.

## AI Instructions (CRITICAL SECTION)

- Follow existing patterns first. Consistency is more valuable than cleverness.
- Treat this file as the default authority for project structure unless the codebase clearly establishes a stronger, already-adopted pattern.
- When unsure, mimic the nearest existing module that solves the same type of problem.
- Prefer best practices over current bad examples, but improve them in a way that fits the existing architecture instead of importing a whole new paradigm.
- Never assume a future plan described in docs is already implemented. Verify against actual code.
- Any public page change is multi-layer by definition:
  - Laravel route
  - `PageController`
  - SEO config
  - Vue route/page
  - tests
- Any lead workflow change is multi-layer by definition:
  - request
  - service
  - model
  - admin UI
  - frontend form
  - tests
- Keep the project localized for Bulgarian users and staff.
- Protect SEO, public response contracts, and admin security whenever you change code.
- Preserve working behavior unless the task explicitly requires a behavior change or refactor.
- If you encounter a bad existing pattern, do not spread it further. Contain it and refactor it only when the surrounding task justifies the change.
- Before introducing a new abstraction, ask:
  - Is this already used elsewhere in the project?
  - Does it solve real duplication or complexity here?
  - Can the same outcome be achieved using the current stack and patterns?
- Prefer small, production-ready increments over sweeping rewrites.
- If a change touches validation, SEO, routing, permissions, or database shape, assume tests are required.
- Never deliver code that is only demo-complete. Ship changes as if they are going to production.
