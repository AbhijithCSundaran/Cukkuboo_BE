<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginModel extends Model
{
    protected $table = 'user';
    protected $primaryKey = 'user_id';

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'username',
        'email',
        'password',
        'phone',
        'join_date',
        'status',
        'user_type',
        'subscription',
        'created_at',
        'updated_at',
        'last_login',
        'created_by',
        'jwt_token',
        'fcm_token',
        'auth_type'
    ];

    // Optional: Clear all jwt_tokens
    public function updateAllTokensNull()
    {
        return $this->builder()->update(['jwt_token' => null]);
    }
}
