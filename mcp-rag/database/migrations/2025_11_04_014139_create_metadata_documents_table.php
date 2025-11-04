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
        Schema::create('metadata_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('document_title', 40);
            $table->jsonb('metadata')->nullable();
            $table->string('document_path', 50);
            $table->boolean('valid')->default(true);
            $table->integer('version')->default(1);
            $table->timestamps();

            // Índices para optimización
            $table->index('document_title');
            $table->index('valid');
            $table->index(['document_title', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metadata_documents');
    }
};
