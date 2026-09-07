<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request; 
class PhoneValidation implements ValidationRule
{
    protected $request;
    protected $language;

    public function __construct(Request $request,$language)
    {
        $this->request = $request;
        $this->language = $language;
    }
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Ensure the phone number does not start with '0' and does not contain '.' or '-'
        if (!preg_match('/^[^0][\d]*$/i', $value) || preg_match('/[\.,-]/', $value)) {
            $preferredLanguage = $this->request->input('language') ?? $this->language;

            $message = $preferredLanguage == 'english'
                ? "Phone number must not contain '-' or '.' and cannot start with '0'."
                : "O número de telefone não deve conter '-' ou '.' e não pode começar com '0'.";

            $fail($message);
        }
    }
}
