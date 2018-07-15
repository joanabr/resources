<?php

declare(strict_types = 1);

namespace HoneyComb\Resources\Repositories;

use HoneyComb\Resources\Http\Requests\HCResourceThumbRequest;
use HoneyComb\Resources\Models\HCResourceThumb;
use HoneyComb\Starter\Repositories\HCBaseRepository;
use HoneyComb\Starter\Repositories\Traits\HCQueryBuilderTrait;

class HCResourceThumbRepository extends HCBaseRepository
{
    use HCQueryBuilderTrait;

    /**
     * @return string
     */
    public function model(): string
    {
        return HCResourceThumb::class;
    }

    /**
     * @param HCResourceThumbRequest $request
     * @return \Illuminate\Support\Collection|static
     */
    public function getOptions(HCResourceThumbRequest $request)
    {
        return $this->createBuilderQuery($request)->where('grab_enabled', '1')->get();
    }


}
