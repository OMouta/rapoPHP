# RapoPHP

RapoPHP is a modern, lightweight PHP framework designed to bring the **Next.js developer experience** to the PHP world. It moves away from traditional MVC patterns in favor of a **component-centric architecture**, file-based routing, and built-in reactivity.

> **⚠️ Warning: RapoPHP is in early development. APIs and features may change significantly and security improvements are ongoing. Use at your own risk.**

## Key Modern Features

### Zero-Config File-based Routing (Next.js App Router Style)

- **Folder-Based URL Segments**: Your directory structure in `src/Pages` directly maps to URLs.
- **Routable Files**: A route is only public if it contains a `Page.php` or `Index.php`. Colocate other files (components, tests) safely.
- **Private Folders**: Prefix folders with `_` (e.g., `_components`) to opt them out of routing.
- **Route Groups**: Wrap folders in `()` (e.g., `(marketing)`) to organize routes without affecting the URL.
- **Dynamic Routes**: Full support for `[slug]` and catch-all `[...slug]` segments.
- **Special Files**: Built-in support for `Layout.php`, `Template.php`, `Error.php`, `NotFound.php`, `Loading.php`, and `Action.php` at any level.
- **Hierarchical Layouts & Middleware**: Layouts and Middleware nest automatically following the folder structure.
- **Server Actions**: Define server-side logic in `Action.php` to handle form submissions directly in your route folder.

### Component-Centric UI

- **Pages as Components**: Forget controllers. A page is just a PHP class extending `Component`.
- **JSX-style PHP**: Use the `h()` helper for a declarative way to build HTML.
- **Functional Hooks**:
  - `useRouter()`: Access routing data and navigation.
  - `useHead()`: Declarative metadata management.
  - `useState()`: Persisted state across requests.
  - `useForm()`: Simplified form handling and validation.
  - `useSession()`: Easy session management.
  - `useCache()`: Access to the built-in caching layer.
- **Nested Layouts**: Intuitively wrap pages with layouts at any level.

### Performance & DX

- **Rapo-Live**: Livewire-style server-side reactivity for interactive components.
- **Data Fetching**: Built-in `getServerSideProps` for pre-rendering data.
- **ISR & Caching**: Incremental Static Regeneration support for blazing fast load times via the built-in `Cache` service.
- **Modern CLI**: Scaffolding for pages, components, models, middleware, and server actions.
- **Image Optimization**: Built-in `Image` component for lazy-loading and optimization.
- **Mail Engine**: Professional email handling using **Symfony Mailer**, powered by Rapo Components.
- **Context Debugger**: A built-in floating badge in debug mode to inspect component props and route hierarchy.
- **Quality Tools**: Built-in `dd()` and `dump()` helpers via **Symfony Var-Dumper**.

### Modern Data & Auth

- **Active Record ORM**: A fluent way to interact with your database.
- **Automatic Migrations**: Define your schema in models and sync effortlessly.
- **Auth Scaffolding**: Get a full authentication system running in seconds.
- **Storage Abstraction**: Simplified file management with `storage()` helper and `useStorage()` hook.
- **Background Queues**: Handle heavy tasks out-of-process with the SQLite-based `queue()` system.
- **Attribute-based Validation**: Cleanly validate requests using PHP 8 Attributes: `#[Validate(['email' => 'required|email'])]`.

## Rapo-Only Unique Features

### Hierarchical i18n

Translations that follow your folder structure. Place `translation.php` in any route folder, and use the `useTranslation()` hook. It automatically merges translations from the current page up to the root.

### Automatic Page-to-API Mirroring

Every page is an API. Request any page with `Accept: application/json` and RapoPHP will return the `props` and `getServerSideProps` data as JSON instead of HTML.

### Markdown-as-Routes

Create docs effortlessly. Create `Page.md` in any route folder, and RapoPHP will automatically parse and render it.

## Installation

The recommended way to install RapoPHP is via [Composer](https://getcomposer.org/):

```bash
composer require rapo/framework
php vendor/bin/rapo project:init
```

## CLI Commands

After initializing your project, you can use the Rapo CLI:

```bash
# Start the development server
php vendor/bin/rapo serve

# Create a dynamic page: src/Pages/blog/[slug]/Page.php
php vendor/bin/rapo make:page blog/[slug]

# Create a new component
php vendor/bin/rapo make:component Navbar

# Create an API route
php vendor/bin/rapo make:api-route status

# Create a Server Action
php vendor/bin/rapo make:action contact

# Run migrations
php vendor/bin/rapo migrate

# Start queue worker
php vendor/bin/rapo queue:work
```

## Advanced Features

### Server Actions & Validation

Handle forms with zero-config using `Action.php` and declarative validation:

```php
namespace App\Pages\contact;

use Rapo\Http\Attributes\Validate;

class Action {
    #[Validate(['email' => 'required|email', 'msg' => 'required'])]
    public function handle($request) {
        // Logic here...
        return redirect('/thanks');
    }
}
```

### Background Jobs

Keep your app fast by offloading heavy tasks:

```php
// Dispatch to queue
queue(SendEmailJob::class, ['to' => 'user@example.com']);
```

### Storage

Easily manage files across different environments:

```php
storage()->put('uploads/photo.jpg', $binaryData);
$url = storage()->url('uploads/photo.jpg');
```

---

Built with ❤️ for PHP developers who love modern workflows.
