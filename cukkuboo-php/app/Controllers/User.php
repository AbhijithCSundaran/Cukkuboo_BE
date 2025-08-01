<?php

namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper; 
use App\Models\UserModel;
use App\Libraries\Jwt;
use App\Models\UsersubModel;
use App\Models\SubscriptionPlanModel;
use App\Models\NotificationModel;
use App\Libraries\AuthService;

class User extends ResourceController
{
    protected $UserModel;
    

    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->input = \Config\Services::request();
        $this->UserModel = new UserModel();
        $this->usersubModel = new UsersubModel();
        $this->subscriptionPlanModel = new SubscriptionPlanModel();
        $this->notificationModel = new NotificationModel();
        $this->authService = new AuthService();
    }

    public function index(): string
    {
        return view('welcome_message');
    }

    public function registerFun()
{
    $data = $this->request->getJSON(true);
    $user_id = $data['user_id'] ?? 0;
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $authenticatedUser = $this->authService->getAuthenticatedUser($authHeader);

    $userData = array_filter([
        'username'     => $data['username'] ?? null,
        'phone'        => $data['phone'] ?? null,
        'email'        => $data['email'] ?? null,
        'password'     => $data['password'] ?? null,
        'country'      => $data['country'] ?? null,
        'subscription' => $data['subscription'] ?? 'free',
        'status'       => (!empty($data['status']) && $data['status'] != 0) ? $data['status'] : 1,
        'join_date'    => $data['join_date'] ?? null,
        'user_type'    => $data['user_type'] ?? 'Customer',
        'date_of_birth'=> $data['date_of_birth'] ?? null,
        'auth_type'    => $data['auth_type'] ?? 'manual',
    ]);

    if (!empty($data['password'])) {
        $userData['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    }

    if (!$user_id) {
        if (empty($data['phone']) && empty($data['email'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Phone or email is required.'
            ]);
        }
        if (!empty($data['email'])) {
        $emailExists = $this->UserModel->checkExistingActiveUser('email', $data['email']);
        if ($emailExists) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Email already in use by an active user.'
            ]);
        }
    }

    if (!empty($data['phone'])) {
        $phoneExists = $this->UserModel->checkExistingActiveUser('phone', $data['phone']);
        if ($phoneExists) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Phone already in use by an active user.'
            ]);
        }
    }
        $existingUser = $this->UserModel->isUserExists($data['phone'] ?? null, $data['email'] ?? null);

        if ($existingUser) {
            $conflictUser = $this->UserModel->where('status !=', 9)
                                            ->groupStart()
                                            ->where('email', $data['email'] ?? '')
                                            ->orWhere('phone', $data['phone'] ?? '')
                                            ->groupEnd()
                                            ->first();

            if ($conflictUser && $conflictUser['user_id'] != $existingUser['user_id']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Email or phone is already in use by another active user.'
                ]);
            }
            if ((int)$existingUser['status'] == 9) {
                $userData['join_date']  = $userData['join_date'] ?? date('Y-m-d');
                $userData['created_at'] = date('Y-m-d H:i:s');

                $newUserId = $this->UserModel->addUser($userData);
                $user = $this->UserModel->find($newUserId);

                $jwt = new Jwt();
                $token = $jwt->encode(['user_id' => $user['user_id']]);
                $created_by = $authenticatedUser ? $authenticatedUser['user_id'] : $newUserId;

                $this->UserModel->update($newUserId, [
                    'created_by' => $created_by,
                    'jwt_token'  => $token
                ]);

                $user = $this->UserModel->find($newUserId);

                $responseData = [
                    'user_id'       => $user['user_id'],
                    'username'      => $user['username'],
                    'email'         => $user['email'],
                    'password'      => $user['password'],
                    'phone'         => $user['phone'],
                    'status'        => $user['status'],
                    'join_date'     => $user['join_date'],
                    'date_of_birth' => $user['date_of_birth'],
                    'subscription'  => $user['subscription'],
                    'user_type'     => $user['user_type'],
                    'created_at'    => $user['created_at'],
                    'created_by'    => $user['created_by'],
                    'jwt_token'     => $user['jwt_token'],
                    'auth_type'     => $user['auth_type'],
                ];

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'User re-registered successfully.',
                    'data'    => $responseData
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User already exists.'
                ]);
            }
        }

        $userData['join_date']  = $userData['join_date'] ?? date('Y-m-d');
        $userData['created_at'] = date('Y-m-d H:i:s');

        $newUserId = $this->UserModel->addUser($userData);
        $user = $this->UserModel->find($newUserId);

        $jwt = new Jwt();
        $token = $jwt->encode(['user_id' => $user['user_id']]);
        $created_by = $authenticatedUser ? $authenticatedUser['user_id'] : $newUserId;

        $this->UserModel->update($user['user_id'], [
            'created_by' => $created_by,
            'jwt_token'  => $token
        ]);

        $user = $this->UserModel->find($newUserId);

        $responseData = [
            'user_id'       => $user['user_id'],
            'username'      => $user['username'],
            'email'         => $user['email'],
            'password'      => $user['password'],
            'phone'         => $user['phone'],
            'status'        => $user['status'],
            'join_date'     => $user['join_date'],
            'date_of_birth' => $user['date_of_birth'],
            'subscription'  => $user['subscription'],
            'user_type'     => $user['user_type'],
            'created_at'    => $user['created_at'],
            'created_by'    => $user['created_by'],
            'jwt_token'     => $user['jwt_token'],
            'auth_type'     => $user['auth_type'],
        ];
        return $this->response->setJSON([
            'success' => true,
            'message' => 'User registered successfully.',
            'data'    => $responseData
        ]);
    }

    if ($authenticatedUser) {
    $existingUser = $this->UserModel->find($user_id);

    if ($existingUser) {
        $updateData = $userData; 

        if ($existingUser['auth_type'] === 'google') {
            
            $allowedFields = ['username', 'phone'];
            $updateData = array_intersect_key($userData, array_flip($allowedFields));
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $updateData['updated_by'] = $authenticatedUser['user_id'];

        $this->UserModel->updateUser($user_id, $updateData);
        $user = $this->UserModel->find($user_id);

        $responseData = [
            'user_id'       => $user['user_id'],
            'username'      => $user['username'],
            'email'         => $user['email'],
            'password'      => $user['password'],
            'phone'         => $user['phone'],
            'status'        => $user['status'],
            'join_date'     => $user['join_date'],
            'date_of_birth' => $user['date_of_birth'],
            'subscription'  => $user['subscription'],
            'user_type'     => $user['user_type'],
            'auth_type'     => $user['auth_type'],
            'created_at'    => $user['created_at'],
            'created_by'    => $user['created_by'],
            'updated_at'    => $user['updated_at'],
            'updated_by'    => $user['updated_by'],
        ];
        return $this->response->setJSON([
            'success' => true,
            'message' => 'User updated successfully.',
            'data'    => $responseData
        ]);
    }
}
}
    //  Get user details
    // public function getUserDetails()
    // {
    //     $user = $this->getAuthenticatedUser();

    //     if (!$user) {
    //         return $this->response->setJSON([
    //             'status' => false,
    //             'message' => 'Unauthorized'
    //         ])->setStatusCode(401);
    //     }

    //     return $this->response->setJSON([
    //         'status' => true,
    //         'data' => $user
    //     ]);
    // }


 public function deleteUser($user_id)
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
     $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }
     if ($user['user_id'] != $user_id) {
        return $this->failForbidden("You are not allowed to delete this account.");
    }
    // $password = $this->request->getJSON()->password;
    $json = $this->request->getJSON();
    $password = $json->password ?? $this->request->getPost('password');
    if (empty($password)) {
         return $this->response->setJSON([
        'success' => false,
        'message' => 'Password is required to delete the account.']);
    }

    $userData = $this->UserModel->find($user_id);
    if (!$userData) {
        return $this->failNotFound("User not found.");
    }

    if (!password_verify($password, $userData['password'])) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Incorrect password.'
        ]);
    }

    $status = 9;
    if ($this->UserModel->deleteUserById($status, $user_id)) {
        return $this->respond([
            'success' => true,
            'message' => "User with ID $user_id has been deleted successfully.",
            'data' => []
        ]);
    } else {
        return $this->response->setJSON([
        'success' => false,
        'message' => 'Invalid or missing token.']);
    }
}

public function getUserDetailsById($userId = null)
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
     $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $authuser = $this->authService->getAuthenticatedUser($authHeader);

    if (!$authuser) {
        return $this->failUnauthorized('Invalid or missing token.');
    }

    if ($userId === null) {
        $userId = $authuser['user_id'];
    }

    $user = $this->UserModel->getUserById($userId);

    if (!$user) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'User not found',
            'data'    => []
        ]);
    }
    $usersubModel = new UsersubModel();
    $today = date('Y-m-d');
    $expired = $usersubModel
        ->where('user_id', $userId)
        ->where('status', 1) 
        ->where('end_date <', $today)
        ->findAll();

    foreach ($expired as $sub) {
        $usersubModel->update($sub['user_subscription_id'], ['status' => 2]);
        $this->UserModel->update($userId, ['subscription' => 'Expired']);
    }
    $usersubModel = new UsersubModel();
    $subscription = $usersubModel
        ->select('user_subscription.*, subscriptionplan.plan_name')
        ->join('subscriptionplan', 'subscriptionplan.subscriptionplan_id = user_subscription.subscriptionplan_id')
        ->where('user_subscription.user_id', $userId)
        ->where('user_subscription.status !=', 9)
        ->orderBy('user_subscription_id', 'DESC')
        ->first();

    $subscriptionStatusMap = [
        1 => 'Premium',
        2 => 'Expired',
        3 => 'Cancelled',
        9 => 'Deleted'
    ];

    $subscriptionData = [
        'user_subscription_id'=> $subscription['user_subscription_id'] ?? null,
        'subscriptionplan_id' => $subscription['subscriptionplan_id'] ?? null,
        'plan_name'           => $subscription['plan_name'] ?? null,
        'start_date'          => $subscription['start_date'] ?? null,
        'end_date'            => $subscription['end_date'] ?? null,
        'subscription' => $subscription['status'] ?? 0,
    ];

    $notificationModel = new NotificationModel();
    $unreadCount = $notificationModel
        ->where('user_id', $userId)
        ->where('status', 1)
        ->countAllResults();
    $jwt = new Jwt(); 
    $token = $jwt->encode(['user_id' => $userId]);
    $response = [
        'success' => true,
        'message' => 'Success',
        'data' => [
            'user_id'       => $user['user_id'],
            'username'      => $user['username'],
            'phone'         => $user['phone'],
            'email'         => $user['email'],
            'isBlocked'     => $user['status'] != 'active',
            'subscription'  => $user['subscription'],
            'country'       => $user['country'],
            'status'        => $user['status'],
            'date_of_birth' => $user['date_of_birth'],
            'join_date'     => $user['join_date'],
            'user_type'     => $user['user_type'],
            'auth_type'     => $user['auth_type'],
            'createdAt'     => $user['created_at'],
            'updatedAt'     => $user['updated_at'],
            'lastLogin'     => $user['last_login'],
            'jwt_token'     => $token,
            'notifications' => $unreadCount,
            'subscription_details' => $subscriptionData
        ]
    ];

    return $this->response->setJSON($response);
}


public function getUserList()
{
    $pageIndex = (int) $this->request->getGet('pageIndex');
    $pageSize  = (int) $this->request->getGet('pageSize');
    $search    = $this->request->getGet('search');
   
    // $authHeader = $this->request->getHeaderLine('Authorization');
     $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $authuser = $this->authService->getAuthenticatedUser($authHeader);
        if(!$authuser) 
            return $this->failUnauthorized('Invalid or missing token.');
    $userQuery = $this->UserModel->where('status !=', 9)
                                 ->where('user_type', 'customer');

   
    if (!empty($search)) {
        $userQuery->groupStart()
                  ->like('username', $search)         
                  ->orLike('email', $search)
                  ->orLike('subscription', $search)
                  ->orLike('phone', $search)
                  ->orLike('country', $search)
                 ->groupEnd();
    }

    if ($pageIndex < 0) {
        $users = $userQuery->orderBy('user_id', 'DESC')->findAll();
        $total = count($users);

        return $this->response->setJSON([
            'success' => true,
            'message'=>'success',
            'data'   => $users,
            'total'  => $total
        ]);
    }

    if ($pageSize <= 0) {
        $pageSize = 10;
    }

    $offset = $pageIndex * $pageSize;

    $total = $userQuery->countAllResults(false); 
    $users = $userQuery->orderBy('user_id', 'DESC')
                       ->findAll($pageSize, $offset);

    return $this->response->setJSON([
        'success' => true,
        'message'=>'success',
        'data'   => $users,
        'total'  => $total
    ]);
}

public function getStaffList()
{
    $pageIndex = (int) $this->request->getGet('pageIndex');
    $pageSize  = (int) $this->request->getGet('pageSize');
    $search    = $this->request->getGet('search');
   
    // $authHeader = $this->request->getHeaderLine('Authorization');
     $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $authuser = $this->authService->getAuthenticatedUser($authHeader);
        if(!$authuser) 
            return $this->failUnauthorized('Invalid or missing token.');
    $userQuery = $this->UserModel->where('status !=', 9)
                                 ->where('user_type !=', 'customer');

   
    if (!empty($search)) {
        $userQuery->groupStart()
                  ->like('username', $search)         
                  ->orLike('email', $search)
                  ->orLike('phone', $search)
                  ->orLike('country', $search)
                  ->orLike('user_type', $search)
                 ->groupEnd();
    }

    if ($pageIndex < 0) {
        $users = $userQuery->orderBy('user_id', 'DESC')->findAll();
        $total = count($users);

        return $this->response->setJSON([
            'success' => true,
            'message'=>'success',
            'data'   => $users,
            'total'  => $total
        ]);
    }

    if ($pageSize <= 0) {
        $pageSize = 10;
    }

    $offset = $pageIndex * $pageSize;

    $total = $userQuery->countAllResults(false); 
    $users = $userQuery->orderBy('user_id', 'DESC')
                       ->findAll($pageSize, $offset);

    return $this->response->setJSON([
        'success' => true,
        'message'=>'success',
        'data'   => $users,
        'total'  => $total
    ]);
}
public function updateEmailPreference()
{
    $json = $this->request->getJSON(true); 

    $userId = isset($json['user_id']) ? (int)$json['user_id'] : null;
    $status = isset($json['status']) ? (int)$json['status'] : null;

    if (!$userId || !in_array($status, [1, 2], true)) {
        return $this->failValidationErrors('Invalid input. user_id must be valid, and status must be 1 (enable) or 2 (disable).');
    }

    $userModel = new UserModel();
    $user = $userModel->find($userId);

    if (!$user) {
        return $this->failNotFound('User not found.');
    }

    if ((int)$user['email_preference'] === $status) {
        return $this->respond([
            'success'  => true,
            'message' => 'Email preference is already set to this value.',
            'data'=>[]
        ]);
    }

    $updated = $userModel->update($userId, ['email_preference' => $status]);

    if ($updated) {
        return $this->respond([
            'success'  => true,
            'message' => 'Email preference updated successfully.',
            'data'=>[
                'user_id'    => $userId,
                'status'     => $status
            ]
        ]);
    } else {
        return $this->failServerError('Failed to update email preference.');
    }
}


//---------------------------------------Admin Home Display------------------------------------------------//


    public function countActiveUsers()
    {
        // $authHeader = $this->request->getHeaderLine('Authorization');
        $authHeader = AuthHelper::getAuthorizationToken($this->request);
        $authuser = $this->authService->getAuthenticatedUser($authHeader);
        if(!$authuser) 
            return $this->failUnauthorized('Invalid or missing token.');
        $userModel = new UserModel();
        $activeCount = $userModel->countActiveUsers();

        return $this->respond([
            'success' => true,
            'message'=>'success',
            'data' => $activeCount
        ]);
    }
    public function changePassword()
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
     $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $authuser = $this->authService->getAuthenticatedUser($authHeader);

    if (!$authuser) {
        return $this->failUnauthorized('Invalid or missing token.');
    }
    if ($authuser['status'] != 1) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }

    $userId = $authuser['user_id'] ?? null;
    if (!$userId) {
        return $this->response->setJSON(['success' => 'false', 'message' => 'User not logged in.']);
    }
    $json = $this->request->getJSON(true);

    $oldPassword     = $json['oldPassword'] ?? null;
    $newPassword     = $json['newPassword'] ?? null;
    $confirmPassword = $json['confirmPassword'] ?? null;

    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        return $this->response->setJSON(['success' => 'false', 'message' => 'All fields are required.']);
    }

    if ($newPassword !== $confirmPassword) {
        return $this->response->setJSON(['success' => 'false', 'message' => 'New password and confirm password do not match.']);
    }

    $user = $this->UserModel->find($userId);

    if (!$user || !password_verify($oldPassword, $user['password'])) {
        return $this->response->setJSON(['success' => 'false', 'message' => 'Old password is incorrect.']);
    }

    $updated = $this->UserModel->update($userId, [
        'password'   => password_hash($newPassword, PASSWORD_BCRYPT),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    if ($updated) {
        return $this->response->setJSON(['success' => 'true', 'message' => 'Password updated successfully.']);
    }

    return $this->response->setJSON(['success' => 'false', 'message' => 'Failed to update password.']);
}

    public function deleteUserById($user_id)
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
     $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }

    $userData = $this->UserModel->find($user_id);
    if (!$userData) {
        return $this->failNotFound("User not found.");
    }

    $status = 9;

    if ($this->UserModel->deleteById($status, $user_id)) {
        return $this->respond([
            'success' => true,
            'message' => "User with ID $user_id has been deleted successfully.",
            'data' => []
        ]);
    } else {
        return $this->failServerError("Failed to delete user with ID $user_id.");
    }
}

}
