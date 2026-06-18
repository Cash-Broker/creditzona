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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v12
- vue (VUE) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app\Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app\Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== filament/filament rules ===

## Filament

- Filament is used by this application. Follow the existing conventions for how and where it is implemented.
- Filament is a Server-Driven UI (SDUI) framework for Laravel that lets you define user interfaces in PHP using structured configuration objects. Built on Livewire, Alpine.js, and Tailwind CSS.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Always inspect required options before running a command, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Actions encapsulate a button with an optional modal form and logic:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data))

</code-snippet>

### Testing

Always authenticate before testing panel functionality. Filament uses Livewire, so use `Livewire::test()` or `livewire()` (available when `pestphp/pest-plugin-livewire` is in `composer.json`):

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
use function Pest\Livewire\livewire;

livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

<code-snippet name="Calling actions in pages" lang="php">
use Filament\Actions\DeleteAction;
use function Pest\Livewire\livewire;

livewire(EditUser::class, ['record' => $user->id])
    ->callAction(DeleteAction::class)
    ->assertNotified()
    ->assertRedirect();

</code-snippet>

<code-snippet name="Calling actions in tables" lang="php">
use Filament\Actions\Testing\TestAction;
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, and `Fieldset` do not span all columns by default. Explicitly set column spans when needed.

</laravel-boost-guidelines>
