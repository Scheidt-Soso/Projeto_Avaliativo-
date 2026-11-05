<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', 'max:50'],
            'senha' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.required' => 'Informe seu nickname.',
            'senha.required' => 'Informe sua senha.',
        ];
    }
}
