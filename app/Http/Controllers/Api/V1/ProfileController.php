<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /** GET /api/v1/me */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('department:id,name', 'manager:id,name');

        return response()->json(['data' => [
            'id'                   => $user->id,
            'name'                 => $user->name,
            'email'                => $user->email,
            'phone'                => $user->phone,
            'role'                 => $user->role,
            'department'           => $user->department?->name,
            'manager'              => $user->manager?->name,
            'two_factor_enabled'   => $user->hasTwoFactorEnabled(),
            'token_abilities'      => $request->user()->currentAccessToken()?->abilities ?? [],
        ]]);
    }

    /** GET /api/v1/announcements */
    public function announcements(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = Announcement::where('is_active', true)
            ->where(fn ($q) => $q->where('audience', 'all')->orWhere('audience', $user->role))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'title', 'body', 'created_at']);

        return response()->json(['data' => $items->map(fn ($a) => [
            'id'         => $a->id,
            'title'      => $a->title,
            'body'       => $a->body,
            'created_at' => $a->created_at?->toIso8601String(),
        ])]);
    }
}
