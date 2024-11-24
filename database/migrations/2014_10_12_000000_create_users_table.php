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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('country_code')->nullable();
            $table->string('phoneno')->nullable();
            $table->string('home_address')->nullable();
            $table->string('occupation')->nullable();
            $table->string('username')->nullable();
            $table->string('is_verified')->nullable()->comment('true, false');
            $table->string('can_login')->nullable()->comment('true, false');
            $table->string('is_active')->nullable()->comment('Active, Inactive');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
