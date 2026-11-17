<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Personal API tokens, for a mobile app or a script acting as the user.
 */
class ApiTokenController extends Controller
{
    public const MAX_TOKENS = 10;

    /** Lifetimes offered, in days (null = never expires). */
    public const LIFETIMES = [30 => '30 days', 90 => '90 days', 365 => '1 year', 0 => 'Never'];

    public const ABILITIES = [
        'read'  => 'Read: see my profile, attendance, leave, payslips and announcements',
        'write' => 'Write: check in and out, request and cancel leave',
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request)
    {
        $tokens = $request->user()->tokens()->orderByDesc('id')->get();

        return view('profile.api-tokens', [
            'tokens'     => $tokens,
            'newToken'   => session('new_token'), // shown once, straight after creation
            'lifetimes'  => self::LIFETIMES,
            'abilities'  => self::ABILITIES,
            'maxTokens'  => self::MAX_TOKENS,
            'apiBase'    => url('/api/v1'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:60',
            'abilities'   => 'required|array|min:1',
            'abilities.*' => [Rule::in(array_keys(self::ABILITIES))],
            'lifetime'    => ['required', 'integer', Rule::in(array_keys(self::LIFETIMES))],
        ], [
            'abilities.required' => 'Choose at least one permission for the token.',
        ]);

        $user = $request->user();

        if ($user->tokens()->count() >= self::MAX_TOKENS) {
            return back()->withInput()->with('error', 'You have reached the limit of ' . self::MAX_TOKENS . ' tokens. Revoke one you no longer use first.');
        }

        $expiresAt = (int) $data['lifetime'] > 0 ? now()->addDays((int) $data['lifetime']) : null;
        $token     = $user->createToken($data['name'], array_values($data['abilities']), $expiresAt);

        $this->audit->record('api_token_created', $user, [], [
            'name'       => $data['name'],
            'abilities'  => implode(', ', $data['abilities']),
            'expires_at' => $expiresAt?->toDateString() ?? 'never',
        ], "API token \"{$data['name']}\" created");

        return redirect()->route('profile.api-tokens')
            ->with('success', 'Token created. Copy it now — it will not be shown again.')
            ->with('new_token', $token->plainTextToken);
    }

    public function destroy(Request $request, int $tokenId)
    {
        $token = $request->user()->tokens()->whereKey($tokenId)->firstOrFail();
        $token->delete();

        $this->audit->record('api_token_revoked', $request->user(), [], [], "API token \"{$token->name}\" revoked");

        return back()->with('success', "Token \"{$token->name}\" revoked. Anything using it will stop working.");
    }
}
