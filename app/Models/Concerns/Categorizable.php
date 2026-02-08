<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait Categorizable
{
    /**
     * Categories (many-to-many polymorphic).
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->morphToMany(Category::class, 'categoryable', 'categoryables');
    }

    public function attachCategory(Category $category): void
    {
        $this->categories()->syncWithoutDetaching([$category->getKey()]);
    }

    public function detachCategory(Category $category): void
    {
        $this->categories()->detach($category->getKey());
    }

    /**
     * @param  array<int, Category>|Collection<int, Category>  $categories
     */
    public function syncCategories(array|Collection $categories): void
    {
        $ids = collect($categories)->map(fn (Category $c) => $c->getKey())->all();

        $this->categories()->sync($ids);
    }

    public function hasCategory(Category|array|Collection $categoryOrCategories): bool
    {
        $ids = collect(is_array($categoryOrCategories) || $categoryOrCategories instanceof Collection
            ? $categoryOrCategories
            : [$categoryOrCategories])
            ->map(fn (Category $c) => $c->getKey())
            ->all();

        return $this->categories()->whereIn('categories.id', $ids)->exists();
    }

    /**
     * @return array<int, string> category id => name
     */
    public function categoriesList(): array
    {
        return $this->categories()->pluck('name', 'id')->all();
    }

    /**
     * @return array<int, int>
     */
    public function categoriesIds(): array
    {
        return $this->categories()->pluck('categories.id')->all();
    }
}
