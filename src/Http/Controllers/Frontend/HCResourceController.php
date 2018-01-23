<?php
declare(strict_types = 1);

namespace HoneyComb\Resources\Http\Controllers\Frontend;

use HoneyComb\Core\Http\Controllers\HCBaseController;
use HoneyComb\Resources\Services\HCResourceService;

/**
 * Class HCResourceController
 * @package HoneyComb\Resources\Http\Controllers\Frontend
 */
class HCResourceController extends HCBaseController
{
    /**
     * @var HCResourceService
     */
    private $service;

    /**
     * HCResourceController constructor.
     * @param HCResourceService $service
     */
    public function __construct(HCResourceService $service)
    {
        $this->service = $service;
    }

    /**
     * Show resource
     *
     * @param null|string $id
     * @param int|null $width
     * @param int|null $height
     * @param bool|null $fit
     * @return mixed
     */
    public function show(string $id = null, int $width = 0, int $height = 0, bool $fit = false): void
    {
        $this->service->show($id, $width, $height, $fit);
    }
}
