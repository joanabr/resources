<?php
declare(strict_types = 1);

namespace HoneyComb\Resources\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class HCResourceRequest
 * @package HoneyComb\Resources\Http\Request
 */
class HCResourceRequest extends FormRequest
{
    /**
     * Get resource file
     *
     * @return \Illuminate\Http\UploadedFile|array|null
     */
    public function getFile()
    {
        return $this->file('file');
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'file' => 'required',
        ];
    }
}
