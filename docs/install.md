Requirements and installation
=============================

Для установки через composer тебуется указать в `composer.json` вашего проекта

```json
{
    "require": {
        "bzick/aspect": "0.9.*"
    },
    "repositories": [
    {
        "type": "git",
        "url": "https://github.com/bzick/aspect.git"
    }]
}
```

Для работы шаблонизатора потребуется расширение tokenizer. Для загрузки классов используется `psr-0` формат.