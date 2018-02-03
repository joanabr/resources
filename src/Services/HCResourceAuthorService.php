<?php

declare(strict_types = 1);

namespace HoneyComb\Resources\Services;

use HoneyComb\Resources\Repositories\HCResourceAuthorRepository;

class HCResourceAuthorService
{
    /**
     * @var HCResourceAuthorRepository
     */
    private $repository;

    /**
     * HCResourceAuthorService constructor.
     * @param HCResourceAuthorRepository $repository
     */
    public function __construct(HCResourceAuthorRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return HCResourceAuthorRepository
     */
    public function getRepository(): HCResourceAuthorRepository
    {
        return $this->repository;
    }
}