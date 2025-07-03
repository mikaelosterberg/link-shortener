<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Notifications\WelcomeUser;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        $formData = $this->form->getState();

        // Assign the selected roles
        if (isset($formData['roles'])) {
            $user->syncRoles($formData['roles']);
        }

        // Send welcome email if requested
        if (isset($formData['send_welcome_email']) && $formData['send_welcome_email']) {
            try {
                // Refresh the user from database to ensure we have the correct data
                $user->refresh();
                $user->notify(new WelcomeUser);

                Notification::make()
                    ->title(__('Welcome email sent'))
                    ->body(__('Login details have been sent to :email', ['email' => $user->email]))
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title(__('Failed to send welcome email'))
                    ->body(__('The user was created but the welcome email could not be sent.'))
                    ->danger()
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
