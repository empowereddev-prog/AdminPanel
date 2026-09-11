<?php

namespace App\Http\Requests;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateTeacherProfileRequest extends ApiFormRequest
{
    /**
     * This endpoint has always answered a validation failure with HTTP 200, so
     * the shipped app is built around it. Kept as-is; v2 clients get the 422
     * from the shared handler path once this endpoint is migrated in Phase 2.
     */
    protected int $failureStatus = 200;

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
}
