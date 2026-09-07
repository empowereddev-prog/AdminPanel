<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GeneralSettingRequest extends FormRequest
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
              'contact_email'   => 'required|regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/',
              'contact_phone'   => 'required|min:10|max:15',
              'contact_address_english' => 'required|min:3',
              'contact_address_simplified_chinese' => 'required|min:3',
              'contact_address_traditional_chinese' => 'required|min:3',
              'ios_app_version' => 'required|numeric',
              'ios_app_url' => 'required|url',
              'MAIL_HOST'=>'required',
              'MAIL_USERNAME' => 'required',
              'MAIL_FROM_ADDRESS' => 'required|regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/',
              'MAIL_FROM_NAME' => 'required',
              'battery_point' => 'required'
          ];
     
    }
}
