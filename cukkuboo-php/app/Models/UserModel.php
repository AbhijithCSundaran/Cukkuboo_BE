<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'user';
    protected $primaryKey = 'user_id';

    protected $allowedFields = [
        'username',
        'phone',
        'email',
        'password',
        'country',
        'status',
        'join_date',
        'subscription',
        'user_type',
        'jwt_token',
        'fcm_token',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'date_of_birth',
        'email_preference',
        'auth_type',
    ];


    public function isUserExists($phone = null, $email = null)
{
    return $this->where('phone', $phone)
                ->orWhere('email', $email)
                ->first();
}


    public function addUser($data)
    {
        $this->insert($data);
        return $this->getInsertID();
    }

    public function updateUser($userId, $data)
    {
        return $this->update($userId, $data);
    }

    public function deleteUserById($status, $user_id)
{
    $userDeleted = $this->update($user_id, [
        'status' => $status,
        'jwt_token' => null
    ]);
    $this->db->table('user_subscription')
        ->where('user_id', $user_id)
        ->update(['status' => $status]);
    $this->db->table('watch_history')
        ->where('user_id', $user_id)
        ->update(['status' => $status]);

    $this->db->table('watch_later')
        ->where('user_id', $user_id)
        ->update(['status' => $status]);
    $this->db->table('notification')
        ->where('user_id', $user_id)
        ->update(['status' => $status]);
    $this->db->table('resume_history')
        ->where('user_id', $user_id)
        ->update(['status' => $status]);
    return true;
    }

    
    public function getUserById($userId)
    {
    return $this->select('*') 
                ->where('user_id', $userId)
                ->first();
    }

    public function checkExistingActiveUser($field, $value)
    {
    return $this->where($field, $value)
                ->where('status !=', 9)
                ->first();
    }


    public function findUserByToken($token)
    {
        return $this->where('jwt_token', $token)->first();
    }

    public function getAllUsers()
    {
        return $this->db->query("SELECT * FROM user WHERE status != 9 and user_type = 'customer'" )->getResult();
    }

    //--------------------------------------Admin Home Display----------------------------//
    public function countActiveUsers()
    {
        return $this->where('status', 1)->countAllResults();
    }
    public function deleteById($status, $userId)
{
    $userDeleted = $this->update($userId, [
        'status' => $status,
        'jwt_token' => null
    ]);
    if (!$userDeleted) {
        return false;
    }
    $this->db->table('user_subscription')
        ->where('user_id', $userId)
        ->update(['status' => $status]);
    $this->db->table('watch_history')
        ->where('user_id', $userId)
        ->update(['status' => $status]);

    $this->db->table('watch_later')
        ->where('user_id', $userId)
        ->update(['status' => $status]);
    $this->db->table('notification')
        ->where('user_id', $userId)
        ->update(['status' => $status]);
    $this->db->table('resume_history')
        ->where('user_id', $userId)
        ->update(['status' => $status]);
    return true;
}


    // -----------------------------------Password changing-----------------------//
    public function changePassword($userId, $oldPassword, $newPassword)
{
    $user = $this->find($userId);

    if (!$user) {
        return ['status' => 0, 'msg' => 'User not found.'];
    }

    if (!password_verify($oldPassword, $user['password'])) {
        return ['status' => 0, 'msg' => 'Old password does not match.'];
    }

    if (password_verify($newPassword, $user['password'])) {
        return ['status' => 0, 'msg' => 'Please use a new password different from the old one.'];
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    $data = [
        'password'   => $hashedPassword,
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if ($this->update($userId, $data)) {
        return ['status' => 1, 'msg' => 'Password updated successfully.'];
    } else {
        return ['status' => 0, 'msg' => 'Could not update the password. Please try again.'];
    }
}

    public function setUserSubscription($userId)
{
    return $this->where('user_id', $userId)
                ->set([
                    'subscription' => 'Cancelled',
                    'updated_at'  => date('Y-m-d H:i:s')
                ])
                ->update();
}
public function updateSubscriptionStatus($userId, $status)
{
    return $this->update($userId, ['subscription' => $status]);
}
    
}
