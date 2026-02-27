<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add from_name and from_email to mail_templates (required by LaravelDatabaseMail EventMail).
     */
    public function up(): void
    {
        Schema::table('mail_templates', static function (Blueprint $table): void {
            if (! Schema::hasColumn('mail_templates', 'from_name')) {
                $table->string('from_name')->nullable()->after('attachments');
            }
            if (! Schema::hasColumn('mail_templates', 'from_email')) {
                $table->string('from_email')->nullable()->after('from_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mail_templates', static function (Blueprint $table): void {
            $table->dropColumn(['from_name', 'from_email']);
        });
    }
};
