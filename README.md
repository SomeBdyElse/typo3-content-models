# TYPO3 Content Models

TYPO3 Content Models generates typed PHP content model classes from TYPO3's TCA schema. They can be used to render frontend content. Instead of passing raw `TYPO3\CMS\Core\Domain\Record` objects through templates and processors, records can be converted into classes with typed getters, enum-backed select fields, typed relation collections, file collections, links, and date values. This makes sense if you want to pre-process data before passing it to the templates, resolve links and hydrate your own view models.

## What it does

### Generator
- Generates content model classes for TCA-managed tables and record types.
- Provides eager and lazy DTO-style generators.
- Generates backed enums for valid static `selectSingle` fields

### Renderer
- Registers generated or custom models through the `#[ContentModel]` PHP attribute.
- Converts frontend content records to the matching model during rendering.
- Falls back to `GenericContentModel` when no matching model class exists.
- Wraps relations in `LazyContentModelCollection`, so related records can be accessed as content models too.

## Requirements

- TYPO3 `^13.4` or `^14.0`
- Composer mode installation

## Installation

```bash
composer require somebdyelse/typo3-content-models
```

## Generating models

Create a project configuration file at:

```text
config/system/content_models.yaml
```

It will extend the default configuration stored at `EXT:typo3-content-models/typo3-content-models-demo/cms/packages/content_models/Resources/Private/Configuration/default_configuration.yaml`

Example:

```yaml
$schema: ../../vendor/somebdyelse/typo3-content-models/Resources/Private/Configuration/configuration.schema.yaml
targetPhpNamespace: Vendor\Sitepackage\ContentModels
targetDirectory: EXT:sitepackage/Classes/ContentModels
overrides:
  generator: SomeBdyElse\Typo3ContentModels\Generation\Generators\LazyDtoGenerator

  # Whitelist the tables that we would like to have models for
  generate: false
  tables:
    pages:
      generate: true
    tt_content:
      generate: true
    sys_category:
      generate: true
```

`targetPhpNamespace` defines the root PHP namespace used in the generated class declarations. `targetDirectory` defines the TYPO3 `EXT:` path where those PHP files are written. Both values should point to the same logical location in your site package.

Run the generator:

```bash
vendor/bin/typo3 content-models:generate_models
```

The command reads TYPO3's TCA schema, applies the configuration, and writes PHP classes to `targetDirectory` with namespaces below `targetPhpNamespace`.

Generated classes must be covered by Symfony service discovery with autoconfiguration enabled so the `#[ContentModel]` attribute can be turned into the internal `content_models.content_model` service tag during container compilation. The compiler pass only uses those tagged definitions to build the content model registry and removes the model service definitions afterwards. Content model instances are created through `fromRecord()`, not shared as Symfony services.

When using the default target inside this `content_models` extension, this is already handled by the extension's service configuration. If you generate models into a site package or another extension, make sure that target namespace is covered by a service resource with autoconfiguration enabled:

```yaml
services:
  Vendor\Sitepackage\:
    resource: '../Classes/*'
    autowire: true
    autoconfigure: true
```

After generating models, flush/warm TYPO3's caches so the container picks up new or changed classes.

## Using generated models

In a `PAGEVIEW` context, use `page-model` to convert the current page record to a content model. Use `content-models` after TYPO3's `page-content` processor to replace fetched frontend content records with content model instances:

```typoscript
page = PAGE
page {
  10 = PAGEVIEW
  10 {
    paths {
      100 = EXT:sitepackage/Resources/Private/PageView/
    }

    dataProcessing {
      10 = page-model
      20 = page-content
      30 = content-models
    }
  }
}
```

`pageModel` contains the content model for the current page. `content` contains TYPO3's content areas with their records converted to content models.

Both processors have `source` and `as` arguments with defaults:

| Processor | Default `source` | Default `as` |
| --- | --- | --- |
| `page-model` | `page` | `pageModel` |
| `content-models` | `content` | same as `source` |

Use `source` and `as` if your PAGEVIEW setup uses different variable names:

```typoscript
dataProcessing {
  10 = page-model
  10.source = page
  10.as = currentPage

  20 = page-content
  20.as = rawContent

  30 = content-models
  30.source = rawContent
  30.as = pageContent
}
```

You can add your own data processors after `dataProcessing.30` to convert the database based content models further to view models.

## Configuration

Configuration is loaded from the extension default file and then overlaid by:

```text
config/system/content_models.yaml
```

Supported top-level options:

| Option | Required | Description |
| --- | --- | --- |
| `targetPhpNamespace` | yes | Root PHP namespace for generated models. Each table gets a namespace segment below this root. |
| `targetDirectory` | yes | `EXT:` path where generated PHP files are written. Each table gets a subdirectory below this path. |
| `commonCodeGenerator` | no | Class name implementing `CommonCodeGeneratorInterface`, used for additional code generated after all models. |
| `overrides` | no | Global, table, type, and field-level generation options. |

### Overrides

Overrides are merged from broad to specific:

1. `overrides`
2. `overrides.tables.<table>`
3. `overrides.tables.<table>.types.<type>`

Available model override options:

| Option | Description |
| --- | --- |
| `generate` | Enables or disables model generation. Defaults to `true` unless changed by configuration. |
| `className` | Overrides the generated class name for the current table or type. |
| `generator` | Generator class implementing `ModelGeneratorInterface`. |
| `fields` | Field-specific generation hints. |

Example:

```yaml
targetPhpNamespace: Vendor\Sitepackage\ContentModel
targetDirectory: EXT:sitepackage/Classes/ContentModel
overrides:
  generator: SomeBdyElse\Typo3ContentModels\Generation\Generators\EagerDtoGenerator
  generate: false
  tables:
    pages:
      generate: true
      types:
        1:
          className: DefaultPage
        3:
          className: ShortcutPage
          generate: false
    tt_content:
      generate: true
      generator: SomeBdyElse\Typo3ContentModels\Generation\Generators\LazyDtoGenerator
```

### Relation target types

Relation fields can be narrowed when a relation points to a table with multiple record types. This improves the PHPDoc type of `LazyContentModelCollection` and runtime validation of related model instances.

```yaml
overrides:
  tables:
    tt_content:
      types:
        menu_pages:
          fields:
            pages:
              relationTargetTypes:
                pages: [1]
```

Without this hint, the relation generator uses all known target table subtypes. If no model can be resolved for a target, the relation falls back to `TYPO3\CMS\Core\Domain\Record`.

## Built-in generators

### `EagerDtoGenerator`

Creates constructor-promoted public readonly properties. Field values are read from the `Record` once in `fromRecord()`.

Use this when you want simple immutable data transfer objects and prefer all field conversion to happen immediately.

### `LazyDtoGenerator`

Stores the original `Record` and creates typed getters. Field values are read when the getter is called.

Use this when you want to avoid resolving every field and relation up front. This is often the better default for records with expensive relation or file fields.

## Generated field types

The built-in field generation handles common TYPO3 field types:

| TYPO3 field | Generated PHP type |
| --- | --- |
| File fields | `TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection` |
| Link fields | `TYPO3\CMS\Core\LinkHandling\TypolinkParameter` |
| Date/time fields | `?\DateTimeImmutable` |
| Relation fields | `LazyContentModelCollection<T>` |
| Static `selectSingle` fields with valid scalar items | PHP backed enum |
| Other static select fields | `array` |
| Folder, FlexForm, and JSON fields | `TYPO3\CMS\Core\Domain\RecordPropertyClosure` |
| Fallback fields | Database-derived scalar type where possible, otherwise `mixed` |

System fields and fields with TCA type `none` are skipped.

## Extending model generation

Create a generator that implements:

```php
SomeBdyElse\Typo3ContentModels\Generation\ModelGeneratorInterface
```

Example:

```php
namespace Vendor\Sitepackage\ContentModelGeneration;

use SomeBdyElse\Typo3ContentModels\Generation\GeneratedModel;
use SomeBdyElse\Typo3ContentModels\Generation\ModelGeneratorInterface;

final readonly class MyModelGenerator implements ModelGeneratorInterface
{
    public function generateModel(string $table, ?string $type): GeneratedModel
    {
        // Generate one PHP class for the given table/type and return metadata
        // about the generated class.
        throw new \LogicException('Implement model generation for your project.');
    }
}
```

Register the class as a service in your extension and make it public, because the generator factory resolves configured generators by class name:

```yaml
services:
  Vendor\Sitepackage\ContentModelGeneration\MyModelGenerator:
    autowire: true
    public: true
```

Then select it globally, per table, or per record type:

```yaml
overrides:
  generator: Vendor\Sitepackage\ContentModelGeneration\MyModelGenerator
  tables:
    tt_content:
      types:
        text:
          generator: Vendor\Sitepackage\ContentModelGeneration\TextContentGenerator
```

A generator can reuse the extension services used by the built-in generators:

- `SomeBdyElse\Typo3ContentModels\Generation\Configuration\Configuration`
- `SomeBdyElse\Typo3ContentModels\Generation\NamingHelper`
- `SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\FieldGenerator`
- `TYPO3\CMS\Core\Schema\TcaSchemaFactory`

Generated classes should usually:

- implement `ContentModelInterface`
- define `#[ContentModel(table: ..., type: ...)]`
- provide `public static function fromRecord(Record $record): self`

The default `ContentModelFactory` relies on `fromRecord()` when it converts TYPO3 records to models.

## Generating shared code

If you need to generate files after all models are known, implement:

```php
SomeBdyElse\Typo3ContentModels\Generation\CommonCodeGeneratorInterface
```

Example:

```php
namespace Vendor\Sitepackage\ContentModelGeneration;

use SomeBdyElse\Typo3ContentModels\Generation\CommonCodeGeneratorInterface;
use SomeBdyElse\Typo3ContentModels\Generation\GeneratedModel;

final readonly class MyCommonCodeGenerator implements CommonCodeGeneratorInterface
{
    /**
     * @param list<GeneratedModel> $generatedModels
     */
    public function generateCommonCode(array $generatedModels = []): void
    {
        // Generate registries, union types, helper classes, documentation, etc.
    }
}
```

Configure it at the top level:

```yaml
commonCodeGenerator: Vendor\Sitepackage\ContentModelGeneration\MyCommonCodeGenerator
```

## Custom content models without generation

You can also create a model manually and let the runtime registry pick it up:

```php
namespace Vendor\Sitepackage\ContentModel\Content;

use SomeBdyElse\Typo3ContentModels\Contract\ContentModel;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use TYPO3\CMS\Core\Domain\Record;

#[ContentModel(table: 'tt_content', type: 'my_custom_element')]
final readonly class MyCustomElement implements ContentModelInterface
{
    public function __construct(
        public int $uid,
        public string $headline,
    ) {
    }

    public static function fromRecord(Record $record): self
    {
        return new self(
            uid: $record->get('uid'),
            headline: (string)$record->get('header'),
        );
    }
}
```

Make sure the class is covered by Symfony service discovery with autoconfiguration enabled, or add the `content_models.content_model` tag manually. The extension's compiler pass adds every tagged content model class to the runtime registry, then removes the content model service definition so model instances are created through `fromRecord()` instead of being shared container services.

## Development

Run static analysis from the extension directory:

```bash
vendor/bin/phpstan analyse -c Build/phpstan/phpstan.neon
```

When working in the demo project, run the command from the TYPO3 project root or through the configured Docker container so TYPO3 can load the project configuration and TCA.
