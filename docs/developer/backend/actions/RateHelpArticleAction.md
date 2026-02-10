# RateHelpArticleAction

## Purpose

Increments the helpful or not-helpful counter on a help article based on user feedback.

## Location

`app/Actions/RateHelpArticleAction.php`

## Method Signature

```php
public function handle(HelpArticle $article, bool $isHelpful): void
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$article` | `HelpArticle` | The help article being rated |
| `$isHelpful` | `bool` | `true` to increment `helpful_count`, `false` to increment `not_helpful_count` |

## Return Value

None (`void`). The model is updated in place via `increment()`.

## Usage Examples

### From Controller

```php
app(RateHelpArticleAction::class)->handle($helpArticle, (bool) $validated['is_helpful']);
```

## Related Components

- **Controller**: `RateHelpArticleController` (invokable)
- **Routes**: `help.rate` (POST `help/{helpArticle}/rate`)
- **Model**: `HelpArticle`
