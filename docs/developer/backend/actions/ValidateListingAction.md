# ValidateListingAction

## Purpose

Validates a Lot or Project listing for completeness and correctness. Returns errors (blocking) and warnings (advisory).

## Location

`app/Actions/ValidateListingAction.php`

## Usage

```php
$result = (new ValidateListingAction())->handle($lot);
// ['valid' => bool, 'errors' => [], 'warnings' => []]
```
