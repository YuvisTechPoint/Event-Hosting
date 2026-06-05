<?php

namespace HiEvents\Http\Actions\Organizers;

use HiEvents\DomainObjects\ImageDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Repository\Interfaces\OrganizerRepositoryInterface;
use HiEvents\Resources\Organizer\OrganizerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetOrganizersAction extends BaseAction
{
    public function __construct(private readonly OrganizerRepositoryInterface $organizerRepository)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $params = $this->getPaginationQueryParams($request);

        $organizers = $this->organizerRepository
            ->loadRelation(ImageDomainObject::class)
            ->paginateWhere(
                where: ['account_id' => $this->getAuthenticatedAccountId()],
                limit: $params->per_page,
                page: $params->page,
            );

        return $this->resourceResponse(
            resource: OrganizerResource::class,
            data: $organizers,
        );
    }
}
