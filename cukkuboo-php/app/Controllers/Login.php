<?php

namespace App\Controllers;

use App\Models\LoginModel;
use App\Libraries\Jwt;
use App\Libraries\AuthService;
use App\Config\Email;
use App\Models\UsersubModel;
use App\Models\UserModel;
use App\Models\SubscriptionPlanModel;
use App\Models\NotificationModel;
use App\Helpers\AuthHelper;
class Login extends BaseController
{
    protected $loginModel;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->input = \Config\Services::request();
        $this->loginModel = new LoginModel();
        $this->usersubModel = new UsersubModel();
        $this->subscriptionPlanModel = new SubscriptionPlanModel();
        $this->userModel = new UserModel();
        $this->notificationModel = new NotificationModel();
    }

    public function loginFun()
{
    $data = $this->request->getJSON(true);
    if (!isset($data['email']) || !isset($data['password'])) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Email and password are required.',
            'data' => []
        ]);
    }

    $user = $this->loginModel
    ->where('email', $data['email'])
    ->where('status', 1)
    ->first();

    if (!$user) {
    //     $googleUser = $this->loginModel
    //     ->where('email', $data['email'])
    //     ->where('auth_type', 'google')
    //     ->first();

    // if ($googleUser) {
    //     return $this->response->setStatusCode(200)->setJSON([
    //         'success' => false,
    //         'message' => 'Login failed. This account is linked with Google. Please sign in using Google.',
    //         'data' => []
    //     ]);
    // }
        $suspendedUser = $this->loginModel
            ->where('email', $data['email'])
            ->where('status', 2)
            ->first();

        if ($suspendedUser) {
            return $this->response->setStatusCode(200)->setJSON([
                'success' => false,
                'message' => 'Your account has been suspended by the admin.',
                'data' => []
            ]);
        }
        $deletedUser = $this->loginModel
            ->where('email', $data['email'])
            ->where('status', 9)
            ->first();

        if ($deletedUser) {
            return $this->response->setStatusCode(200)->setJSON([
                'success' => false,
                'message' => 'Invalid email or password.',
                'data' => []
            ]);
        }
        return $this->response->setStatusCode(200)->setJSON([
            'success' => false,
            'message' => 'Invalid email or password.',
            'data' => []
        ]);
    }
    if ($user['auth_type'] === 'google') {
    return $this->response->setStatusCode(200)->setJSON([
        'success' => false,
        'message' => 'Login failed. If you signed up with Google, please use the Google Sign-In button.',
        'data' => []
    ]);
}
    if (!password_verify($data['password'], $user['password'])) {
        return $this->response->setStatusCode(200)->setJSON([
            'success' => false,
            'message' => 'Invalid email or password.',
            'data' => []
        ]);
    }
    $now = date('Y-m-d H:i:s');
    $jwt = new Jwt();
    $token = $jwt->encode(['user_id' => $user['user_id']]);
    $updateData = [
        'jwt_token' => $token,
        'last_login' => $now,
    ];
    $usersubModel = new UsersubModel();
    $notificationModel = new NotificationModel();
    $today = date('Y-m-d');
    $expired = $usersubModel
        ->where('user_id', $user['user_id'])
        ->where('status', 1)
        ->where('end_date <', $today)
        ->findAll();

    foreach ($expired as $sub) {
        $usersubModel->update($sub['user_subscription_id'], ['status' => 2]);
        $this->UserModel->update($userId, ['subscription' => 'Expired']);
    }
    $subscription = $usersubModel
        ->select('user_subscription.*, subscriptionplan.plan_name') 
        ->join('subscriptionplan', 'subscriptionplan.subscriptionplan_id = user_subscription.subscriptionplan_id')
        ->where('user_id', $user['user_id'])
        ->where('user_subscription.status !=', 9)
        ->orderBy('user_subscription_id', 'DESC')
        ->first();

    $subscriptionData = [];

    if ($subscription) {
    $subscriptionStatus = [
        1 => 'Premium',
        2 => 'Expired',
        3 => 'Cancelled',
        9 => 'Deleted'
    ];

    $subscriptionData = [
        'user_subscription_id'=> $subscription['user_subscription_id'] ?? null,
        'subscriptionplan_id' => $subscription['subscriptionplan_id'],
        'plan_name'           => $subscription['plan_name'],
        'start_date'          => $subscription['start_date'],
        'end_date'            => $subscription['end_date'],
        'subscription'        => $subscription['status']
    ];
}
    else {
        $subscriptionData = [
            'subscriptionplan_id' => null,
            'plan_name' => null,
            'start_date' => null,
            'end_date' => null,
            'subscription' => 0
        ];
    }
    $unreadCount = $notificationModel
    ->where('user_id', $user['user_id'])
    ->where('status', 1)
    ->countAllResults();

    // Login Type 1: No fcm_token → just return existing token, no update
    if (empty($data['fcm_token'])) {
    $this->loginModel->update($user['user_id'], $updateData);
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
            'user_id' =>  $user['user_id'],
            'username' => $user['username'],
            'phone' => $user['phone'],
            'email' => $user['email'],
            'isBlocked' => $user['status'] !== 'active',
            'subscription' => $user['subscription'],
            'user_type' => $user['user_type'],
            'auth_type' => $user['auth_type'],
            // 'date' => date('Y-m-d'),
            'createdAt' => $user['created_at'],
            'updatedAt' => $user['updated_at'],
            'lastLogin' => $now,
            'jwt_token' => $token,
            'notifications' => $unreadCount,
            'subscription_details' => $subscriptionData
            ]
        ]);
    }

    // Login Type 2: Email + Password + fcm_token → update token and fcm_token

    $updateData['fcm_token'] = $data['fcm_token'];
    
    $this->loginModel->update($user['user_id'], $updateData);
    return $this->response->setJSON([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'user_id' =>  $user['user_id'],
            'username' => $user['username'],
            'phone' => $user['phone'],
            'email' => $user['email'],
            'isBlocked' => $user['status'] !== 'active',
            'subscription' => $user['subscription'],
            'user_type' => $user['user_type'],
            'auth_type' => $user['auth_type'],
            // 'date' => date('Y-m-d'),
            'createdAt' => $user['created_at'],
            'updatedAt' => $user['updated_at'],
            'lastLogin' => $now,
            'jwt_token' => $token,
            'fcm_token' => $data['fcm_token'],
            'notifications' => $unreadCount,
            'subscription_details' => $subscriptionData
        ]
    ]);
}

    public function logout()
    {
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $auth = new AuthService();
    $user=$auth->getAuthenticatedUser($authHeader);

    if (!$user) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Invalid token or user not found.',
            'data' => []
        ]);
    }

    $this->loginModel->update($user['user_id'], ['jwt_token' => null]);

    return $this->response->setJSON([
        'success' => true,
        'message' => 'Logout successful. Token removed.',
        'data' => []
    ]);
}


public function resetPassword()
{
    $data = $this->request->getJSON(true);

    if (empty($data['email']) || empty($data['otp']) || empty($data['new_password'])) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Email, OTP, and new password are required.',
            'data' => []
        ]);
    }

    $user = $this->loginModel->where('email', $data['email'])->first();

    if (!$user || $user['password'] !== $data['otp']) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Invalid OTP or email.',
            'data' => []
        ]);
    }

    $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);

    // Only update if new password is different from current password (which is OTP right now)
    if ($user['password'] !== $hashedPassword) {
        $this->loginModel->update($user['user_id'], ['password' => $hashedPassword]);
    }

    return $this->response->setJSON([
        'success' => true,
        'message' => 'Password reset successful.',
        
    ]);
}
}