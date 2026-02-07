<?php

declare(strict_types=1);

use App\Features\ExampleFeature;

return [
    /*
     * Feature classes to resolve and expose to the Inertia frontend as shared props.
     * Keys become the feature name in the `features` object (e.g. ExampleFeature -> example_feature).
     */
    'inertia_features' => [
        'example' => ExampleFeature::class,
    ],
];
