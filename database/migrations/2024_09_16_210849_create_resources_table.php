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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('resource_category_id')->nullable();
            $table->longText('resource_file')->nullable();
            $table->longText('background_image')->nullable();
            $table->string('title')->nullable();
            $table->string('author')->nullable();
            $table->integer('download_count')->default(0);
            $table->string('file_type')->nullable()->comment('PDF, MP4, MP3');
            $table->string('resource_type')->nullable()->comment('Document, Media');
            $table->string('status')->nullable()->comment('Published, Draft');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('resource_category_id')->references('id')->on('resource_categories')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
