<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
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
            'title' => 'required|min:3|max:30',
            'description' => 'required|min:3|max:500',
            'contact_no' => 'required|regex:/^(?!0+$)[+]?[0-9]+$/|min:10|max:10',
        ];
    }
     public function messages(): array
    {
        return [
            'contact_no.required' => 'The contact no. field is required.',
            
        ];
    }
}
