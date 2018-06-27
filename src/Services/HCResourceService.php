<?php
/**
 * @copyright 2018 interactivesolutions
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * Contact InteractiveSolutions:
 * E-mail: hello@interactivesolutions.lt
 * http://www.interactivesolutions.lt
 */

declare(strict_types = 1);

namespace HoneyComb\Resources\Services;

use Carbon\Carbon;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use File;
use HoneyComb\Resources\Models\HCResource;
use HoneyComb\Resources\Repositories\Admin\HCResourceRepository;
use Illuminate\Http\UploadedFile;
use Image;
use Intervention\Image\Constraint;
use Ramsey\Uuid\Uuid;
use Storage;

/**
 * Class HCResourceService
 * @package HoneyComb\Resources\Services
 */
class HCResourceService
{
    /**
     * File upload location
     *
     * @var string
     */
    protected $uploadPath;

    /**
     * If uploaded file has predefined ID it will be used
     *
     * @var
     */
    protected $resourceId;

    /**
     * @var bool
     */
    protected $allowDuplicates;

    /**
     * @var HCResourceRepository
     */
    protected $repository;

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
     * @return HCResourceRepository
     */
    public function getRepository(): HCResourceRepository
    {
        return $this->repository;
    }

    /**
     * @param null|string $id
     * @param int $width
     * @param int $height
     * @param bool $fit
     */
    public function show(?string $id, int $width, int $height, bool $fit): void
    {
        if (is_null($id)) {
            logger()->info('resourceId is null');
            exit;
        }

        $storagePath = storage_path('app/');

        // cache resource for 10 days
        $resource = \Cache::remember($id, 14400, function () use ($id) {
            return HCResource::find($id);
        });

        if (!$resource) {
            logger()->info('File record not found', ['id' => $id]);
            exit;
        }


        if (!Storage::disk($resource->disk)->exists($resource->path)) {
            logger()->info('File not found in storage', ['id' => $id, 'path' => $resource->path]);
            exit;
        }

        $cachePath = $this->generateResourceCacheLocation($resource->id, $width, $height, $fit,
                $resource->disk) . $resource->extension;

        // TODO interact with disk
        if (file_exists($cachePath)) {
            $resource->size = File::size($cachePath);
            $resource->path = $cachePath;
        } else {
            switch ($resource->mime_type) {
                case 'text/plain':
                    if ($resource->extension == '.svg') {
                        $resource->mime_type = 'image/svg+xml';
                    }

                    $resource->path = $storagePath . $resource->path;
                    break;

                case 'image/svg':
                    if ($resource->mime_type = 'image/svg') {
                        $resource->mime_type = 'image/svg+xml';
                    }

                    $resource->path = $storagePath . $resource->path;
                    break;

                case 'image/jpg':
                case 'image/png':
                case 'image/jpeg':
                    if ($width != 0 && $height != 0) {
                        $this->createImage($storagePath . $resource->path, $cachePath, $width, $height, $fit);

                        $resource->size = File::size($cachePath);
                        $resource->path = $cachePath;
                    } else {
                        $resource->path = $storagePath . $resource->path;
                    }
                    break;

                case 'video/mp4':
                    $previewPath = str_replace('-', '/', $resource->id);
                    $fullPreviewPath = $storagePath . 'video-previews/' . $previewPath;

                    $cachePath = $this->generateResourceCacheLocation($previewPath, $width, $height, $fit) . '.jpg';

                    if (file_exists($cachePath)) {
                        $resource->size = File::size($cachePath);
                        $resource->path = $cachePath;
                        $resource->mime_type = 'image/jpg';
                    } else {
                        if ($width != 0 && $height != 0) {
                            $videoPreview = $fullPreviewPath . '/preview_frame.jpg';

                            //TODO: generate 3-5 previews and take the one with largest size
                            $this->generateVideoPreview($resource, $storagePath, $previewPath);

                            $this->createImage($videoPreview, $cachePath, $width, $height, $fit);

                            $resource->size = File::size($cachePath);
                            $resource->path = $cachePath;
                            $resource->mime_type = 'image/jpg';
                        } else {
                            $resource->path = $storagePath . $resource->path;
                        }
                    }
                    break;

                default:
                    $resource->path = $storagePath . $resource->path;
                    break;
            }
        }

        // Show resource
        header('Pragma: public');
        header('Cache-Control: max-age=86400');
        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
        header('Content-Length: ' . $resource->size);
        header('Content-Disposition: inline;filename="' . $resource->original_name . '"');
        header('Content-Type: ' . $resource->mime_type);
        readfile($resource->path);

        exit;
    }

    /**
     * Upload and insert new resource into database
     * Catch for errors and if is error throw error
     *
     * @param UploadedFile $file
     * @param bool $full
     * @param string $id
     * @param \HoneyComb\Resources\Models\HCResource|null $resource
     * @param string|null $disk
     * @return array
     * @throws \Throwable
     */
    public function upload(
        UploadedFile $file,
        bool $full = null,
        string $id = null,
        HCResource $resource = null,
        string $disk = null
    ): array {
        if (!$resource) {
            if (is_null($file)) {
                throw new \Exception(trans('HCResource::resource.error.no_resource_selected'));
            }

            try {
                if (is_null($disk)) {
                    $disk = config('filesystems.default');
                }

                /** @var HCResource $resource */
                $resource = $this->repository->create(
                    $this->getFileParams($file, $id, $disk)
                );

                $fileName = $resource->id . $this->getExtension($file);

                $this->saveResourceInStorage($fileName, $file, $disk);

                // generate checksum
                if ($resource['size'] <= config('resources.max_checksum_size')) {
                    // TODO check with checksum
                    $this->repository->update(
                        [
                            'checksum' => hash_file('sha256', storage_path('app/' . $resource['path'])),
                        ],
                        $resource->id
                    );
                }

            } catch (\Throwable $exception) {
                if (isset($resource)) {
                    $this->removeImageFromStorage($resource, $disk);
                }

                throw $exception;
            }
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
     * @throws \Throwable
     */
    public function download(string $source, bool $full = null, string $id = null, string $mime_type = null)
    {
        if ($id) {
            $resource = $this->repository->find($id);

            if ($resource) {
                if ($full) {
                    return $resource->toArray();
                }

                return [
                    'id' => $resource->id,
                    'url' => route('resource.get', $resource->id),
                ];
            }
        }

        $this->createFolder('uploads/tmp');

        $fileName = $this->getFileName($source);

        if (strlen($fileName)) {
            $destination = storage_path('app/uploads/tmp/' . $fileName);

            file_put_contents($destination, file_get_contents($source));

            if (filesize($destination) <= config('resources.max_checksum_size')) {
                $resource = $this->repository->findOneBy(['checksum' => hash_file('sha256', $destination)]);

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
     * @param string|null $recordId
     * @param string $disk
     * @return array
     */
    public function getFileParams(UploadedFile $file, string $recordId = null, string $disk)
    {
        $params = [];

        if ($recordId) {
            $params['id'] = $recordId;
        } else {
            $params['id'] = Uuid::uuid4()->toString();
        }

        // TODO test with .svg
        $extension = $this->getExtension($file);

        // TODO add extension to original when original name is not well formed
        $params['disk'] = $disk;
        $params['original_name'] = $file->getClientOriginalName();
        $params['extension'] = $extension;
        $params['safe_name'] = $params['id'] . $extension;
        $params['path'] = $this->uploadPath . $params['safe_name'];
        $params['size'] = $file->getSize();
        $params['mime_type'] = $file->getClientMimeType();
        $params['uploaded_by'] = auth()->check() ? auth()->id() : null;
        // TODO move lastModified getting to upload method as param
        $params['original_at'] = request()->has('lastModified') ? Carbon::createFromTimestampMs(request()->get('lastModified')) : null;

        return $params;
    }

    /**
     * Upload file to server
     *
     * @param HCResource $resource
     * @param UploadedFile $file
     * @param string $disk
     */
    protected function saveResourceInStorage(string $fileName, UploadedFile $file, string $disk): void
    {
//        $this->createFolder($this->uploadPath);


        if (Storage::disk($disk)->exists($this->uploadPath . DIRECTORY_SEPARATOR . $fileName)) {
            return null;
        }

        $file->store($this->uploadPath . DIRECTORY_SEPARATOR . $fileName, $disk);

        $originalFilePath = Storage::putFileAs($this->uploadPath, $file, $fileName);

        $file->move(
            storage_path('app/' . $this->uploadPath),
            $resource->id . $this->getExtension($file)
        );
    }

    /**
     * Remove item from storage
     *
     * @param HCResource $resource
     * @param string $disk
     */
    protected function removeImageFromStorage(HCResource $resource, string $disk): void
    {
        $path = $this->uploadPath . $resource->id;

        if (Storage::disk($disk)->has($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    /**
     * Create folder
     *
     * @param string $path
     * @param string $disk
     */
    protected function createFolder(string $path, string $disk): void
    {
        if (!Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->makeDirectory($path);
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
        if (!$fileName) {
            return null;
        }

        if (filter_var($fileName, FILTER_VALIDATE_URL)) {
            $headers = (get_headers($fileName));

            foreach ($headers as $header) {
                if (strpos($header, 'filename')) {
                    $name = explode('filename="', $header);
                    $name = rtrim($name[1], '"');

                    $name = explode('.', $name);

                    if (sizeof($name) > 1) {
                        return str_slug(implode('.', $name));
                    }
                }
            }
        }

        $explodeFileURL = explode('/', $fileName);
        $fileName = end($explodeFileURL);

        $explodedByParams = explode('?', $fileName);
        $fileName = head($explodedByParams);

        if (strpos($fileName, '.') !== false && substr($fileName, -3) != 'php') {
            return sanitizeString(pathinfo($fileName, PATHINFO_FILENAME)) . '.' . pathinfo($fileName,
                    PATHINFO_EXTENSION);
        } else {
            return sanitizeString(pathinfo($fileName, PATHINFO_FILENAME));
        }
    }

    /**
     * generating video preview
     *
     * @param HCResources $resource
     * @param string $storagePath
     * @param string $previewPath
     */
    private function generateVideoPreview(HCResources $resource, string $storagePath, string $previewPath): void
    {
        $fullPreviewPath = $storagePath . 'video-previews/' . $previewPath;

        if (!file_exists($fullPreviewPath)) {
            mkdir($fullPreviewPath, 0755, true);
        }

        $videoPreview = $fullPreviewPath . '/preview_frame.jpg';

        if (!file_exists($videoPreview)) {

            $videoPath = $storagePath . $resource->path;

            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries' => '/usr/bin/ffmpeg',
                'ffprobe.binaries' => '/usr/bin/ffprobe',
                'timeout' => 3600, // The timeout for the underlying process
                'ffmpeg.threads' => 12,   // The number of threads that FFMpeg should use
            ]);

            $video = $ffmpeg->open($storagePath . $resource->path);
            $duration = $video->getFFProbe()->format($videoPath)->get('duration');

            $video->frame(TimeCode::fromSeconds(rand(1, $duration)))
                ->save($videoPreview);

            $resource->mime_type = 'image/jpg';
            $resource->path = $videoPreview;
        }
    }

    /**
     * Generating resource cache location and name
     *
     * @param $id
     * @param int|null $width
     * @param int|null $height
     * @param null $fit
     * @return string
     */
    private function generateResourceCacheLocation($id, $width = 0, $height = 0, $fit = null): string
    {
        $path = storage_path('app/') . 'cache/' . str_replace('-', '/', $id) . '/';

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $path .= $width . '_' . $height;

        if ($fit) {
            $path .= '_fit';
        }

        return $path;
    }

    /**
     * Creating image based on provided data
     *
     * @param $source
     * @param $destination
     * @param int $width
     * @param int $height
     * @param bool $fit
     * @return bool
     */
    private function createImage($source, $destination, $width = 0, $height = 0, $fit = false): bool
    {
        if ($width == 0) {
            $width = null;
        }

        if ($height == 0) {
            $height = null;
        }

        /** @var \Intervention\Image\Image $image */
        $image = Image::make($source);

        if ($fit) {
            $image->fit($width, $height, function (Constraint $constraint) {
                $constraint->upsize();
            });
        } else {
            $image->resize($width, $height, function (Constraint $constraint) {
                $constraint->upsize();
                $constraint->aspectRatio();
            });
        }

        $image->save($destination);

        return true;
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    private function getExtension(UploadedFile $file): string
    {
        if (!$extension = $file->getClientOriginalExtension()) {
            $extension = explode('/', $file->getClientMimeType())[1];
        }

        return '.' . $extension;
    }
}
