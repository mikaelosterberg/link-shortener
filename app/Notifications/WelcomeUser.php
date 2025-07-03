<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class WelcomeUser extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $resetUrl = $this->createPasswordResetUrl($notifiable);

        return (new MailMessage)
            ->subject(__('Welcome to :app', ['app' => config('app.name')]))
            ->greeting(__('Hello :name!', ['name' => $notifiable->name]))
            ->line(__('Your account has been created.'))
            ->line(__('Username: :email', ['email' => $notifiable->email]))
            ->line(__('For security, please set your own password:'))
            ->action(__('Set Password'), $resetUrl)
            ->line(__('This link will expire in 24 hours.'))
            ->line(__('If you have any issues, please contact your administrator.'));
    }

    /**
     * Create a password reset URL for the user.
     */
    protected function createPasswordResetUrl(User $user): string
    {
        $token = app('auth.password.broker')->createToken($user);

        return URL::temporarySignedRoute(
            'filament.admin.auth.password-reset.reset',
            now()->addHours(24),
            [
                'token' => $token,
                'email' => $user->email,
            ]
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
