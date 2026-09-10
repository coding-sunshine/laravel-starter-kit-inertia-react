<?php

declare(strict_types=1);

/**
 * Fix jijunair/laravel-referral PHP 8.4 deprecation: implicit nullable parameter.
 * Run via: composer run fix-referral-php84
 */
$file = __DIR__.'/../vendor/jijunair/laravel-referral/src/Traits/Referrable.php';

if (! file_exists($file)) {
    return;
}

$content = file_get_contents($file);
$fixed = str_replace('int $referrerID = NULL', '?int $referrerID = null', $content);

if ($fixed !== $content) {
    file_put_contents($file, $fixed);
    echo "Fixed PHP 8.4 nullable deprecation in jijunair/laravel-referral\n";
}
