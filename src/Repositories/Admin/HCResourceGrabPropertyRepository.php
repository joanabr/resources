<?php

declare(strict_types = 1);

namespace HoneyComb\Resources\Repositories\Admin;

use HoneyComb\Resources\Models\HCResourceGrabProperty;
use HoneyComb\Resources\Http\Requests\Admin\HCResourceGrabPropertyRequest;
use HoneyComb\Starter\Repositories\Traits\HCQueryBuilderTrait;
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

    /**
     * @param HCResourceGrabPropertyRequest $request
     * @return \Illuminate\Support\Collection|static
     */
    public function getOptions(HCResourceGrabPropertyRequest $request)
    {
        return $this->createBuilderQuery($request)->get()->map(function ($record) {
            return [
                'id' => $record->id,
                'label' => $record->label
            ];
        });
    }

    /**
 * Soft deleting records
 * @param $ids
 */
public function deleteSoft(array $ids): void
{
    $records = $this->makeQuery()->whereIn('id', $ids)->get();

    foreach ($records as $record) {
       /** @var HCResourceGrabProperty $record */
        $record->delete();
    }
}

    

    
}