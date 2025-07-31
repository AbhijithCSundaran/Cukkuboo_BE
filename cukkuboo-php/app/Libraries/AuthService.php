<?php

namespace App\Libraries;

//use App\Models\TokenVerifyModel;
 use App\Models\UserModel;
use App\Libraries\Jwt;

class AuthService
{
    protected $UserModel;

    public function __construct()
    {
        $this->UserModel = new UserModel();
    }

    public function getAuthenticatedUser($authHeader)
    {

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = trim(str_replace('Bearer', '', $authHeader));
        try {
            $user = $this->UserModel->findUserByToken($token);

            if (!$user || $user['jwt_token'] !== $token) {
                return null;
            }

            return $user;

        } catch (\Exception $e) {
            return null;
        }
    }
}
