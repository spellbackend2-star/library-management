<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_pages', function (Blueprint $table) {
            $table->string('hero_title')->nullable();
            $table->text('hero_subtitle')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('hero_cta_label')->nullable();
            $table->string('hero_cta_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('central_blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('central_blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()
                ->constrained('central_blog_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_published', 'published_at']);
        });

        Schema::create('central_testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('role_or_company', 180)->nullable();
            $table->text('quote');
            $table->string('photo')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('central_contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('subject', 200)->nullable();
            $table->text('message');
            $table->string('status', 30)->default('new');
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_contact_messages');
        Schema::dropIfExists('central_testimonials');
        Schema::dropIfExists('central_blogs');
        Schema::dropIfExists('central_blog_categories');

        Schema::table('central_pages', function (Blueprint $table) {
            $table->dropColumn([
                'hero_title',
                'hero_subtitle',
                'hero_image',
                'hero_cta_label',
                'hero_cta_url',
                'sort_order',
            ]);
        });
    }
};
