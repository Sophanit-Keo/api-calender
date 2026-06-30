<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if ($plainToken === null || $plainToken === '') {
            throw new AuthenticationException('Unauthenticated.', ['api']);
        }

        $apiToken = ApiToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if ($apiToken === null || $apiToken->user === null) {
            throw new AuthenticationException('Unauthenticated.', ['api']);
        }

        $apiToken->forceFill([
            'last_used_at' => now(),
        ])->save();

        $user = $apiToken->user;
        $user->setRelation('currentApiToken', $apiToken);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
