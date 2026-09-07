<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTeacherProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->user_role_id == 5;
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'name'           => 'required|string|min:3|max:100',
            'username'       => 'required|string|max:100|unique:users,username,' . $userId . ',id',
            'email'          => 'required|email|max:255|unique:users,email,' . $userId . ',id',
            'country_code'   => 'required|string|max:10',
            'phone_no'       => 'required|numeric|digits_between:8,15|unique:users,phone_no,' . $userId . ',id',

            'subject'        => 'required|array|min:1',
            'subject.*'      => 'required|string',
            'experience'     => 'required|numeric|min:0|max:99.9',
            'qualification'  => 'required|array|min:1',
            'qualification.*'=> 'required|string',
            'language'       => 'nullable|string|in:english,chinese'
        ];
    }

    /**
     * Clear payload strings from HTML injection attempts before running validation rules
     */
    protected function prepareForValidation()
    {
        $subjects = $this->subject;
        if (is_string($subjects)) {
            $subjects = array_map('trim', explode(',', $subjects));
        }

        $qualifications = $this->qualification;
        if (is_string($qualifications)) {
            $qualifications = array_map('trim', explode(',', $qualifications));
        }

        $this->merge([
            'name'          => strip_tags($this->name),
            'username'      => strip_tags($this->username),
            'subject'       => $subjects,
            'qualification' => $qualifications,
            'language'      => $this->language ?? 'english'
        ]);
    }

    /**
     * Override default redirect to provide API-compliant error messaging formats
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => $validator->errors()->first(),
            'data'    => (object) []
        ], 200));
    }
}
