<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            // Identifier from the external provider (e.g. "OL45804W"). Null for books created manually.
            $table->string('external_id', 32)->nullable()->unique();
            $table->string('title');
            $table->string('author')->nullable()->index();
            $table->unsignedSmallInteger('first_publish_year')->nullable()->index();
            $table->string('cover_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
