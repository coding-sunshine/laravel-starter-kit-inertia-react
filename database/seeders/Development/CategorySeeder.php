<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Category;
use Illuminate\Database\Seeder;

final class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds (idempotent).
     */
    public function run(): void
    {
        if (Category::query()->exists()) {
            return;
        }

        $default = Category::create(['name' => 'Default', 'type' => 'default']);
        Category::create(['name' => 'Development', 'type' => 'default']);
        Category::create(['name' => 'Support', 'type' => 'default']);
        $sub = new Category(['name' => 'Subcategory', 'type' => 'default']);
        $default->appendNode($sub);
    }
}
