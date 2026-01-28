<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;

final readonly class DocumentationPromptGenerator
{
    /**
     * Generate AI prompt for Action documentation.
     *
     * @param  array<string, mixed>  $actionInfo
     * @param  array<string, mixed>  $relationships
     */
    public function generateActionPrompt(
        array $actionInfo,
        array $relationships,
        string $templatePath
    ): string {
        $template = File::get($templatePath);
        $codeContent = isset($actionInfo['filePath']) ? File::get($actionInfo['filePath']) : '';

        $prompt = "Generate comprehensive documentation for the following Action class.\n\n";
        $prompt .= "## Code Context\n\n";
        $prompt .= "```php\n{$codeContent}\n```\n\n";

        // Add PHPDoc if available
        if (isset($actionInfo['phpDoc']['class']['parsed']['description'])) {
            $prompt .= "## Existing Documentation\n\n";
            $prompt .= $actionInfo['phpDoc']['class']['parsed']['description']."\n\n";
        }

        // Add method signature
        if (isset($actionInfo['handleMethod'])) {
            $method = $actionInfo['handleMethod'];
            $prompt .= "## Method Signature\n\n";
            $prompt .= "Method: `{$method['name']}`\n";
            $prompt .= 'Return Type: `'.($method['returnType'] ?? 'mixed')."`\n\n";

            if (! empty($method['parameters'])) {
                $prompt .= "Parameters:\n";
                foreach ($method['parameters'] as $param) {
                    $prompt .= "- `{$param['name']}`: `".($param['type'] ?? 'mixed').'`';
                    if (isset($param['description'])) {
                        $prompt .= " - {$param['description']}";
                    }
                    $prompt .= "\n";
                }
                $prompt .= "\n";
            }
        }

        // Add dependencies
        if (! empty($actionInfo['dependencies'])) {
            $prompt .= "## Dependencies\n\n";
            foreach ($actionInfo['dependencies'] as $dep) {
                $prompt .= "- `{$dep['type']}`: `{$dep['name']}`\n";
            }
            $prompt .= "\n";
        }

        // Add relationships
        if (! empty($relationships)) {
            $prompt .= "## Relationships\n\n";
            if (! empty($relationships['usedBy'])) {
                $prompt .= 'Used by Controllers: '.implode(', ', $relationships['usedBy'])."\n";
            }
            if (! empty($relationships['usesModels'])) {
                $prompt .= 'Uses Models: '.implode(', ', $relationships['usesModels'])."\n";
            }
            if (! empty($relationships['relatedRoutes'])) {
                $prompt .= 'Related Routes: '.implode(', ', $relationships['relatedRoutes'])."\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "## Template Structure\n\n";
        $prompt .= "```markdown\n{$template}\n```\n\n";

        $prompt .= 'Generate complete documentation following the template structure. ';
        $prompt .= 'Fill in all placeholders with accurate information from the code context. ';
        $prompt .= 'Include usage examples, parameter descriptions, and related component links.';

        return $prompt;
    }

    /**
     * Generate AI prompt for Controller documentation.
     *
     * @param  array<string, mixed>  $controllerInfo
     * @param  array<string, mixed>  $relationships
     */
    public function generateControllerPrompt(
        array $controllerInfo,
        array $relationships,
        string $templatePath
    ): string {
        $template = File::get($templatePath);
        $codeContent = isset($controllerInfo['filePath']) ? File::get($controllerInfo['filePath']) : '';

        $prompt = "Generate comprehensive documentation for the following Controller class.\n\n";
        $prompt .= "## Code Context\n\n";
        $prompt .= "```php\n{$codeContent}\n```\n\n";

        // Add methods
        if (! empty($controllerInfo['methods'])) {
            $prompt .= "## Methods\n\n";
            foreach ($controllerInfo['methods'] as $methodName => $method) {
                $prompt .= "### {$methodName}\n\n";
                $prompt .= 'Return Type: `'.($method['returnType'] ?? 'mixed')."`\n";
                if (! empty($method['parameters'])) {
                    $prompt .= 'Parameters: '.count($method['parameters'])."\n";
                }
                $prompt .= "\n";
            }
        }

        // Add relationships
        if (! empty($relationships)) {
            $prompt .= "## Relationships\n\n";
            if (! empty($relationships['usesActions'])) {
                $prompt .= 'Uses Actions: '.implode(', ', $relationships['usesActions'])."\n";
            }
            if (! empty($relationships['usesFormRequests'])) {
                $prompt .= 'Uses Form Requests: '.implode(', ', $relationships['usesFormRequests'])."\n";
            }
            if (! empty($relationships['relatedRoutes'])) {
                $prompt .= 'Routes: '.implode(', ', $relationships['relatedRoutes'])."\n";
            }
            if (! empty($relationships['rendersPages'])) {
                $prompt .= 'Renders Pages: '.implode(', ', $relationships['rendersPages'])."\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "## Template Structure\n\n";
        $prompt .= "```markdown\n{$template}\n```\n\n";

        $prompt .= 'Generate complete documentation following the template structure.';

        return $prompt;
    }

    /**
     * Generate AI prompt for Page documentation.
     *
     * @param  array<string, mixed>  $pageInfo
     * @param  array<string, mixed>  $relationships
     */
    public function generatePagePrompt(
        array $pageInfo,
        array $relationships,
        string $templatePath
    ): string {
        $template = File::get($templatePath);
        $codeContent = isset($pageInfo['filePath']) ? File::get($pageInfo['filePath']) : '';

        $prompt = "Generate comprehensive documentation for the following React/Inertia page component.\n\n";
        $prompt .= "## Code Context\n\n";
        $prompt .= "```tsx\n{$codeContent}\n```\n\n";

        // Add props
        if (! empty($pageInfo['tsDoc']['props'])) {
            $prompt .= "## Props\n\n";
            foreach ($pageInfo['tsDoc']['props'] as $prop) {
                $prompt .= "- `{$prop['name']}`: `{$prop['type']}`";
                if ($prop['optional'] ?? false) {
                    $prompt .= ' (optional)';
                }
                $prompt .= "\n";
            }
            $prompt .= "\n";
        }

        // Add relationships
        if (! empty($relationships)) {
            $prompt .= "## Relationships\n\n";
            if (! empty($relationships['renderedBy'])) {
                $prompt .= 'Rendered by Controllers: '.implode(', ', $relationships['renderedBy'])."\n";
            }
            if (! empty($relationships['relatedRoutes'])) {
                $prompt .= 'Routes: '.implode(', ', $relationships['relatedRoutes'])."\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "## Template Structure\n\n";
        $prompt .= "```markdown\n{$template}\n```\n\n";

        $prompt .= 'Generate complete documentation following the template structure.';

        return $prompt;
    }
}
