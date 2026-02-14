<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $template = config('tenancy.default_organization_name', "{name}'s Workspace");
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $name = str_replace('{name}', $user->name, $template);
            $slug = Str::slug($name);
            $baseSlug = $slug;
            $counter = 0;
            while (DB::table('organizations')->where('slug', $slug)->exists()) {
                $counter++;
                $slug = $baseSlug.'-'.$counter;
            }

            $orgId = DB::table('organizations')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'settings' => null,
                'owner_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('organization_user')->insert([
                'organization_id' => $orgId,
                'user_id' => $user->id,
                'is_default' => true,
                'joined_at' => now(),
                'invited_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('posts')->where('author_id', $user->id)->update(['organization_id' => $orgId]);
            DB::table('help_articles')->where('created_by', $user->id)->update(['organization_id' => $orgId]);
            DB::table('changelog_entries')->where('created_by', $user->id)->update(['organization_id' => $orgId]);
        }
    }

    public function down(): void
    {
        DB::table('posts')->update(['organization_id' => null]);
        DB::table('help_articles')->update(['organization_id' => null]);
        DB::table('changelog_entries')->update(['organization_id' => null]);
        DB::table('contact_submissions')->update(['organization_id' => null]);
        DB::table('categories')->update(['organization_id' => null]);
        DB::table('organization_user')->delete();
        DB::table('organization_invitations')->delete();
        DB::table('organizations')->delete();
    }
};
