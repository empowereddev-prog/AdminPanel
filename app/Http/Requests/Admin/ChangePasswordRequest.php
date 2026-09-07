<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'current_password' => 'required|min:8|max:15',
            'password' => 'required|min:8|max:15|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9]).{8,15}$/',
            'confirm_password' => 'required|same:password',
        ];
    }
    public function messages()
    {
        return [
            'current_password.required' => "Current Password is required.",
            'password.required' => "New Password is required.",
            'confirm_password.required' => "Confirm Password is required.",
            'password.regex' => "The password must be between 8 and 15 characters long and must contain at least one uppercase letter, one lowercase letter, and one numeric digit.",
             'confirm_password.same' => 'The new password and confirm password must be the same.',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->password === $this->current_password) {
                $validator->errors()->add('password', 'The new password must be different from the current password.');
            }
        });
    }
}
