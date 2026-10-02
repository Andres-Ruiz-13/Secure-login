<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Categories
        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Banners / Hero
        if (! Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->string('image')->nullable();
                $table->string('button_text')->nullable();
                $table->string('button_url')->nullable();
                $table->integer('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 3. About / Institutional
        if (! Schema::hasTable('about_settings')) {
            Schema::create('about_settings', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->longText('content')->nullable();
                $table->text('mission')->nullable();
                $table->text('vision')->nullable();
                $table->text('values')->nullable();
                $table->text('history')->nullable();
                $table->string('primary_image')->nullable();
                $table->string('secondary_image')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. Videos
        if (! Schema::hasTable('videos')) {
            Schema::create('videos', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('provider')->default('youtube'); // youtube, vimeo
                $table->string('video_url');
                $table->string('video_id');
                $table->string('thumbnail')->nullable();
                $table->integer('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 5. Team Members
        if (! Schema::hasTable('team_members')) {
            Schema::create('team_members', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('position');
                $table->text('biography')->nullable();
                $table->string('photo')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('linkedin_url')->nullable();
                $table->integer('position_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 6. Testimonials
        if (! Schema::hasTable('testimonials')) {
            Schema::create('testimonials', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('position_or_company')->nullable();
                $table->text('testimonial');
                $table->string('photo')->nullable();
                $table->unsignedTinyInteger('rating')->default(5);
                $table->integer('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 7. Contact Messages
        if (! Schema::hasTable('contact_messages')) {
            Schema::create('contact_messages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('phone')->nullable();
                $table->string('subject')->nullable();
                $table->text('message');
                $table->string('status')->default('new'); // new, read, answered, archived
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }

        // 8. Social Links
        if (! Schema::hasTable('social_links')) {
            Schema::create('social_links', function (Blueprint $table) {
                $table->id();
                $table->string('network'); // Facebook, Instagram, LinkedIn, etc.
                $table->string('url');
                $table->string('icon')->nullable();
                $table->integer('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 9. Site Settings
        if (! Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('group')->default('general');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('social_links');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('about_settings');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('categories');
    }
};
