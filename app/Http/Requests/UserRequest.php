<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
class UserRequest extends FormRequest
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
        if($this->user_type == 2){

            if (request()->isMethod('put')) {
                $rules = [
                'name'   => 'required|max:30',
                'email'  => 'required |regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/|email|unique:users,email,'.$this->user_id,
                'designation' => 'sometimes|max:15',
                'photo'  => 'sometimes|image|mimes:jpeg,png,jpg',
                'code' => 'required',
                'phone'   => 'nullable|integer|digits_between:8,15',
                'address'   => 'sometimes|max:200',
                ];
            } else{
                $rules = [
                   'name'   => 'required|max:30',
                    'email'  => 'required |regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/ | unique:users,email|email',
                    'designation' => 'sometimes|max:15',
                    'photo'  => 'sometimes|image|mimes:jpeg,png,jpg',
                    'code' => 'required',
                    'phone'   => 'nullable|integer|digits_between:8,15',
                    'address'   => 'sometimes|max:200',
                ];
            }
            return $rules;
        }
        else{
            if (request()->isMethod('put')) {
                $rules = [

                'name'   => 'nullable',
                'email'  => 'required |regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/|email|unique:users,email,'.$this->user_id,
                'code' => 'nullable',
                'phone'   => 'nullable',
                ];
            } else{
                $rules = [
                    'name'   => 'nullable',
                    'email'  => 'required |regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/ | unique:users,email|email',
                    'code' => 'nullable',
                    'phone'   => 'nullable',
                ];
            }
            return $rules;

            
        }
    }

    public function messages(): array
    {
        return [
            // 'nric_id.required' => 'The Username field is required.',
            // 'nric_id.unique' => 'The Username already exists.',
            // 'nric_id.max' => 'The Usename must not be greater than 15 characters.',
            // 'nric_id.alpha_num' => 'The Username must only contain letters and numbers.'
        ];
    }

   public function withValidator($validator){
        
        $useremail = $this->email;
        $email = $useremail;
        $validator->after(function ($validator) use($email)
        {  
            if(isset($this->user_id)){
                    return;
            }
            else{
                $user = User::where('email',$email)->first();
                  if(!empty($user)){
                      $validator->errors()->add('email', 'Email address already taken.');
                   }
                   return;
            }
          
          
           
       });
        
    }

}
