<?php

namespace App\Contracts;

use App\Models\MetadataDocument;
use Illuminate\Support\Collection;

/**
 * Contrato para el repositorio de documentos.
 */
interface DocumentRepositoryInterface
{
    /**
     * Crear un nuevo documento con metadatos.
     */
    public function create(array $data): MetadataDocument;

    /**
     * Buscar documento por ID.
     */
    public function findById(string $id): ?MetadataDocument;

    /**
     * Buscar documento por título y versión.
     */
    public function findByTitleAndVersion(string $title, int $version): ?MetadataDocument;

    /**
     * Obtener la última versión de un documento.
     */
    public function getLatestVersion(string $title): ?MetadataDocument;

    /**
     * Obtener todas las versiones de un documento.
     */
    public function getAllVersions(string $title): Collection;

    /**
     * Actualizar un documento.
     */
    public function update(string $id, array $data): bool;

    /**
     * Marcar documento como inválido (soft delete lógico).
     */
    public function markAsInvalid(string $id): bool;

    /**
     * Eliminar documento y sus fragmentos (hard delete).
     */
    public function delete(string $id): bool;

    /**
     * Obtener documentos válidos.
     */
    public function getValidDocuments(): Collection;

    /**
     * Crear nueva versión de un documento existente.
     */
    public function createNewVersion(string $title): MetadataDocument;
}
