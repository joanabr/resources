<?php
declare(strict_types = 1);

namespace HoneyComb\Resources\Repositories;

use HoneyComb\Core\Repositories\HCBaseRepository;
use HoneyComb\Resources\Models\HCResource;
use Illuminate\Http\UploadedFile;

/**
 * Class HCResourceRepository
 * @package HoneyComb\Resources\Repositories
 */
class HCResourceRepository extends HCBaseRepository
{
    /**
     * @return string
     */
    public function model(): string
    {
        return HCResource::class;
    }

    /**
     * Get file params
     *
     * @param $file
     * @return array
     */
    public function getFileParams(UploadedFile $file)
    {
        $params = [];

        if ($this->resourceID) {
            $params['id'] = $this->resourceID;
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
}
