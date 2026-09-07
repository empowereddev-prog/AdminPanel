<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
            'name' => 'required|min:3|max:50|regex:/^[a-zA-Z.\s]+$/',
            'gender' => 'required',
            'email' => 'required|regex:/(.+)@(.+)\.(.+)/i',
            'contact_no' => 'required|regex:/^(?!0+)[+]?[0-9]+$/',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ];
    }
}
