# SuggestPushTimeAction

## Purpose

Uses Prism AI to suggest the optimal day and time to publish a listing on a given channel. Falls back to next Tuesday 10am if AI fails.

## Location

`app/Actions/SuggestPushTimeAction.php`

## Usage

```php
$result = (new SuggestPushTimeAction())->handle($lot, 'rea');
// ['suggested_at' => Carbon, 'reason' => string]
```
