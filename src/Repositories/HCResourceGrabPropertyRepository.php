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

}
