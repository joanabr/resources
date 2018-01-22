<?php
declare(strict_types = 1);

namespace HoneyComb\Resources\Models;

use HoneyComb\Core\Models\HCUuidModel;

/**
 * Class HCResource
 * @package HoneyComb\Resources\Models
 */
class HCResource extends HCUuidModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'hc_resources';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'original_name',
        'safe_name',
        'size',
        'path',
        'mime_type',
        'extension',
        'checksum',
        'uploaded_by',
    ];

    /**
     * Get file path of the resource
     *
     * @return string
     */
    public function file_path(): string
    {
        return storage_path('app' . DIRECTORY_SEPARATOR . $this->path);
    }

    /**
     * Check if resource is image
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return strpos($this->mime_type, 'image') !== false;
    }
}
