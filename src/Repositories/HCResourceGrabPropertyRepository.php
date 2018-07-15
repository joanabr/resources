<?php

declare(strict_types = 1);

namespace HoneyComb\Resources\Repositories;

use HoneyComb\Resources\Models\HCResourceGrabProperty;
use HoneyComb\Starter\Repositories\HCBaseRepository;
use HoneyComb\Starter\Repositories\Traits\HCQueryBuilderTrait;

class HCResourceGrabPropertyRepository extends HCBaseRepository
{
    use HCQueryBuilderTrait;

    /**
     * @return string
     */
    public function model(): string
    {
        return HCResourceGrabProperty::class;
    }

    /**
     * @param string $sourceType
     * @param string $sourceId
     * @param string $resourceId
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getGrabbed(string $sourceType, string $sourceId, string $resourceId)
    {
        return $this->makeQuery()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('resource_id', $resourceId)->get();
    }

}
