<?php

namespace App\Http\Auth\Controllers;

use App\Http\Auth\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

final class RegisterController extends Controller
{
    public function register(RegisterRequest $request)
    {
        /** @var User $user */
        $user = User::create($request->validated());

        event(new Registered($user));

        return response()->json([
            'message' => trans('auth.register'),
            'data' => $this->authResource($user),
        ]);
    }
}
