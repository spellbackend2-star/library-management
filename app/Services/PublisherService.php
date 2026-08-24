<?php

namespace App\Services;

use App\Models\Publisher;
use App\Repositories\Interface\PublisherInterface;

class PublisherService
{
    public function __construct(
        protected PublisherInterface $publisherRepository
    ) {}

    public function getAll()
    {
        return $this->publisherRepository->all();
    }

    public function getById(int $id): ?Publisher
    {
        return $this->publisherRepository->find($id);
    }

    public function create(array $data): Publisher
    {
        return $this->publisherRepository->create($data);
    }

    public function update(int $id, array $data): Publisher
    {
        return $this->publisherRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->publisherRepository->delete($id);
    }
}
