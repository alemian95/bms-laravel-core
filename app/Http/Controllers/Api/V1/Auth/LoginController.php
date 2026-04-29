<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\Auth\InvalidApiCredentialsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Services\Auth\ApiAuthenticator;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, ApiAuthenticator $authenticator): JsonResponse
    {
        try {
            $token = $authenticator->login($request->toData());
        } catch (InvalidApiCredentialsException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }

        return response()->json(['token' => $token->plainTextToken]);
    }
}
