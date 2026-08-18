<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json(['error' => 'Agent token required.'], 401);
        }

        $user = User::where('agent_token', hash('sha256', $token))->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid agent token.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['error' => 'Account is inactive.'], 403);
        }

        // Attach the resolved user so controllers can call $request->agentUser()
        $request->attributes->set('_agent_user', $user);
        $request->macro('agentUser', fn () => $request->attributes->get('_agent_user'));

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }
}
