<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

final class PermissionCategoryResolver
{
    /**
     * Return permission names for a role based on config/permission_categories.php.
     *
     * @return list<string>
     */
    public function getPermissionsForRole(string $roleName): array
    {
        $rolesConfig = config('permission_categories.roles', []);
        $roleConfig = $rolesConfig[$roleName] ?? null;

        if ($roleConfig === null) {
            return [];
        }

        $strategy = $roleConfig['strategy'] ?? 'explicit';

        if ($strategy === 'bypass') {
            return $this->resolveExplicit($roleConfig['explicit'] ?? []);
        }

        if ($strategy === 'explicit') {
            return $this->resolveExplicit($roleConfig['explicit'] ?? []);
        }

        if ($strategy === 'categories') {
            $categoryKeys = $roleConfig['categories'] ?? [];
            $explicit = $roleConfig['explicit'] ?? [];
            $exclude = $roleConfig['exclude'] ?? [];

            $fromCategories = $this->getPermissionsFromCategories($categoryKeys);
            $explicitList = $this->resolveExplicit($explicit);
            $merged = array_values(array_unique(array_merge($fromCategories, $explicitList)));

            return array_values(array_diff($merged, $exclude));
        }

        return [];
    }

    /**
     * @param  list<string>  $categoryKeys
     * @return list<string>
     */
    private function getPermissionsFromCategories(array $categoryKeys): array
    {
        $categories = config('permission_categories.categories', []);
        $allPermissionNames = Permission::query()->pluck('name');
        $result = [];

        foreach ($categoryKeys as $key) {
            $category = $categories[$key] ?? null;
            if ($category === null) {
                continue;
            }
            $patterns = $category['patterns'] ?? [];
            $exclude = $category['exclude'] ?? [];
            $matched = $this->matchPatterns($allPermissionNames, $patterns);
            foreach ($matched as $name) {
                if (! in_array($name, $exclude, true)) {
                    $result[] = $name;
                }
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * @param  Collection<int, string>  $permissionNames
     * @param  list<string>  $patterns
     * @return list<string>
     */
    private function matchPatterns(Collection $permissionNames, array $patterns): array
    {
        $result = [];
        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $regex = '/^'.str_replace('\*', '.*', preg_quote($pattern, '/')).'$/';
                foreach ($permissionNames as $name) {
                    if (preg_match($regex, $name) === 1) {
                        $result[] = $name;
                    }
                }
            } else {
                if ($permissionNames->contains($pattern)) {
                    $result[] = $pattern;
                }
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    private function resolveExplicit(array $names): array
    {
        if ($names === []) {
            return [];
        }
        $existing = Permission::query()->whereIn('name', $names)->pluck('name')->all();

        return array_values($existing);
    }
}
