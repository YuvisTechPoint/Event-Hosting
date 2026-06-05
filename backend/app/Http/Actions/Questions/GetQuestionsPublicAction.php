<?php

namespace HiEvents\Http\Actions\Questions;

use HiEvents\DomainObjects\Generated\QuestionDomainObjectAbstract;
use HiEvents\DomainObjects\ProductDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Repository\Interfaces\QuestionRepositoryInterface;
use HiEvents\Resources\Question\QuestionResourcePublic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetQuestionsPublicAction extends BaseAction
{
    private QuestionRepositoryInterface $questionRepository;

    public function __construct(QuestionRepositoryInterface $questionRepository)
    {
        $this->questionRepository = $questionRepository;
    }

    public function __invoke(Request $request, int $eventId): JsonResponse
    {
        $params = QueryParamsDTO::fromArray(array_merge($request->query->all(), [
            'per_page' => max((int) $request->query('per_page', 100), 100),
        ]));

        $questions = $this->questionRepository
            ->loadRelation(ProductDomainObject::class)
            ->findByEventId(
                eventId: $eventId,
                params: $params,
                additionalWhere: [QuestionDomainObjectAbstract::IS_HIDDEN => false],
            );

        return $this->resourceResponse(QuestionResourcePublic::class, $questions);
    }
}
