Template Providers
=================

Template providers allow storing templates not only on the filesystem, but in any storage system you want — for example, a database like MySQL.

To use a template from a specific provider, use the schema prefix in the template name: `db:page/about.tpl`. The template will be loaded from the provider registered with the `db` schema.

## Registering a Provider

Use the `addProvider()` method to register a template provider:

```php
// Basic usage - templates from provider, compiled to default compile directory
$fenom->addProvider("db", $db_provider);

// With custom compile directory - compiled templates stored separately
$fenom->addProvider("cms", $cmsProvider, '/path/to/cms/cache');
```

Parameters:
* `$scm` — schema name used in template references (e.g., `db:template.tpl`)
* `$provider` — object implementing `Fenom\ProviderInterface`
* `$compile_path` (optional) — separate directory for compiled templates from this provider

## Using Provider Templates

Once registered, use the schema prefix to access templates from the provider:

```php
// Display template from the 'db' provider
$fenom->display('db:page/about.tpl', $vars);

// Fetch template from the 'cms' provider
$result = $fenom->fetch('cms:article.tpl', $vars);
```

## Creating a Custom Provider

Create a class that implements `Fenom\ProviderInterface`:

```php
use Fenom\ProviderInterface;

class DatabaseProvider implements ProviderInterface {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function templateExists(string $template): bool {
        $stmt = $this->pdo->prepare('SELECT 1 FROM templates WHERE name = ?');
        return $stmt->execute([$template]) && $stmt->fetch();
    }

    public function getSource(string $template, float &$time): string {
        $stmt = $this->pdo->prepare('SELECT content, updated_at FROM templates WHERE name = ?');
        $stmt->execute([$template]);
        $row = $stmt->fetch();
        $time = strtotime($row['updated_at']);
        return $row['content'];
    }

    public function getLastModified(string $template): float {
        $stmt = $this->pdo->prepare('SELECT updated_at FROM templates WHERE name = ?');
        $stmt->execute([$template]);
        $row = $stmt->fetch();
        return strtotime($row['updated_at']);
    }

    public function verify(array $templates): bool {
        // Check if all templates are still valid
        return true;
    }

    public function getList(): iterable {
        // Return all template names
        return [];
    }
}
```

## Multiple Compile Directories

When using multiple providers, you can specify separate compile directories for each:

```php
$fenom->setCompileDir('/var/cache/fenom/default');
$fenom->addProvider('theme1', $theme1Provider, '/var/cache/fenom/theme1');
$fenom->addProvider('theme2', $theme2Provider, '/var/cache/fenom/theme2');
```

Each provider's templates will be compiled to its specified directory, keeping caches isolated.
