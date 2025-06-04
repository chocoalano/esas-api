<?php
namespace App\FcmNotification;

use App\Events\MessageSent;
use App\Models\CoreApp\Notification;
use App\Models\FcmModel;
use App\Support\FcmService;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;

class PermitNotification
{
    protected $fcm;

    public function __construct(FcmService $fcmService)
    {
        $this->fcm = $fcmService;
    }

    public function broadcast_approvals(array $user_target, string $user_apply, string $type)
    {
        try {
            // Ambil token FCM dari database
            $fcmToken = FcmModel::whereIn('user_id', $user_target);
            $token = $fcmToken->pluck('device_token')->toArray();
            $title = "Pemberitahuan Permohonan Izin/Cuti/Dispensasi";
            $body = "Saya $user_apply, ingin mengajukan permohonan $type. Mohon Bapak/Ibu yang bersangkutan untuk memeriksa dan memberi tanggapan untuk saya";
            $data = [
                "actions" => [
                    [
                        "name" => "view",
                        "color" => null,
                        "event" => null,
                        "eventData" => [],
                        "dispatchDirection" => false,
                        "dispatchToComponent" => null,
                        "extraAttributes" => [],
                        "icon" => null,
                        "iconPosition" => "before",
                        "iconSize" => null,
                        "isOutlined" => false,
                        "isDisabled" => false,
                        "label" => "View",
                        "shouldClose" => false,
                        "shouldMarkAsRead" => true,
                        "shouldMarkAsUnread" => false,
                        "shouldOpenUrlInNewTab" => false,
                        "size" => "sm",
                        "tooltip" => null,
                        "url" => "",
                        "view" => "filament-actions::button-action",
                    ],
                ],
                "body" => $body,
                "color" => null,
                "duration" => "persistent",
                "icon" => "heroicon-o-check-circle",
                "iconColor" => "success",
                "status" => "success",
                "title" => "Informasi permintaan",
                "view" => "filament-notifications::notification",
                "viewData" => [],
                "format" => "filament",
            ];
            foreach ($user_target as $user_target_id) {
                $notifsave = new Notification();
                $notifsave->type = "Filament\Notifications\DatabaseNotification";
                $notifsave->notifiable_type = "App\Models\User";
                $notifsave->notifiable_id = $user_target_id;
                $notifsave->data = $data;
                $notifsave->save();
            }
            broadcast(new MessageSent($body, $user_apply, $user_target, '/'));
            $this->fcm->sendToMultiple($token, $title, $body, $data);
        } catch (MessagingException | FirebaseException $e) {
            throw new \Exception($e->getMessage(), 1);
        }
    }
    public function broadcast_user_apply(int $user_target, string $user_approve, string $type)
    {
        try {
            // Ambil token FCM dari database
            $fcmToken = FcmModel::where('user_id', $user_target)->first();
            $token = $fcmToken->device_token;
            $title = "Pemberitahuan Permohonan Izin/Cuti/Dispensasi";
            $body = "Saya $user_approve, telah menindak permohonan $type milik anda. Silahkan periksa sekarang.";
            $data = [
                "actions" => [
                    [
                        "name" => "view",
                        "color" => null,
                        "event" => null,
                        "eventData" => [],
                        "dispatchDirection" => false,
                        "dispatchToComponent" => null,
                        "extraAttributes" => [],
                        "icon" => null,
                        "iconPosition" => "before",
                        "iconSize" => null,
                        "isOutlined" => false,
                        "isDisabled" => false,
                        "label" => "View",
                        "shouldClose" => false,
                        "shouldMarkAsRead" => true,
                        "shouldMarkAsUnread" => false,
                        "shouldOpenUrlInNewTab" => false,
                        "size" => "sm",
                        "tooltip" => null,
                        "url" => "",
                        "view" => "filament-actions::button-action",
                    ],
                ],
                "body" => $body,
                "color" => null,
                "duration" => "persistent",
                "icon" => "heroicon-o-check-circle",
                "iconColor" => "success",
                "status" => "success",
                "title" => "Informasi permintaan",
                "view" => "filament-notifications::notification",
                "viewData" => [],
                "format" => "filament",
            ];
            $notifsave = new Notification();
            $notifsave->type = "Filament\Notifications\DatabaseNotification";
            $notifsave->notifiable_type = "App\Models\User";
            $notifsave->notifiable_id = $user_target;
            $notifsave->data = $data;
            $notifsave->save();
            broadcast(new MessageSent($body, $user_approve, $user_target, route('filament.app.resources.permits.index')));
            $this->fcm->send($token, $title, $body, $data);
        } catch (MessagingException | FirebaseException $e) {
            throw new \Exception($e->getMessage(), 1);
        }
    }
}
