<?php

declare(strict_types=1);

/**
 * Rake weighment Excel templates (static files under public/).
 *
 * @see App\Services\RakeWeighmentExcelTemplateResolver
 */
return [
    'excel_templates_directory' => 'rake_weighment_excel_template',

    'excel_templates' => [
        'pakur' => [
            'relative_path' => 'weighment_template_structured.xlsx',
            'siding_codes' => ['PKUR'],
        ],
        'dumka_kurwa' => [
            'relative_path' => 'weighment_template_dmgk_bmgk.xlsx',
            'siding_codes' => ['DUMK', 'KURWA'],
        ],
    ],
];
