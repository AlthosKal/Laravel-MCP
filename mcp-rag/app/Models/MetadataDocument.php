<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para documentos con metadatos.
 * Almacena información general del documento y versiones.
 *
 * @property string $id UUID del documento
 * @property string $document_title Título del documento (max 40 chars)
 * @property array|null $metadata Metadatos adicionales en formato JSON
 * @property string $document_path Ruta del documento (max 50 chars)
 * @property bool $valid Si el documento es válido
 * @property int $version Versión del documento
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class MetadataDocument extends Model
{
    use HasUuids;

    protected $table = 'metadata_documents';

    protected $fillable = [
        'document_title',
        'metadata',
        'document_path',
        'valid',
        'version',
    ];

    /**
     * Get the casts array.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'valid' => 'boolean',
            'version' => 'integer',
        ];
    }

    /**
     * Relación: Un documento tiene muchos fragmentos.
     */
    public function fragments(): HasMany
    {
        return $this->hasMany(FragmentDocument::class, 'id_metadata_document');
    }

    /**
     * Scope para obtener solo documentos válidos.
     */
    public function scopeValid($query)
    {
        return $query->where('valid', true);
    }

    /**
     * Scope para obtener la última versión de un documento.
     */
    public function scopeLatestVersion($query, string $documentTitle)
    {
        return $query->where('document_title', $documentTitle)
            ->orderBy('version', 'desc')
            ->limit(1);
    }
}
