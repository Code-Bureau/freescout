<?php

namespace App\Http\Requests;

use App\Conversation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SanitizeSearchRequest
 * @package App\Http\Requests
 */
class SanitizeSearchRequest extends FormRequest
{
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
     *
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'q'         => $this->getSearchQuery(),
            'mode'      => !empty($this->mode) &&  $this->mode === Conversation::SEARCH_MODE_CUSTOMERS ? Conversation::SEARCH_MODE_CUSTOMERS : Conversation::SEARCH_MODE_CONV,
            'filters'   => $this->getSearchFilters()
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'q'         => 'string',
            'mode'      => 'required',
            'filters'   => 'array'
        ];
    }

    private function getSearchQuery(): string
    {
        $q = '';
        if (!empty($this->q)) {
            $q = $this->q;
        } elseif (!empty($this->filter) && !empty($this->filter['q'])) {
            $q = $this->filter['q'];
        }

        return trim($q);
    }

    private function getSearchFilters()
    {
        $filters = [];

        if (!empty($this->f)) {
            $filters = $this->f;
        } elseif (!empty($this->filter) && !empty($this->filter['f'])) {
            $filters = $this->filter['f'];
        }

        return $filters;
    }
}
