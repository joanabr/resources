<?php

declare(strict_types = 1);

namespace HoneyComb\Resources\Http\Controllers\Admin;

use HoneyComb\Resources\Services\HCResourceService;
use HoneyComb\Core\Http\Controllers\HCBaseController;
use HoneyComb\Core\Http\Controllers\Traits\HCAdminListHeaders;
use HoneyComb\Starter\Helpers\HCFrontendResponse;
use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class HCResourceController extends HCBaseController
{
    use HCAdminListHeaders;

    /**
     * @var HCResourceService
     */
    protected $service;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var HCFrontendResponse
     */
    private $response;

    /**
     * HCResourceController constructor.
     * @param Connection $connection
     * @param HCFrontendResponse $response
     * @param HCResourceService $service
     */
    public function __construct(Connection $connection, HCFrontendResponse $response, HCResourceService $service)
    {
        $this->connection = $connection;
        $this->response = $response;
        $this->service = $service;
    }

    /**
     * Admin panel page view
     *
     * @return View
     */
    public function index(): View
    {
        $config = [
            'title' => trans('HCResource::resource.page_title'),
            'url' => route('admin.api.resource'),
            'form' => route('admin.api.form-manager', ['resource']),
            'headers' => $this->getTableColumns(),
            'actions' => $this->getActions('honey_comb_resources_resource'),
        ];

        return view('HCCore::admin.service.index', ['config' => $config]);
    }

    /**
     * Get admin page table columns settings
     *
     * @return array
     */
    public function getTableColumns(): array
    {
        $columns = [
            'id' => $this->headerImage(trans('HCResource::resource.preview')),
            'uploaded_by' => $this->headerText(trans('HCResource::resource.uploaded_by')),
            'original_name' => $this->headerText(trans('HCResource::resource.original_name')),
            'size' => $this->headerText(trans('HCResource::resource.size')),
        ];

        return $columns;
    }
}