<?php

declare(strict_types = 1);

namespace HoneyComb\Resources\Repositories;

use HoneyComb\Core\Repositories\Traits\HCQueryBuilderTrait;
use HoneyComb\Resources\Http\Requests\HCResourceThumbRequest;
use HoneyComb\Resources\Models\HCResourceThumb;
use HoneyComb\Starter\Repositories\HCBaseRepository;

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
        return $this->createBuilderQuery($request)->get()->map(function ($record) {
            return [
                'id' => $record->id,
                'name' => $record->id,
            ];
        });
    }


}
