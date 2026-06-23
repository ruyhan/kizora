<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops tables whose content now lives in the JSON CMS (data/*.json) or whose
 * feature was removed: site_pages (privacy/terms), faqs, and corporate_inquiries
 * (the corporate/B2B flow was removed). website_settings is kept — it still
 * stores dynamic reCAPTCHA config.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('site_pages');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('corporate_inquiries');
    }

    public function down(): void
    {
        if (! Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->id();
                $table->string('question', 500);
                $table->text('answer');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('site_pages')) {
            Schema::create('site_pages', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('title');
                $table->string('last_updated')->nullable();
                $table->json('sections')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('corporate_inquiries')) {
            Schema::create('corporate_inquiries', function (Blueprint $table) {
                $table->id();
                $table->string('company_name');
                $table->string('contact_name');
                $table->string('email');
                $table->string('phone');
                $table->string('event_type')->nullable();
                $table->integer('expected_quantity')->nullable();
                $table->date('required_date')->nullable();
                $table->text('requirements')->nullable();
                $table->json('design_files')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }
    }
};
