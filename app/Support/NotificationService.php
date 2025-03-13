<?php

namespace App\Support;

use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Log;

class NotificationService
{
    public static function sendNotification(string $title, string $body, string $routeName = null, ?int $recipientId = null): bool // Ubah return type menjadi bool
    {
        try {
            // Retrieve the authenticated user (sender)
            $sender = Auth::user(); // Ambil user yang sedang login
            if (!$sender) {
                Log::warning("Sender user is not authenticated in NotificationService.");
                return false; // Return false jika sender tidak ditemukan
            }

            // Retrieve the recipient user
            $recipient = User::find($recipientId);

            if (!$recipient) {
                Log::warning("Recipient user not found: {$recipientId}");
                return false; // Return false jika recipient tidak ditemukan
            }

            // Generate the URL for the notification
            $url = $routeName && Route::has($routeName)
                ? route($routeName)
                : (filter_var($routeName, FILTER_VALIDATE_URL) ? $routeName : '');

            // Dispatch a broadcast event
            broadcast(new MessageSent($body, $sender->id, $recipientId, $url));

            // Send a database notification
            $recipient->notify(
                FilamentNotification::make()
                    ->title($title)
                    ->success()
                    ->body($body)
                    ->actions([
                        Action::make('view')
                            ->button()
                            ->url($url)
                            ->markAsRead(),
                    ])
                    ->toDatabase()
            );

            // Send a broadcast notification
            $recipient->notify(
                FilamentNotification::make()
                    ->title($title)
                    ->success()
                    ->body($body)
                    ->actions([
                        Action::make('view')
                            ->button()
                            ->url($url)
                            ->markAsRead(),
                    ])
                    ->toBroadcast()
            );

            return true; // Return true jika notifikasi berhasil dikirim

        } catch (\Throwable $e) { // Tangkap Throwable untuk semua jenis exception
            Log::error("Error sending notification: " . $e->getMessage());
            return false; // Return false jika terjadi error
        }
    }
}
