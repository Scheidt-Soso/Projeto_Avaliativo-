<?php

namespace App\Http\Requests;

use App\Models\Aventureiro;
use App\Support\RecordRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', 'max:50', Rule::unique(Aventureiro::class, 'nickname')],
            'classe' => ['required', 'string', 'max:50'],
            'senha' => RecordRules::password(),
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.required' => 'Escolha seu nickname.',
            'nickname.unique' => 'Este nickname já está em uso.',
            'classe.required' => 'Escolha sua classe.',
            'senha.required' => 'Escolha sua senha.',
            'senha.min' => 'A senha precisa ter pelo menos 6 caracteres.',
            'senha.confirmed' => 'A confirmação da senha não confere.',
        ];
    }
}
