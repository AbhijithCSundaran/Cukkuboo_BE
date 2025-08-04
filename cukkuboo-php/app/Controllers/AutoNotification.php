<?php

namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper;
use App\Models\UserModel;
use App\Models\NotificationModel;
use App\Libraries\AuthService;

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
        'account_login' => [
            'title'   => 'Login Successful',
            'content' => 'You have successfully logged in.',
        ],
        'password_changed' => [
            'title'   => 'Password Changed',
            'content' => 'Your password has been changed for security.',
        ],
        'support_issue_created' => [
            'title'   => 'Support Request Received',
            'content' => 'We have received your issue . Our support team will get back to you shortly.'
        ],
        'subscription_started' => [
            'title'   => 'Subscription Activated',
            'content' => 'Your new subscription is now active!',
        ],
        'subscription_expired' => [
            'title'   => 'Subscription Expired',
            'content' => 'Your subscription has expired. Please renew.',
        ],
        'account_created' => [
            'title'   => 'Welcome!',
            'content' => 'Your account has been successfully created.',
        ],
        'account_logout' => [
            'title'   => 'Logout Successful',
            'content' => 'You have been successfully logged out.',
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
