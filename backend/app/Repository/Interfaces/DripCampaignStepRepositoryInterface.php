<?php

declare(strict_types=1);

namespace HiEvents\Repository\Interfaces;

use HiEvents\DomainObjects\DripCampaignStepDomainObject;
use Illuminate\Support\Collection;

/**
 * @extends RepositoryInterface<DripCampaignStepDomainObject>
 */
interface DripCampaignStepRepositoryInterface extends RepositoryInterface
{
    public function findByCampaignId(int $campaignId): Collection;

    public function deleteByCampaignId(int $campaignId): void;
}
