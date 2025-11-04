<?php

namespace App\Contracts;

use App\Models\FragmentDocument;
use Illuminate\Support\Collection;

/**
 * Contrato para el repositorio de fragmentos.
 */
interface FragmentRepositoryInterface
{
    /**
     * Crear un nuevo fragmento.
     */
    public function create(array $data): FragmentDocument;

    /**
     * Crear múltiples fragmentos en batch.
     */
    public function createBatch(array $fragments): int;

    /**
     * Buscar fragmento por ID.
     */
    public function findById(int $id): ?FragmentDocument;

    /**
     * Obtener todos los fragmentos de un documento.
     */
    public function getByDocument(string $documentId): Collection;

    /**
     * Actualizar un fragmento.
     */
    public function update(int $id, array $data): bool;

    /**
     * Eliminar fragmento.
     */
    public function delete(int $id): bool;

    /**
     * Eliminar todos los fragmentos de un documento.
     */
    public function deleteByDocument(string $documentId): int;

    /**
     * Contar fragmentos de un documento.
     */
    public function countByDocument(string $documentId): int;
}
