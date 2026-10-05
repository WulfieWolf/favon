<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('locale', 10)->default('de')->after('email');
        });

        Schema::create('user_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('allow_email_notifications')->default(true);
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('user_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('consent_type', 100);
            $table->string('version', 50);
            $table->timestamp('accepted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'consent_type', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_consents');
        Schema::dropIfExists('user_settings');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('locale');
        });
    }
};
