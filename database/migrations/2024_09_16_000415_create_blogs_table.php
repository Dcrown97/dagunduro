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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('blog_category_id')->nullable();
            $table->string('title')->nullable();
            $table->string('author_name')->nullable();
            $table->longText('blog_banner')->nullable();
            $table->longText('author_image')->nullable();
            $table->longText('description')->nullable();
            $table->integer('like')->default(0);
            $table->integer('share')->default(0);
            $table->integer('comment_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->string('show_author')->nullable()->comment('true, false');
            $table->string('allow_comments')->nullable()->comment('true, false');
            $table->string('allow_share')->nullable()->comment('true, false');
            $table->string('allow_likes')->nullable()->comment('true, false');
            $table->string('status')->nullable()->comment('Posts, Draft');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('blog_category_id')->references('id')->on('blog_categories')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
