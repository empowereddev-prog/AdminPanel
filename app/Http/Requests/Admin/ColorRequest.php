<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ColorRequest extends FormRequest
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
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_title' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'color.required' => 'The color field is required.',
            'color.regex' => 'Please enter a valid hex color code.',
            'color_title.required' => 'The title color field is required.',
            'color_title.regex' => 'Please enter a valid hex color code.',
        ];
    }
}
