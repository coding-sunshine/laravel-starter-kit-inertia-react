# CreateListingVersionAction

## Purpose

Snapshots the current state of a Lot or Project and persists it as a ListingVersion record for audit/rollback purposes.

## Location

`app/Actions/CreateListingVersionAction.php`

## Usage

```php
$version = (new CreateListingVersionAction())->handle($lot, 'Price updated');
```
