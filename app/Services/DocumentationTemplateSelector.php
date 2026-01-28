<?php

declare(strict_types=1);

namespace App\Services;

final readonly class DocumentationTemplateSelector
{
    /**
     * Select appropriate template for an Action.
     *
     * @param  array<string, mixed>  $actionInfo
     */
    public function selectActionTemplate(array $actionInfo): string
    {
        $complexity = $this->calculateActionComplexity($actionInfo);

        // Simple template for basic actions
        if ($complexity < 3) {
            return 'action-simple';
        }

        // Detailed template for complex actions
        if ($complexity >= 5) {
            return 'action-detailed';
        }

        // Standard template
        return 'action';
    }

    /**
     * Select appropriate template for a Controller.
     *
     * @param  array<string, mixed>  $controllerInfo
     */
    public function selectControllerTemplate(array $controllerInfo): string
    {
        $methodCount = count($controllerInfo['methods'] ?? []);
        $routeCount = count($controllerInfo['relationships']['relatedRoutes'] ?? []);

        // API template for controllers with many routes
        if ($routeCount >= 5 || $methodCount >= 5) {
            return 'controller-api';
        }

        // Standard template
        return 'controller';
    }

    /**
     * Select appropriate template for a Page.
     *
     * @param  array<string, mixed>  $pageInfo
     */
    public function selectPageTemplate(array $pageInfo): string
    {
        $propsCount = count($pageInfo['tsDoc']['props'] ?? []);

        // Detailed template for pages with many props
        if ($propsCount >= 5) {
            return 'page-detailed';
        }

        // Standard template
        return 'page';
    }

    /**
     * Get template path for a template name.
     */
    public function getTemplatePath(string $templateName): string
    {
        $basePath = base_path('docs/.templates');

        // Check if specific template exists
        $specificPath = "{$basePath}/{$templateName}.md";
        if (file_exists($specificPath)) {
            return $specificPath;
        }

        // Fallback to standard template
        $standardPath = "{$basePath}/{$templateName}.md";
        if (file_exists($standardPath)) {
            return $standardPath;
        }

        // Default fallback
        return "{$basePath}/action.md";
    }

    /**
     * Calculate complexity score for an Action.
     */
    private function calculateActionComplexity(array $actionInfo): int
    {
        $complexity = 0;

        // Count dependencies
        $dependencyCount = count($actionInfo['dependencies'] ?? []);
        $complexity += $dependencyCount;

        // Count parameters in handle method
        $paramCount = count($actionInfo['handleMethod']['parameters'] ?? []);
        $complexity += $paramCount;

        // Check for relationships
        $relationships = $actionInfo['relationships'] ?? [];
        if (! empty($relationships['usedBy'])) {
            $complexity += 1;
        }
        if (! empty($relationships['usesModels'])) {
            $complexity += 1;
        }

        // Check for PHPDoc complexity
        if (isset($actionInfo['phpDoc']['class']['parsed']['throws'])) {
            $complexity += count($actionInfo['phpDoc']['class']['parsed']['throws']);
        }

        return $complexity;
    }
}
