<?php

namespace App\Support;

use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\Topic;
use Kreait\Firebase\Messaging\Condition;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected Messaging $messaging;

    public function __construct()
    {
        // Ambil path credentials dari config/firebase.php
        $credentialsPath = config('firebase.projects.app.credentials');

        // Inisialisasi Firebase Messaging secara manual
        $factory = (new Factory)->withServiceAccount(storage_path($credentialsPath));
        $this->messaging = $factory->createMessaging();
    }

    /**
     * Mengirim notifikasi ke satu perangkat.
     */
    public function send(string $token, string $title, string $body, array $data = []): bool
    {
        if (empty($token)) {
            Log::error("FCM token is empty. Cannot send message.");
            return false;
        }

        try {
            // Pastikan `$data` adalah array asosiatif, jika bukan, ubah ke format yang benar
            if (!is_array($data)) {
                Log::warning("Invalid data format for FCM. Converting to an empty array.");
                $data = [];
            }

            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData(["payload" => json_encode($data)])
                ->withAndroidConfig(AndroidConfig::fromArray([
                    'priority' => 'high',
                    'notification' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ]))
                ->withApnsConfig(ApnsConfig::fromArray([
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                ]));

            $this->messaging->send($message->withChangedTarget('token', $token));

            Log::info("FCM message sent successfully to token: " . $token);
            return true;
        } catch (MessagingException | FirebaseException $e) {
            Log::error("Failed to send FCM message to token: " . $token . ": " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengirim notifikasi ke banyak perangkat.
     */
    public function sendToMultiple(array $tokens, string $title, string $body, array $data = []): array
    {
        if (empty($tokens)) {
            Log::error("FCM tokens array is empty.");
            return [];
        }

        try {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData(["payload" => json_encode($data)]);

            $responses = $this->messaging->sendMulticast($message, $tokens);

            $successCount = $responses->successes()->count();
            $failureCount = $responses->failures()->count();

            Log::info("FCM multicast sent: Success ($successCount), Failures ($failureCount)");

            return [
                'success' => $responses->successes(),
                'failures' => $responses->failures(),
            ];
        } catch (MessagingException | FirebaseException $e) {
            Log::error("Failed to send FCM multicast: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Berlangganan ke topik.
     */
    public function subscribeToTopic(string $token, string $topic): bool
    {
        try {
            $this->messaging->subscribeToTopic(Topic::fromValue($topic), [$token]);
            Log::info("User subscribed to topic: $topic with token: $token");
            return true;
        } catch (MessagingException | FirebaseException $e) {
            Log::error("Failed to subscribe to topic: $topic. Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Berhenti berlangganan dari topik.
     */
    public function unsubscribeFromTopic(string $token, string $topic): bool
    {
        try {
            $this->messaging->unsubscribeFromTopic(Topic::fromValue($topic), [$token]);
            Log::info("User unsubscribed from topic: $topic with token: $token");
            return true;
        } catch (MessagingException | FirebaseException $e) {
            Log::error("Failed to unsubscribe from topic: $topic. Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengirim notifikasi ke topik.
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        try {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message->withChangedTarget('topic', $topic));

            Log::info("FCM message sent successfully to topic: " . $topic);
            return true;
        } catch (MessagingException | FirebaseException $e) {
            Log::error("Failed to send FCM message to topic: " . $topic . ". Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengirim notifikasi ke condition.
     */
    public function sendToCondition(string $condition, string $title, string $body, array $data = []): bool
    {
        try {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message->withChangedTarget('condition', Condition::fromValue($condition)));

            Log::info("FCM message sent successfully to condition: " . $condition);
            return true;
        } catch (MessagingException | FirebaseException $e) {
            Log::error("Failed to send FCM message to condition: " . $condition . ". Error: " . $e->getMessage());
            return false;
        }
    }
}
