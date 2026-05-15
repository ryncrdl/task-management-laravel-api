<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('notification_jobs')) {
            return;
        }

        Schema::create('notification_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('task_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('event_type', 50);
            $table->jsonb('details')->default('{}');
            $table->string('status', 20)->default('pending')->index();
            $table->smallInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestampTz('scheduled_at')->useCurrent();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_jobs');
    }
};
