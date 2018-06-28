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
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * @var HCResourceRepository
     */
    protected $repository;

    /**
     * @var bool
     */
    protected $allowDuplicates;

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
     * @return StreamedResponse
     */
    public function show(?string $id, int $width, int $height, bool $fit): StreamedResponse
    {
        if (is_null($id)) {
            logger()->info('resourceId is null');
            exit;
        }

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

        $cachePath = $this->generateCachePath(
            $resource->id,
            $resource->disk,
            $resource->extension,
            $width,
            $height,
            $fit
        );

        if (Storage::disk($resource->disk)->exists($cachePath)) {
            //set cache details
            $resource->size = Storage::disk($resource->disk)->size($cachePath);
            $resource->path = $cachePath;
        } else {
            switch ($resource->mime_type) {
                case 'text/plain':
                    if ($resource->extension == '.svg') {
                        $resource->mime_type = 'image/svg+xml';
                    }
                    break;

                case 'image/svg':
                    if ($resource->mime_type = 'image/svg') {
                        $resource->mime_type = 'image/svg+xml';
                    }
                    break;

                case 'image/jpg':
                case 'image/png':
                case 'image/jpeg':
                    if ($width != 0 && $height != 0) {
                        $this->createImage(
                            $resource->disk,
                            $resource->path,
                            $cachePath,
                            $width,
                            $height,
                            $fit
                        );

                        $resource->size = Storage::disk($resource->disk)->size($cachePath);
                        $resource->path = $cachePath;
                    }
                    break;

                case 'video/mp4':
                    $storagePath = '/';

                    $previewPath = str_replace('-', '/', $resource->id);
                    $fullPreviewPath = $storagePath . 'video-previews/' . $previewPath;

                    $cachePath = $this->generateCachePath(
                        $previewPath,
                        $resource->disk,
                        '.jpg',
                        $width,
                        $height,
                        $fit
                    );

                    if (Storage::disk($resource->disk)->exists($cachePath)) {
                        $resource->size = Storage::disk($resource->disk)->size($cachePath);
                        $resource->path = $cachePath;
                        $resource->mime_type = 'image/jpg';
                    } else {
                        if ($width != 0 && $height != 0) {
                            dd('not finished, because of package');
                            $videoPreview = $fullPreviewPath . '/preview_frame.jpg';

                            //TODO: generate 3-5 previews and take the one with largest size
                            $this->generateVideoPreview($resource, $storagePath, $previewPath);

                            $this->createImage($videoPreview, $cachePath, $width, $height, $fit);

                            $resource->size = Storage::disk($resource->disk)->size($cachePath);
                            $resource->path = $cachePath;
                            $resource->mime_type = 'image/jpg';
                        } else {
                            $resource->path = $storagePath . $resource->path;
                        }
                    }
                    break;
            }
        }

        $headers = [
            'Content-Type' => $resource->mime_type,
            'Content-Length' => $resource->size,
        ];

        return Storage::disk($resource->disk)
            ->response($resource->path, $this->getOriginalFileName($resource), $headers);
    }

    /**
     * Upload and insert new resource into database
     * Catch for errors and if is error throw error
     *
     * @param UploadedFile $file
     * @param string|null $lastModified
     * @param string|null $disk
     * @param string|null $customId
     * @return array
     * @throws \Throwable
     */
    public function upload(
        UploadedFile $file,
        string $lastModified = null,
        string $disk = null,
        string $customId = null
    ): array {
        try {
            if (is_null($disk)) {
                $disk = config('filesystems.default');
            }

            /** @var HCResource $resource */
            $resource = $this->getRepository()->create(
                $this->getFileParams($file, $disk, $lastModified, $customId)
            );

            $this->saveInStorage($resource, $file);

            // generate checksum
            if ($resource->size <= config('resources.max_checksum_size')) {
                $this->getRepository()->updateChecksum($resource);
            }

        } catch (\Throwable $exception) {
            if (isset($resource)) {
                $this->removeResource($disk, $resource);
            }

            throw $exception;
        }

        $resource = $resource->toArray();
        // set storage resource url
        $resource['storageUrl'] = $this->isLocal($disk) ? null : Storage::disk($disk)->url($resource['path']);

        return $resource;
    }

    /**
     * Downloading and storing image in the system
     *
     * @param string $source
     * @param bool $full - if set to true than return full resource data
     * @param string $customId
     * @param null|string $mime_type
     * @return array|mixed|null
     * @throws \Throwable
     */
    public function download(string $source, bool $full = null, string $customId = null, string $mime_type = null)
    {
        if ($customId) {
            $resource = $this->getRepository()->find($customId);

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
                $resource = $this->getRepository()->findOneBy(['checksum' => hash_file('sha256', $destination)]);

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

            return $this->upload($file, null, $customId);
        }

        return null;
    }

    /**
     * Get file params
     * @param UploadedFile $file
     * @param string $disk
     * @param string|null $lastModified
     * @param string|null $customId
     * @return array
     */
    public function getFileParams(
        UploadedFile $file,
        string $disk,
        string $lastModified = null,
        string $customId = null
    ): array {
        $params = [];

        if ($customId) {
            $params['id'] = $customId;
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
        $params['size'] = $file->getClientSize();
        $params['mime_type'] = $file->getClientMimeType();
        $params['uploaded_by'] = auth()->check() ? auth()->id() : null;
        // TODO move lastModified getting to upload method as param
        $params['original_at'] = $lastModified;

        return $params;
    }

    /**
     * Upload file to server
     *
     * @param HCResource $resource
     * @param UploadedFile $file
     */
    protected function saveInStorage(HCResource $resource, UploadedFile $file): void
    {
        if (Storage::disk($resource->disk)->exists($resource->path)) {
            logger()->warning('file already exists with this name: ' . $resource->safe_name);

            return;
        }

        $file->storeAs($this->uploadPath, $resource->safe_name, $resource->disk);
    }

    /**
     * Remove item from storage
     *
     * @param HCResource $resource
     * @param string $disk
     */
    protected function removeResourceFromStorage(HCResource $resource, string $disk): void
    {
        if (Storage::disk($disk)->has($resource['path'])) {
            Storage::disk($disk)->delete($resource['path']);
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
                        return implode('.', $name);
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
     * @param HCResource $resource
     * @param string $storagePath
     * @param string $previewPath
     */
    private function generateVideoPreview(HCResource $resource, string $storagePath, string $previewPath): void
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
     * @param string $resourceId
     * @param string $disk
     * @param string $extension
     * @param int|null $width
     * @param int|null $height
     * @param null $fit
     * @return string
     */
    private function generateCachePath(
        string $resourceId,
        string $disk,
        string $extension,
        int $width = 0,
        int $height = 0,
        $fit = null
    ): string {
        if ($this->isLocal($disk)) {
            $folder = config('filesystems.disks.local.root') . '/cache/' . str_replace('-', '/', $resourceId);

            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }
        }

        $path = 'cache/' . str_replace('-', '/', $resourceId) . '/';

        $path .= $width . '_' . $height;

        if ($fit) {
            $path .= '_fit';
        }

        return $path . $extension;
    }

    /**
     * Creating image based on provided data
     *
     * @param string $disk
     * @param $source
     * @param $destination
     * @param int $width
     * @param int $height
     * @param bool $fit
     * @return bool
     */
    private function createImage(string $disk, $source, $destination, $width = 0, $height = 0, $fit = false): bool
    {
        if ($width == 0) {
            $width = null;
        }

        if ($height == 0) {
            $height = null;
        }

        if ($this->isLocal($disk)) {
            $source = config('filesystems.disks.local.root') . DIRECTORY_SEPARATOR . $source;
            $destination = config('filesystems.disks.local.root') . DIRECTORY_SEPARATOR . $destination;
        } else {
            $source = Storage::disk($disk)->url($source);
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

        if ($this->isLocal($disk)) {
            $image->save($destination);
        } else {
            Storage::disk($disk)->put($destination, (string)$image->encode());
        }

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

    /**
     * @param string $path - uploads/date/file-name
     * @param string $disk
     * @return string
     */
    protected function getResourcePath(string $path, string $disk): string
    {
        if ($this->isLocal($disk)) {
            return config('filesystems.disks.local.root') . DIRECTORY_SEPARATOR . $path;
        }

        return Storage::disk($disk)->url($path);
    }

    /**
     * @param string $disk
     * @return bool
     */
    private function isLocal(string $disk): bool
    {
        return $disk == 'local';
    }

    /**
     * @param string $disk
     * @param $resource
     */
    private function removeResource(string $disk, $resource): void
    {
        $this->removeResourceFromStorage($resource, $disk);

        if ($resource instanceof HCResource) {
            $resource->forceDelete();
        }
    }

    /**
     * @param $resource
     * @return string
     */
    private function getOriginalFileName($resource): string
    {
        $name = str_replace($resource->extension, '', $resource->original_name);

        return str_slug($name) . $resource->extension;
    }
}
