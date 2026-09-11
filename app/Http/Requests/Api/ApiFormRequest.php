<?php

namespace App\Http\Requests\Api;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base for mobile API form requests.
 *
 * Validation failures render through the shared envelope rather than each
 * request inventing its own shape, and authorization failures return the
 * envelope too instead of Laravel's HTML 403 page.
 *
 * Subclasses that must preserve an unusual legacy status code for the shipped
 * app override $failureStatus.
 */
abstract class ApiFormRequest extends FormRequest
{
    /**
     * HTTP status for a validation failure. 422 matches both Laravel's own
     * behaviour for Accept: application/json clients and the exception handler.
     */
    protected int $failureStatus = 422;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The caller's requested language, used for localised messages.
     */
    public function language(): string
    {
        return $this->input('language') === 'chinese' ? 'chinese' : 'english';
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error(
                $validator->errors()->first(),
                $this->failureStatus,
                $validator->errors()->toArray(),
                null,
                // Laravel's native key, kept for v1 clients that read it.
                ['errors' => $validator->errors()->toArray()]
            )
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            ApiResponse::error('This action is unauthorized.', 403)
        );
    }
}
