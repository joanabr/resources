<?php

declare(strict_types = 1);

namespace HoneyComb\Resources\Http\Controllers\Admin;

use HoneyComb\Resources\Services\HCResourceAuthorService;
use HoneyComb\Resources\Requests\HCResourceAuthorRequest;
use HoneyComb\Resources\Models\HCResourceAuthor;

use HoneyComb\Core\Http\Controllers\HCBaseController;
use HoneyComb\Core\Http\Controllers\Traits\HCAdminListHeaders;
use HoneyComb\Starter\Helpers\HCFrontendResponse;
use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class HCResourceAuthorController extends HCBaseController
{
    use HCAdminListHeaders;

    /**
     * @var HCResourceAuthorService
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
     * HCResourceAuthorController constructor.
     * @param Connection $connection
     * @param HCFrontendResponse $response
     * @param HCResourceAuthorService $service
     */
    public function __construct(Connection $connection, HCFrontendResponse $response, HCResourceAuthorService $service)
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
            'title' => trans('HCResource::resource_author.page_title'),
            'url' => route('admin.api.resource.author'),
            'form' => route('admin.api.form-manager', ['resource.author']),
            'headers' => $this->getTableColumns(),
            'actions' => $this->getActions('honey_comb_resources_resource_author'),
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
            'name' => $this->headerText(trans('HCResource::resource_author.name')),
        ];

        return $columns;
    }

    /**
     * @param string $id
     * @return \HoneyComb\Resources\Models\HCResourceAuthor|\HoneyComb\Resources\Repositories\HCResourceAuthorRepository|\Illuminate\Database\Eloquent\Model|null
     */
    public function getById(string $id)
    {
        return $this->service->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Creating data list
     * @param HCResourceAuthorRequest $request
     * @return JsonResponse
     */
    public function getListPaginate(HCResourceAuthorRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->getRepository()->getListPaginate($request)
        );
    }

    /**
     * Creating record
     *
     * @param HCResourceAuthorRequest $request
     * @return JsonResponse
     */
    public function store(HCResourceAuthorRequest $request): JsonResponse
    {
        $this->connection->beginTransaction();

        try {
            $this->service->getRepository()->create($request->getRecordData());

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();

            return $this->response->error($e->getMessage());
        }

        return $this->response->success("Created");
    }

    /**
     * Updating menu group record
     *
     * @param HCResourceAuthorRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(HCResourceAuthorRequest $request, string $id): JsonResponse
    {
        $model = $this->service->getRepository()->findOneBy(['id' => $id]);
        $model->update($request->getRecordData());

        return $this->response->success("Created");
    }

    /**
     * @param HCResourceAuthorRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function deleteSoft(HCResourceAuthorRequest $request): JsonResponse
    {
        $this->connection->beginTransaction();

        try {
            $this->service->getRepository()->deleteSoft($request->getListIds());

            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();

            return $this->response->error($exception->getMessage());
        }

        return $this->response->success('Successfully deleted');
    }

    /**
     * @param HCResourceAuthorRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function restore(HCResourceAuthorRequest $request): JsonResponse
    {
        $this->connection->beginTransaction();

        try {
            $this->service->getRepository()->restore($request->getListIds());

            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();

            return $this->response->error($exception->getMessage());
        }

        return $this->response->success('Successfully restored');
    }

    /**
     * @param HCResourceAuthorRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function deleteForce(HCResourceAuthorRequest $request): JsonResponse
    {
        $this->connection->beginTransaction();

        try {
            $this->service->getRepository()->deleteForce($request->getListIds());

            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();

            return $this->response->error($exception->getMessage());
        }

        return $this->response->success('Successfully deleted');
    }
}