Провайдеры шаблонов
====================

Провайдеры шаблонов позволяют хранить шаблоны не только в файловой системе, но и в любом хранилище — например, в базе данных MySQL.

Для использования шаблона из определённого провайдера используется префикс схемы в имени шаблона: `db:page/about.tpl`. Шаблон будет загружен из провайдера, зарегистрированного со схемой `db`.

## Регистрация провайдера

Используйте метод `addProvider()` для регистрации провайдера шаблонов:

```php
// Базовое использование - шаблоны из провайдера, компиляция в директорию по умолчанию
$fenom->addProvider("db", $db_provider);

// С отдельной директорией компиляции
$fenom->addProvider("cms", $cmsProvider, '/path/to/cms/cache');
```

Параметры:
* `$scm` — имя схемы для обращения к шаблонам (например, `db:template.tpl`)
* `$provider` — объект, реализующий `Fenom\ProviderInterface`
* `$compile_path` (опционально) — отдельная директория для скомпилированных шаблонов этого провайдера

## Использование шаблонов провайдера

После регистрации используйте префикс схемы для доступа к шаблонам:

```php
// Отобразить шаблон из провайдера 'db'
$fenom->display('db:page/about.tpl', $vars);

// Получить шаблон из провайдера 'cms'
$result = $fenom->fetch('cms:article.tpl', $vars);
```

## Создание своего провайдера

Создайте класс, реализующий `Fenom\ProviderInterface`:

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
        // Проверка актуальности всех шаблонов
        return true;
    }

    public function getList(): iterable {
        // Вернуть список всех имён шаблонов
        return [];
    }
}
```

## Несколько директорий компиляции

При использовании нескольких провайдеров можно указать отдельные директории компиляции:

```php
$fenom->setCompileDir('/var/cache/fenom/default');
$fenom->addProvider('theme1', $theme1Provider, '/var/cache/fenom/theme1');
$fenom->addProvider('theme2', $theme2Provider, '/var/cache/fenom/theme2');
```

Шаблоны каждого провайдера будут компилироваться в свою директорию.
