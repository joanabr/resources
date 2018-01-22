<?php
declare(strict_types = 1);

namespace HoneyComb\Resources\Services;

use HoneyComb\Resources\Models\HCResource;
use HoneyComb\Resources\Repositories\HCResourceRepository;
use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\Uuid;
use Storage;

/**
 * Class HCResourceService
 * @package HoneyComb\Resources\Services
 */
class HCResourceService
{
    /**
     * Maximum file size to perform checksum calculation
     */
    const MAX_CHECKSUM_SIZE = 102400000;

    /**
     * File upload location
     *
     * @var string
     */
    private $uploadPath;

    /**
     * If uploaded file has predefined ID it will be used
     *
     * @var
     */
    private $resourceId;

    /**
     * @var bool
     */
    private $allowDuplicates;

    /**
     * @var HCResourceRepository
     */
    private $repository;

    /**
     * HCResourceService constructor.
     *
     * @param HCResourceRepository $repository
     * @param bool $allowDuplicates - should the checksum be validated and duplicates found
     */
    public function __construct(HCResourceRepository $repository, bool $allowDuplicates = false)
    {
        $this->allowDuplicates = $allowDuplicates;

        $this->uploadPath = 'uploads/' . date("Y-m-d") . DIRECTORY_SEPARATOR;
        $this->repository = $repository;
    }

    /**
     * Upload and insert new resource into database
     * Catch for errors and if is error throw error
     *
     * @param UploadedFile $file
     * @param bool $full
     * @param string $id
     * @return mixed
     * @throws \Exception
     */
    public function upload(UploadedFile $file, bool $full = null, string $id = null)
    {
        if (is_null($file)) {
            throw new \Exception(trans('resources::resources.errors.no_resource_selected'));
        }

        $this->resourceId = $id;

        try {
            $resource = $this->repository->create(
                $this->getFileParams($file)
            );

            $this->saveResourceInStorage($resource, $file);

            // generate checksum
            if ($resource['size'] <= config('resources.max_checksum_size', self::MAX_CHECKSUM_SIZE)) {
                $this->repository->update(
                    [
                        'checksum' => hash_file('sha256', storage_path('app/' . $resource['path'])),
                    ],
                    $resource->id
                );
            }

//            Artisan::call('hc:generate-thumbs', ['id' => $resource->id]);
        } catch (\Exception $e) {

            if (isset($resource)) {
                $this->removeImageFromStorage($resource);
            }

            throw new \Exception($e);
        }


        if ($full) {
            return $resource->toArray();
        }

        return [
            'id' => $resource->id,
            'url' => route('resource.get', $resource->id),
        ];
    }

    /**
     * Downloading and storing image in the system
     *
     * @param string $source
     * @param bool $full - if set to true than return full resource data
     * @param string $id
     * @param null|string $mime_type
     * @return array|mixed|null
     * @throws \Exception
     */
    public function downloadResource(string $source, bool $full = null, string $id = null, string $mime_type = null)
    {
        $this->createFolder('uploads/tmp');

        $fileName = $this->getFileName($source);

        if ($fileName && $fileName != '') {

            $destination = storage_path('app/uploads/tmp/' . $fileName);

            file_put_contents($destination, file_get_contents($source));

            if (filesize($destination) <= config('resources.max_checksum_size', self::MAX_CHECKSUM_SIZE)) {
                $resource = $this->repository->findOneBy(['checksum', hash_file('sha256', $destination)]);

                if (!$this->allowDuplicates && $resource) {
                    //If duplicate found deleting downloaded file
                    \File::delete($destination);

                    if ($full) {
                        return $resource->toArray();
                    }

                    return [
                        'id' => $resource->id,
                        'url' => route('resource.get', $resource->id),
                    ];
                }
            }

            if (!\File::exists($destination)) {
                return null;
            }

            if (!$mime_type) {
                $mime_type = mime_content_type($destination);
            }

            $file = new UploadedFile($destination, $fileName, $mime_type, filesize($destination), null, true);

            return $this->upload($file, $full, $id);
        }

        return null;
    }

    /**
     * Get file params
     *
     * @param UploadedFile $file
     * @return array
     */
    public function getFileParams(UploadedFile $file)
    {
        $params = [];

        if ($this->resourceId) {
            $params['id'] = $this->resourceId;
        } else {
            $params['id'] = Uuid::uuid4()->toString();
        }

        $params['original_name'] = $file->getClientOriginalName();
        $params['extension'] = '.' . $file->getClientOriginalExtension();
        $params['path'] = $this->uploadPath . $params['id'] . $params['extension'];
        $params['size'] = $file->getClientSize();
        $params['mime_type'] = $file->getClientMimeType();

        return $params;
    }

    /**
     * Upload file to server
     *
     * @param $resource
     * @param $file
     */
    protected function saveResourceInStorage(HCResources $resource, UploadedFile $file): void
    {
        $this->createFolder($this->uploadPath);

        $file->move(storage_path('app/' . $this->uploadPath),
            $resource->id . '.' . $file->getClientOriginalExtension());
    }

    /**
     * Remove item from storage
     *
     * @param HCResource $resource
     */
    protected function removeImageFromStorage(HCResource $resource): void
    {
        $path = $this->uploadPath . $resource->id;

        if (Storage::has($path)) {
            Storage::delete($path);
        }
    }

    /**
     * Create folder
     *
     * @param $path
     */
    protected function createFolder(string $path): void
    {
        if (!Storage::exists($path)) {
            Storage::makeDirectory($path);
        }
    }

    /**
     * Retrieving file name
     *
     * @param $fileName
     * @return null|string
     */
    protected function getFileName(string $fileName): ? string
    {
        if (!$fileName && filter_var($fileName, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $explodeFileURL = explode('/', $fileName);
        $fileName = end($explodeFileURL);

        return sanitizeString(pathinfo($fileName, PATHINFO_FILENAME)) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
    }
}
