<?php

declare(strict_types=1);

namespace Orboto\Mail\Laravel;

use Illuminate\Notifications\Notification;
use Orboto\Mail\Dto\SendResult;
use Orboto\Mail\OrbotoMail;

/**
 * Laravel Notification Channel.
 *
 * Register in `config/notifications.php` or via the user's
 * `routeNotificationFor()` method. Notifications implement
 * `toOrbotoMail($notifiable)` and return an array shaped like
 * `OrbotoMail::send()`'s input.
 *
 * Example:
 *
 *   class Welcome extends Notification {
 *     public function via($notifiable): array {
 *       return [\Orboto\Mail\Laravel\OrbotoMailChannel::class];
 *     }
 *     public function toOrbotoMail($notifiable): array {
 *       return [
 *         'from'    => 'noreply@acme.orbo.to',
 *         'to'      => $notifiable->email,
 *         'subject' => 'Welcome to ACME',
 *         'html'    => view('mail.welcome', ['user' => $notifiable])->render(),
 *       ];
 *     }
 *   }
 */
final class OrbotoMailChannel
{
    public function __construct(private readonly OrbotoMail $mail)
    {
    }

    public function send(mixed $notifiable, Notification $notification): SendResult
    {
        /** @phpstan-ignore-next-line — notification provides toOrbotoMail() */
        $message = $notification->toOrbotoMail($notifiable);
        if (!is_array($message)) {
            throw new \InvalidArgumentException(
                'OrbotoMailChannel: notification toOrbotoMail() must return an array shaped like OrbotoMail::send() input.',
            );
        }
        return $this->mail->send($message);
    }
}
