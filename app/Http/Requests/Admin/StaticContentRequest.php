<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StaticContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title_english' => 'required|string|min:3|max:100|not_regex:/<[^>]*>/u',
            'content_english' => 'required|string|min:3|max:100000',
            // 'title_chinese' => 'required|string|min:3|max:100',
            // 'content_chinese' => 'required|string|min:3|max:100000',
        ];
    }
}
