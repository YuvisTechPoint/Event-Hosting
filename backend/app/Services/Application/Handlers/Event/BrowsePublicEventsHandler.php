<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Event;

use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Repository\Interfaces\EventRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

readonly class BrowsePublicEventsHandler
{
    public function __construct(private EventRepositoryInterface $eventRepository)
    {
    }

    public function handle(Request $request): LengthAwarePaginator
    {
        $params = QueryParamsDTO::fromArray($request->query->all());

        return $this->eventRepository->findBrowsableLiveEvents(
            params: $params,
            category: $request->query('category'),
            startDateFrom: $request->query('start_date_from'),
            startDateTo: $request->query('start_date_to'),
            sortBy: $request->query('sort', 'start_date'),
        );
    }
}
