<?php

declare(strict_types = 1);

namespace HoneyComb\Resources\Repositories\Admin;

use HoneyComb\Resources\Models\HCResourceTag;
use HoneyComb\Resources\Http\Requests\Admin\HCResourceTagRequest;
use HoneyComb\Starter\Repositories\Traits\HCQueryBuilderTrait;
use HoneyComb\Starter\Repositories\HCBaseRepository;

class HCResourceTagRepository extends HCBaseRepository
{
    use HCQueryBuilderTrait;

    /**
     * @return string
     */
    public function model(): string
    {
        return HCResourceTag::class;
    }

    /**
     * @param HCResourceTagRequest $request
     * @return \Illuminate\Support\Collection|static
     */
    public function getOptions(HCResourceTagRequest $request)
    {
        return $this->createBuilderQuery($request)->get()->map(function($record) {
            return [
                'id' => $record->id,
                'label' => $record->label,
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
            /** @var HCResourceTag $record */
            $record->delete();
        }
    }

    /**
     * Restore soft deleted records
     *
     * @param array $ids
     * @return void
     */
    public function restore(array $ids): void
    {
        $records = $this->makeQuery()->withTrashed()->whereIn('id', $ids)->get();

        foreach ($records as $record) {
            /** @var HCResourceTag $record */
            $record->restore();
        }
    }


}