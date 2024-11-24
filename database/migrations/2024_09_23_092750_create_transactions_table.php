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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('amount')->default(0.00);
            $table->string('currency')->nullable();
            $table->string('description')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('donation_type')->nullable()->comment('Tithe, Offering, Mission');
            $table->dateTime("paid_at")->nullable();
            $table->string('payment_status')->default('Pending')->comment('Pending, Successful, Declined');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
