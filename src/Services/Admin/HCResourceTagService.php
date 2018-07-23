<?php

declare(strict_types = 1);

namespace HoneyComb\Resources\Services\Admin;

use HoneyComb\Resources\Repositories\Admin\HCResourceTagRepository;

class HCResourceTagService
{
    /**
     * @var HCResourceTagRepository
     */
    private $repository;

    /**
     * HCResourcesTagService constructor.
     * @param HCResourceTagRepository $repository
     */
    public function __construct(HCResourceTagRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return HCResourceTagRepository
     */
    public function getRepository(): HCResourceTagRepository
    {
        return $this->repository;
    }
}