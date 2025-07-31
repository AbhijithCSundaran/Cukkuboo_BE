<?php

namespace App\Libraries;

use App\Models\NotificationModel;
use App\Models\UserNotificationModel;
use App\Helpers\AuthHelper; 
use App\Libraries\AuthService;
class NotificationService
{
    protected $notificationModel;
    // protected $userNotificationModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
        // $this->userNotificationModel = new UserNotificationModel();
        $this->authService = new AuthService();
    }

    // Automatically send notification based on user event
    public function sendAutoNotification($userId, $eventType)
    {
        $title = '';
        $message = '';

        switch ($eventType) {
            case 'registration':
                $title = 'Welcome!';
                $message = 'Thanks for registering. Start exploring the app now.';
                break;

            case 'subscription_start':
                $title = 'Subscription Started';
                $message = 'Your subscription is now active. Enjoy premium features!';
                break;

            case 'subscription_expiry':
                $title = 'Subscription Expired';
                $message = 'Your subscription has expired. Please renew it to continue.';
                break;

            case 'password_changed':
                $title = 'Password Changed';
                $message = 'You successfully changed your password.';
                break;

            case 'profile_updated':
                $title = 'Profile Updated';
                $message = 'Your profile information was updated.';
                break;

            default:
                return false;
        }

        return $this->sendToUser($userId, $title, $message);
    }

    // Helper to send notification to a specific user
    public function sendToUser($userId, $title, $message)
    {
        $notificationData = [
            'user_id'       => $userId,
            'title'         => $title,
            'content'       => $message,
            'type_enum'     => 'individual',  // or enum int like 1 if you prefer
            'target'        => 'user',
            'status'        => 1,
            'is_scheduled'  => 0,
            'created_by'    => $userId,
            'created_on'    => date('Y-m-d H:i:s')
        ];
        $this->notificationModel->insert($notificationData);
        return [ 
        'title' => $title,
        'message' => $message
    ];
}
}