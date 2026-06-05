<?php

namespace HiEvents\Http\Actions\Questions;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\DomainObjects\ProductDomainObject;
use HiEvents\DomainObjects\ProductPriceDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Repository\Eloquent\Value\Relationship;
use HiEvents\Repository\Interfaces\QuestionRepositoryInterface;
use HiEvents\Resources\Question\QuestionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetQuestionsAction extends BaseAction
{
    private QuestionRepositoryInterface $questionRepository;

    public function __construct(QuestionRepositoryInterface $questionRepository)
    {
        $this->questionRepository = $questionRepository;
    }

    public function __invoke(Request $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $params = QueryParamsDTO::fromArray(array_merge($request->query->all(), [
            'per_page' => (int) $request->query('per_page', 500),
        ]));

        $questions = $this->questionRepository
            ->loadRelation(
                new Relationship(ProductDomainObject::class, [
                    new Relationship(ProductPriceDomainObject::class)
                ])
            )
            ->findByEventId($eventId, $params);

        return $this->resourceResponse(QuestionResource::class, $questions);
    }
}
