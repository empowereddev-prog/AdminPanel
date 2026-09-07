<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MeetTeamRequest extends FormRequest
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
        $imageRule = $this->isMethod('post') ? 'required' : 'nullable';

        return [
            'title' => 'required|string|min:3|max:100|not_regex:/<[^>]*>/u',
            'profession' => 'required|string|min:3',
            'description' => 'required|string|min:5',
            'designation' => 'nullable|string|min:3',
            // 'color' => 'required|string',
            'url' => 'nullable|url',
            'image' => [$imageRule, 'image', 'mimes:jpeg,png,jpg,gif,svg'], // 2MB Max
        ];
    }

    /**
     * Get the custom error messages for validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The meet team title is required.',
            'profession.required' => 'The meet team profession is required.',
            'description.required' => 'The meet team description cannot be empty.',
            'image.required' => 'Please upload an image for the meet team.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'Only JPEG, PNG, JPG, GIF, and SVG images are allowed.',
            'image.max' => 'The image may not be greater than 2MB.',
        ];
    }
}
