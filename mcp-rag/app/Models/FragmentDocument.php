<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

/**
 * Modelo para fragmentos de documentos con embeddings.
 * Almacena chunks de texto con sus vectores de embedding.
 *
 * @property int $id ID del fragmento
 * @property string $id_metadata_document UUID del documento padre
 * @property int $chunk_index Índice del chunk en el documento
 * @property string $content Contenido del fragmento
 * @property Vector|null $embedding Vector de embedding (1536 dimensiones)
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class FragmentDocument extends Model
{
    use HasNeighbors;

    protected $table = 'fragment_documents';

    protected $fillable = [
        'id_metadata_document',
        'chunk_index',
        'content',
        'embedding',
    ];

    /**
     * Get the casts array.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'embedding' => Vector::class,
        ];
    }

    /**
     * Relación: Un fragmento pertenece a un documento.
     */
    public function metadataDocument(): BelongsTo
    {
        return $this->belongsTo(MetadataDocument::class, 'id_metadata_document');
    }

    /**
     * Scope para obtener fragmentos ordenados por índice.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('chunk_index');
    }

    /**
     * Scope para obtener fragmentos de un documento específico.
     */
    public function scopeForDocument($query, string $documentId)
    {
        return $query->where('id_metadata_document', $documentId);
    }
}
