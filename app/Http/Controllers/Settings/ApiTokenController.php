<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Auth\IssueApiToken;
use App\Actions\Auth\RevokeApiToken;
use App\Data\Auth\RevokeTokenData;
use App\Enums\TokenPreset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreApiTokenRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiTokenController extends Controller
{
    public function index(Request $request)
    {
        $tokens = $request->user()->tokens()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'abilities', 'last_used_at', 'created_at']);

        return Inertia::render('settings/api-tokens', [
            'tokens' => $tokens,
            'presets' => collect(TokenPreset::cases())->map(fn (TokenPreset $p) => [
                'value' => $p->value,
                'label' => $p->label(),
                'abilities' => $p->abilities(),
            ])->values(),
        ]);
    }

    public function store(StoreApiTokenRequest $request, IssueApiToken $action)
    {
        $token = $action->handle($request->toData());

        Inertia::flash('newToken', [
            'name' => $token->accessToken->name,
            'plainTextToken' => $token->plainTextToken,
        ]);

        return redirect()->route('api-tokens.index');
    }

    public function destroy(Request $request, int $token, RevokeApiToken $action)
    {
        $action->handle(new RevokeTokenData($request->user(), $token));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Token revoked.']);

        return redirect()->route('api-tokens.index');
    }
}
