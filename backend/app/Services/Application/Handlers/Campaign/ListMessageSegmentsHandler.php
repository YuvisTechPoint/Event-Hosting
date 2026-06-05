<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Campaign;

use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Repository\Interfaces\MessageSegmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListMessageSegmentsHandler
{
    public function __construct(
        private readonly MessageSegmentRepositoryInterface $messageSegmentRepository,
    ) {
    }

    public function handle(int $eventId, QueryParamsDTO $params): LengthAwarePaginator
    {
        return $this->messageSegmentRepository->findByEventId($eventId, $params);
    }
}
