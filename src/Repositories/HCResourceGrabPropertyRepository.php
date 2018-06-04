<?php

declare(strict_types = 1);

namespace HoneyComb\Resources\Repositories;

use HoneyComb\Resources\Models\HCResourceGrabProperty;
use HoneyComb\Core\Repositories\Traits\HCQueryBuilderTrait;
use HoneyComb\Starter\Repositories\HCBaseRepository;

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