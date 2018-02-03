<?php

declare(strict_types = 1);

namespace HoneyComb\Resources\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HCResourceAuthorRequest extends FormRequest
{
    /**
     * Get request inputs
     *
     * @return array
     */
    public function getRecordData(): array
    {
        return request()->all();
    }

    /**
     * Get ids to delete, force delete or restore
     *
     * @return array
     */
    public function getListIds(): array
    {
        return $this->input('list', []);
    }

    /**
     * Getting translations
     *
     * @return array
     */
    public function getTranslations(): array
    {
        return $this->input('translations', []);
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
        switch ($this->method()) {
            case 'POST':
                if ($this->segment(4) == 'restore') {
                    return [
                        'list' => 'required|array',
                    ];
                }

                return [
                    'name' => 'required|min:2'
                ];

            case 'PUT':

                return [
                    'name' => 'required|min:2'
                ];

            case 'DELETE':
                return [
                    'list' => 'required|array',
                ];
        }

        return [];
    }
}
