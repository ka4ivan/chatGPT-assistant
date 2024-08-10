<?php

namespace App\Http\Auth\Controllers;

use App\Http\Auth\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class LoginController extends Controller
{
    /**
     * LoginController constructor.
     */
    public function __construct()
    {
       $this->middleware('auth:sanctum', ['except' => ['login']]);
    }

    public function login(LoginRequest $request)
    {
        /** @var User $user */
        $user = User::where($this->conditions($request))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        return response()->json([
            'message' => trans('auth.login'),
            'data' => $this->authResource($user)
        ]);
    }

    public function logout(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => trans('auth.logout'),
        ]);
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function conditions(Request $request): array
    {
        return [
            'email' => $request->email,
        ];
    }
}
