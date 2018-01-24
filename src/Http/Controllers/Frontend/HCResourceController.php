<?php
declare(strict_types = 1);

namespace HoneyComb\Resources\Http\Controllers\Frontend;

use HoneyComb\Core\Helpers\HCFrontendResponse;
use HoneyComb\Core\Http\Controllers\HCBaseController;
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
    /**
     * @var HCResourceService
     */
    private $service;

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
     * @param HCResourceService $service
     * @param HCFrontendResponse $response
     */
    public function __construct(Connection $connection, HCResourceService $service, HCFrontendResponse $response)
    {
        $this->service = $service;
        $this->connection = $connection;
        $this->response = $response;
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
