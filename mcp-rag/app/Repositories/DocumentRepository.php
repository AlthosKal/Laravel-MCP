<?php

namespace App\Repositories;

use App\Contracts\DocumentRepositoryInterface;
use App\Models\MetadataDocument;
use Illuminate\Support\Collection;

/**
 * Implementación del repositorio de documentos.
 */
class DocumentRepository implements DocumentRepositoryInterface
{
    public function create(array $data): MetadataDocument
    {
        return MetadataDocument::query()->create($data);
    }

    public function findById(string $id): ?MetadataDocument
    {
        return MetadataDocument::query()->find($id);
    }

    public function findByTitleAndVersion(string $title, int $version): ?MetadataDocument
    {
        return MetadataDocument::query()
            ->where('document_title', $title)
            ->where('version', $version)
            ->first();
    }

    public function getLatestVersion(string $title): ?MetadataDocument
    {
        return MetadataDocument::query()
            ->latestVersion($title)
            ->first();
    }

    public function getAllVersions(string $title): Collection
    {
        return MetadataDocument::query()
            ->where('document_title', $title)
            ->orderBy('version', 'desc')
            ->get();
    }

    public function update(string $id, array $data): bool
    {
        $document = $this->findById($id);

        if ($document === null) {
            return false;
        }

        return $document->update($data);
    }

    public function markAsInvalid(string $id): bool
    {
        return $this->update($id, ['valid' => false]);
    }

    public function delete(string $id): bool
    {
        $document = $this->findById($id);

        if ($document === null) {
            return false;
        }

        // CASCADE delete se encarga de los fragmentos
        return $document->delete();
    }

    public function getValidDocuments(): Collection
    {
        return MetadataDocument::query()
            ->valid()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createNewVersion(string $title): MetadataDocument
    {
        $latestVersion = $this->getLatestVersion($title);

        if ($latestVersion === null) {
            throw new \RuntimeException("No existe documento con título: {$title}");
        }

        $newVersion = $latestVersion->version + 1;

        return $this->create([
            'document_title' => $title,
            'metadata' => $latestVersion->metadata,
            'document_path' => $latestVersion->document_path,
            'valid' => true,
            'version' => $newVersion,
        ]);
    }
}
