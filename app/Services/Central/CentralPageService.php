<?php

namespace App\Services\Central;

use App\Models\CentralPage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CentralPageService
{
    /**
     * Get paginated list of central pages.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        return CentralPage::with(['creator', 'updater'])
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Create a new central page.
     */
    public function create(array $data, ?int $userId = null): CentralPage
    {
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;

        return CentralPage::create($data);
    }

    /**
     * Find a central page by ID.
     */
    public function find(int $id): ?CentralPage
    {
        return CentralPage::with(['creator', 'updater'])->find($id);
    }

    /**
     * Update a central page.
     */
    public function update(CentralPage $page, array $data, ?int $userId = null): CentralPage
    {
        $data['updated_by'] = $userId;

        $page->update($data);

        return $page->fresh(['creator', 'updater']);
    }

    /**
     * Delete a central page.
     */
    public function delete(CentralPage $page): void
    {
        $page->delete();
    }
}
