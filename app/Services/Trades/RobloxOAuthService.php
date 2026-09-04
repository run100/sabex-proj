<?php

namespace App\Services\Trades;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RobloxOAuthService
{
    public function configured(): bool
    {
        return trim((string) config('services.roblox.client_id')) !== ''
            && trim((string) config('services.roblox.client_secret')) !== '';
    }

    /**
     * @return array{url: string, state: string, verifier: string, nonce: string}
     */
    public function authorization(): array
    {
        $state = Str::random(40);
        $nonce = Str::random(40);
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $query = http_build_query([
            'client_id' => config('services.roblox.client_id'),
            'redirect_uri' => config('services.roblox.redirect'),
            'response_type' => 'code',
            'scope' => 'openid profile',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]);

        return [
            'url' => rtrim((string) config('services.roblox.authorize'), '?').'?'.$query,
            'state' => $state,
            'verifier' => $verifier,
            'nonce' => $nonce,
        ];
    }

    /**
     * @return array{sub: string, username: string, display_name: string, avatar_url: string}
     */
    public function userFromCallback(string $code, string $verifier): array
    {
        $token = Http::asForm()->post((string) config('services.roblox.token'), [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('services.roblox.redirect'),
            'client_id' => config('services.roblox.client_id'),
            'client_secret' => config('services.roblox.client_secret'),
            'code_verifier' => $verifier,
        ])->throw()->json();

        $access = (string) ($token['access_token'] ?? '');
        abort_if($access === '', 404);

        $info = Http::withToken($access)
            ->get((string) config('services.roblox.userinfo'))
            ->throw()
            ->json();

        $sub = trim((string) ($info['sub'] ?? ''));
        abort_if($sub === '', 404);

        return [
            'sub' => $sub,
            'username' => (string) ($info['preferred_username'] ?? $info['nickname'] ?? $info['name'] ?? 'roblox'),
            'display_name' => (string) ($info['name'] ?? $info['nickname'] ?? $info['preferred_username'] ?? 'Roblox user'),
            'avatar_url' => (string) ($info['picture'] ?? ''),
        ];
    }
}
