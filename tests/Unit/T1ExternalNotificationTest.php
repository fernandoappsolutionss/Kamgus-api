<?php

namespace Tests\Unit;

use App\Classes\ResendClient;
use App\Jobs\NotifyServiceStatusByEmail;
use App\Notifications\K_FirebaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class T1ExternalNotificationTest extends TestCase
{
    public function testGlobalPushHelpersReturnBeforeNetworkWhenDisabled(): void
    {
        config(['services.disable_external_notifications' => true]);

        $this->assertNull(notifyToDriver('token', 'title', 'body'));
        $this->assertNull(notifyToCustomer('token', 'title', 'body'));
    }

    public function testFirebaseChannelReturnsBeforeBuildingExternalClientsWhenDisabled(): void
    {
        config(['services.disable_external_notifications' => true]);

        $notification = new class extends Notification {
            public function toFirebase($notifiable): array
            {
                throw new \RuntimeException('Firebase payload should not be built when disabled.');
            }
        };

        $this->assertSame([], (new K_FirebaseChannel())->send(new \stdClass(), $notification));
    }

    public function testResendClientReturnsDisabledResultBeforeHttpWhenDisabled(): void
    {
        config(['services.disable_external_notifications' => true]);
        Http::fake(function () {
            throw new \RuntimeException('HTTP should not be called when disabled.');
        });

        $result = ResendClient::getInstance()->send('nobody@example.test', 'Subject', '<p>Body</p>');

        $this->assertSame([
            'ok' => true,
            'id' => null,
            'error' => null,
            'status' => 0,
            'disabled' => true,
        ], $result);
    }

    public function testEmailNotificationJobDoesNotSendMailWhenDisabled(): void
    {
        config(['services.disable_external_notifications' => true]);
        Mail::fake();

        (new NotifyServiceStatusByEmail(['driver@example.test'], 1, 'Title', 'Body'))->handle();

        Mail::assertNothingSent();
    }

    public function testMailConfigUsesArrayTransportWhenExternalNotificationsAreDisabled(): void
    {
        $source = file_get_contents(base_path('config/mail.php'));

        $this->assertStringContainsString('DISABLE_EXTERNAL_NOTIFICATIONS', $source);
        $this->assertStringContainsString("? 'array'", $source);
    }
}
