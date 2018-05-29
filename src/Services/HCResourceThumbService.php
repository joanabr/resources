<?php

declare(strict_types = 1);

namespace HoneyComb\Resources\Services;

use HoneyComb\Resources\Repositories\HCResourceThumbRepository;

class HCResourceThumbService
{
    /**
     * @var HCResourceThumbRepository
     */
    private $repository;

    /**
     * HCResourceThumbService constructor.
     * @param HCResourceThumbRepository $repository
     */
    public function __construct(HCResourceThumbRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return HCResourceThumbRepository
     */
    public function getRepository(): HCResourceThumbRepository
    {
        return $this->repository;
    }
}