<?php
declare(strict_types = 1);

namespace HoneyComb\Resources\Http\Controllers\Admin;

use HoneyComb\Core\Helpers\HCFrontendResponse;
use HoneyComb\Core\Http\Controllers\HCBaseController;
use HoneyComb\Core\Http\Controllers\Traits\HCAdminListHeaders;
use HoneyComb\Resources\Http\Request\HCResourceRequest;
use HoneyComb\Resources\Services\HCResourceService;
use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;

/**
 * Class HCResourceController
 * @package HoneyComb\Resources\Http\Controllers\Frontend
 */
class HCResourceController extends HCBaseController
{
    use HCAdminListHeaders;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var HCUserService
     */
    private $service;

    /**
     * @var HCFrontendResponse
     */
    private $response;

    /**
     * HCUsersController constructor.
     * @param Connection $connection
     * @param HCResourceService $service
     * @param HCFrontendResponse $response
     */
    public function __construct(Connection $connection, HCResourceService $service, HCFrontendResponse $response)
    {
        $this->connection = $connection;
        $this->service = $service;
        $this->response = $response;
    }

    /**
     * Store record
     *
     * @param HCResourceRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function store(HCResourceRequest $request): JsonResponse
    {
        $this->connection->beginTransaction();

        try {
            $record = $this->service->upload($request->getFile());

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            return $this->response->error($exception->getMessage());
        }

        return $this->response->success('Uploaded', $record);
    }
}
