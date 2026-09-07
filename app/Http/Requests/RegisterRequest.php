<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
     * @return array<string,r \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */   
    
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:50',
            'email' => [
            'required',
            'email',
            // 'unique:users,email,NULL,id,deleted_at,NULL',
            'regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix'
            ],
            'phone_no' => 'required|string|max:15',
            'password' => 'required|string|min:8',
            'user_type' => 'required',
            'status' => 'in:active, inactive, block'
        ];
    }
}
