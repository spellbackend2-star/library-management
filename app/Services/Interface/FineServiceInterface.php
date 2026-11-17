<?php

namespace App\Services\Interface;

use App\Models\Borrow;
use App\Models\Fine;

interface FineServiceInterface
{
    public function getAll(array $filters = []): array;

    public function find(int $id): ?Fine;

    public function findBorrow(int $borrowId): ?Borrow;

    public function createManualFine(int $memberId, float $amount, string $reason, ?string $note, ?int $createdBy): Fine;

    public function updateFineAmount(int $fineId, float $amount, string $reason, ?string $notes, bool $notesProvided, ?int $actorUserId): Fine;

    public function cancelFine(int $fineId, ?int $actorUserId, string $note = 'Fine cancelled by admin.'): Fine;

    public function deleteFine(int $fineId): bool;

    public function fineForBorrowOnReturn(Borrow $borrow, ?string $returnDate = null): ?Fine;
}
