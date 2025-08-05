<?php

namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper;
use App\Models\UserModel;
use App\Models\NotificationModel;
use App\Libraries\AuthService;
use App\Models\UsersubModel;

class AutoNotification extends ResourceController
{
    protected $notificationModel;
    protected $authService;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->input = \Config\Services::request();
        $this->notificationModel = new NotificationModel();
        $this->UserModel = new UserModel();
        $this->usersubModel = new UsersubModel();
        $this->authService = new AuthService();
    }
public function sendAutoNotification($userId, $eventType)
{
    $notificationModel = new NotificationModel();

    $eventMap = [
        'profile_updated' => [
            'title'   => 'Profile Updated',
            'content' => 'Your profile has been successfully updated.',
        ],
        
        'password_changed' => [
            'title'   => 'Password Changed',
            'content' => 'Your password has been successfully changed',
        ],
        
        'support_issue_created' => [
            'title'   => 'Support Request Received',
            'content' => 'We have received your issue . Our support team will get back to you shortly.',
        ],
        
        'subscription_started' => [
            'title'   => 'Subscription Activated',
            'content' => 'You’ve successfully subscribed. Enjoy unlimited access to premium content!',
        ],
        
        'subscription_expired' => [
            'title'   => 'Subscription Ended',
            'content' => 'Your subscription has ended. Renew now to resume streaming without interruptions.',
        ],
        'account_created' => [
            'title'   => 'Welcome to Cukkuboo',
            'content' => 'Hi {{user_name}}, your account has been successfully created. Start watching now!',
        ],
        
    ];

    if (!isset($eventMap[$eventType])) {
        return; 
    }

    $data = $eventMap[$eventType];
    $data['user_id'] = $userId;
    $data['type'] = 'individual';
    $data['target'] = 'user';
    $data['is_scheduled'] = false;
    $data['scheduled_time'] = null;
    $data['created_by'] = $userId;
    $data['created_on'] = date('Y-m-d H:i:s');
    $data['status'] = 1;

    $notificationModel->insert($data);
}
}
