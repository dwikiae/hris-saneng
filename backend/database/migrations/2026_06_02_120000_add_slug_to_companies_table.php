<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columnAdded = false;

        if (!Schema::hasColumn('companies', 'slug')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('legal_name');
            });
            $columnAdded = true;
        }

        $usedSlugs = [];

        DB::table('companies')->orderBy('id')->select(['id', 'name'])->get()->each(function (object $company) use (&$usedSlugs): void {
            $baseSlug = Str::slug((string) $company->name) ?: 'company-'.$company->id;
            $slug = $baseSlug;
            $suffix = 2;

            while (in_array($slug, $usedSlugs, true)) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }

            $usedSlugs[] = $slug;

            DB::table('companies')
                ->where('id', $company->id)
                ->update(['slug' => $slug]);
        });

        if ($columnAdded) {
            Schema::table('companies', function (Blueprint $table) {
                $table->unique('slug');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
