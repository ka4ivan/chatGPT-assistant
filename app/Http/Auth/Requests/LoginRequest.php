<?php

namespace App\Http\Auth\Requests;

use App\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email'    => 'required|email:strict',
            'password' => 'required|string',
        ];
    }
}
