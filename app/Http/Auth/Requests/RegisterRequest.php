<?php

namespace App\Http\Auth\Requests;

use App\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function rules()
    {
        $res = [
            'email' => ['required', 'email:strict', 'max:255', 'unique:users,email'],
            'name' => 'sometimes|string|max:255',
            'password' => ['required', 'string', Password::min(8), 'max:255'],
        ];

        if ($this->has('password_confirmation')) {
            $res['password'] = ['required', 'string', Password::min(8), 'max:255', 'confirmed'];
        }

        return $res;
    }
}
