<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fragment_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('id_metadata_document');
            $table->integer('chunk_index');
            $table->text('content');
            $table->timestamps();

            // Relación con metadata_documents
            $table->foreign('id_metadata_document')
                ->references('id')
                ->on('metadata_documents')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            // Índices para optimización
            $table->index('id_metadata_document');
            $table->index(['id_metadata_document', 'chunk_index']);
        });

        // Agregar columna vector usando SQL nativo
        DB::statement('ALTER TABLE fragment_documents ADD COLUMN embedding vector(1536)');

        // Crear índice HNSW para búsqueda eficiente de vectores
        DB::statement('CREATE INDEX fragment_documents_embedding_idx ON fragment_documents USING hnsw (embedding vector_cosine_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // El índice y la columna se eliminan automáticamente con la tabla
        Schema::dropIfExists('fragment_documents');
    }
};
