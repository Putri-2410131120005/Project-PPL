<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    public function vapidKey(): JsonResponse
    {
        abort_unless(config('push.vapid.publicKey'), 503, 'Push notification belum dikonfigurasi.');

        return response()->json(['publicKey' => config('push.vapid.publicKey')]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser();
        abort_unless($user, 401);

        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);

        PushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'id_pengguna' => $user->getKey(),
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
            ],
        );

        return response()->json(['message' => 'Notifikasi perangkat berhasil diaktifkan.']);
    }

    private function authenticatedUser(): ?Pengguna
    {
        foreach (['parent', 'staff', 'guru_mapel'] as $guard) {
            $user = Auth::guard($guard)->user();
            if ($user instanceof Pengguna) return $user;
        }

        return null;
    }
}
