<?php

declare(strict_types=1);

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\DripCampaignStepDomainObject;
use HiEvents\DomainObjects\Generated\DripCampaignStepDomainObjectAbstract;
use HiEvents\Models\DripCampaignStep;
use HiEvents\Repository\Eloquent\Value\OrderAndDirection;
use HiEvents\Repository\Interfaces\DripCampaignStepRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepository<DripCampaignStepDomainObject>
 */
class DripCampaignStepRepository extends BaseRepository implements DripCampaignStepRepositoryInterface
{
    protected function getModel(): string
    {
        return DripCampaignStep::class;
    }

    public function getDomainObject(): string
    {
        return DripCampaignStepDomainObject::class;
    }

    public function findByCampaignId(int $campaignId): Collection
    {
        return $this->findWhere(
            where: [DripCampaignStepDomainObjectAbstract::DRIP_CAMPAIGN_ID => $campaignId],
            orderAndDirections: [new OrderAndDirection(DripCampaignStepDomainObjectAbstract::STEP_ORDER)],
        );
    }

    public function deleteByCampaignId(int $campaignId): void
    {
        $this->deleteWhere([
            DripCampaignStepDomainObjectAbstract::DRIP_CAMPAIGN_ID => $campaignId,
        ]);
    }
}
