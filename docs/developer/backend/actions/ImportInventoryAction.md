# ImportInventoryAction

## Purpose

Imports an array of property rows (from JSON or CSV) into the lots or projects tables. Supports upsert via legacy external_id and dry-run mode.

## Location

`app/Actions/ImportInventoryAction.php`

## Usage

```php
$result = (new ImportInventoryAction())->handle($rows, 'lots', $orgId, dryRun: false);
// ['imported' => int, 'updated' => int, 'errors' => []]
```
