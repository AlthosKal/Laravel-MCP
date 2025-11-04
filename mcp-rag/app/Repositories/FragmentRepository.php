<?php

namespace App\Repositories;

use App\Contracts\FragmentRepositoryInterface;
use App\Models\FragmentDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Implementación del repositorio de fragmentos.
 */
class FragmentRepository implements FragmentRepositoryInterface
{
    public function create(array $data): FragmentDocument
    {
        return FragmentDocument::query()->create($data);
    }

    public function createBatch(array $fragments): int
    {
        return DB::transaction(function () use ($fragments) {
            $inserted = 0;

            foreach ($fragments as $fragment) {
                FragmentDocument::query()->create($fragment);
                $inserted++;
            }

            return $inserted;
        });
    }

    public function findById(int $id): ?FragmentDocument
    {
        return FragmentDocument::query()->find($id);
    }

    public function getByDocument(string $documentId): Collection
    {
        return FragmentDocument::query()
            ->forDocument($documentId)
            ->ordered()
            ->get();
    }

    public function update(int $id, array $data): bool
    {
        $fragment = $this->findById($id);

        if ($fragment === null) {
            return false;
        }

        return $fragment->update($data);
    }

    public function delete(int $id): bool
    {
        $fragment = $this->findById($id);

        if ($fragment === null) {
            return false;
        }

        return $fragment->delete();
    }

    public function deleteByDocument(string $documentId): int
    {
        return FragmentDocument::query()
            ->forDocument($documentId)
            ->delete();
    }

    public function countByDocument(string $documentId): int
    {
        return FragmentDocument::query()
            ->forDocument($documentId)
            ->count();
    }
}
