<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->noContent();
    }
}
