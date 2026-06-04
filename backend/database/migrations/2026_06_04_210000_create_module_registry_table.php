<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('module_registry')) {
            return;
        }

        Schema::create('module_registry', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('version');
            $table->text('description')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->json('dependencies')->nullable();
            $table->boolean('is_installed')->default(false);
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('uninstalled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_registry');
    }
};
