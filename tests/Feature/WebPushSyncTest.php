<?php

namespace Tests\Feature;

use App\Jobs\SendPushNotification;
use App\Models\Pengguna;
use App\Models\PushSubscription;
use App\Models\Role;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\WebPush;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class WebPushSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_queue_completes_a_successful_push_without_throwing(): void
    {
        $user = $this->userWithSubscription();
        $report = Mockery::mock(MessageSentReport::class);
        $report->shouldReceive('isSuccess')->once()->andReturnTrue();
        $client = Mockery::mock(WebPush::class);
        $client->shouldReceive('queueNotification')->once();
        $client->shouldReceive('flush')->once()->andReturn((function () use ($report) {
            yield $report;
        })());

        $this->dispatchWith($client, $user);
        $this->assertDatabaseHas('push_subscriptions', ['id_pengguna' => $user->getKey()]);
    }

    public function test_push_provider_failure_does_not_break_the_sync_job(): void
    {
        $user = $this->userWithSubscription('failure');
        $client = Mockery::mock(WebPush::class);
        $client->shouldReceive('queueNotification')->andThrow(new RuntimeException('provider unavailable'));

        $this->dispatchWith($client, $user);
        $this->assertDatabaseHas('push_subscriptions', ['id_pengguna' => $user->getKey()]);
    }

    public function test_push_timeout_does_not_break_the_sync_job_and_has_a_bound(): void
    {
        config()->set('push.timeout', 1);
        $user = $this->userWithSubscription('timeout');
        $client = Mockery::mock(WebPush::class);
        $client->shouldReceive('queueNotification')->once();
        $client->shouldReceive('flush')->andThrow(new RuntimeException('timeout'));

        $this->dispatchWith($client, $user);
        $this->assertSame(1, config('push.timeout'));
        $this->assertDatabaseHas('push_subscriptions', ['id_pengguna' => $user->getKey()]);
    }

    private function dispatchWith(WebPush $client, Pengguna $user): void
    {
        config()->set('push.vapid', [
            'subject' => 'mailto:test@example.test',
            'publicKey' => 'public',
            'privateKey' => 'private',
        ]);
        config()->set('queue.default', 'sync');
        app()->instance(WebPushService::class, new WebPushService(
            fn (array $vapid, int $timeout): WebPush => $client,
        ));

        SendPushNotification::dispatch([$user->getKey()], ['title' => 'Test']);
    }

    private function userWithSubscription(string $suffix = 'success'): Pengguna
    {
        $role = Role::query()->create(['nama_role' => 'GPK']);
        $user = Pengguna::query()->create([
            'id_role' => $role->getKey(),
            'nama' => 'GPK Push '.$suffix,
            'email' => 'push-'.$suffix.'@example.test',
            'password' => bcrypt('testing-password'),
            'status_akun' => 'Aktif',
        ]);
        PushSubscription::query()->create([
            'id_pengguna' => $user->getKey(),
            'endpoint' => 'https://push.example.test/'.$suffix,
            'endpoint_hash' => hash('sha256', 'https://push.example.test/'.$suffix),
            'public_key' => 'public-key',
            'auth_token' => 'auth-token',
        ]);

        return $user;
    }
}
