<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRecommendationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Set to true to allow anyone to make this request.
        // You can add your authorization logic here if needed.
        return true;
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $imageRule = $this->isMethod('post') ? 'required' : 'nullable';

        return [
            'title' => 'required|string|min:3|max:100|not_regex:/<[^>]*>/u',
            'description' => 'required|string|min:5|',
            // 'color' => 'required|string',
            'url' => 'required|url',
            'image' => [$imageRule, 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'], // 2MB Max
            'color' => 'required|string',
            'color_title' => 'required|string',
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
            'name.required' => 'The resource title is required.',
            'description.required' => 'The resource description is required.',
            'image.required' => 'Please upload an image for the resource.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'Only JPEG, PNG, JPG, GIF, and SVG images are allowed.',
            'image.max' => 'The image may not be greater than 2MB.',
        ];
    }
}
