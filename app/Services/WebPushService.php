<?php

namespace App\Services;

use App\Models\PushSubscription;
use Closure;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class WebPushService
{
    public function __construct(private readonly ?Closure $clientFactory = null) {}

    public function sendForUsers(iterable $userIds, array $payload): void
    {
        $vapid = config('push.vapid');
        if (! $vapid['subject'] || ! $vapid['publicKey'] || ! $vapid['privateKey']) return;

        $subscriptions = PushSubscription::query()->whereIn('id_pengguna', $userIds)->get();
        if ($subscriptions->isEmpty()) return;

        try {
            $push = $this->makeClient($vapid);
            foreach ($subscriptions as $subscription) {
                $push->queueNotification(Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => 'aes128gcm',
                ]), json_encode($payload, JSON_THROW_ON_ERROR));
            }

            foreach ($push->flush() as $report) {
                if ($report->isSuccess()) continue;
                $endpoint = $report->getRequest()->getUri()->__toString();
                if (in_array($report->getResponse()?->getStatusCode(), [404, 410], true)) {
                    PushSubscription::query()->where('endpoint', $endpoint)->delete();
                }
                Log::warning('Web push gagal dikirim.', ['endpoint' => $endpoint, 'reason' => $report->getReason()]);
            }
        } catch (Throwable $exception) {
            // Push is auxiliary; a provider/network/VAPID failure must not roll
            // back the primary workflow transaction.
            Log::warning('Web push tidak dapat diproses.', [
                'exception' => $exception::class,
                'reason' => $exception->getMessage(),
            ]);
        }
    }

    private function makeClient(array $vapid): WebPush
    {
        if ($this->clientFactory) {
            return ($this->clientFactory)($vapid, config('push.timeout', 10));
        }

        return new WebPush(
            ['VAPID' => $vapid],
            ['TTL' => 86400, 'urgency' => 'normal'],
            config('push.timeout', 10),
        );
    }
}
