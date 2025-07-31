<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper; 
use App\Models\ReelLikeModel;
use App\Models\UserModel;
use App\Libraries\AuthService;

class ReelLike extends ResourceController
{
    protected $reelLikeModel;
    protected $userModel;
    protected $authService;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->input = \Config\Services::request();
        $this->reelLikeModel = new ReelLikeModel();
        $this->userModel = new UserModel();
        $this->authService = new AuthService();
    }

    public function reelLike()
    {
        // $authHeader = $this->request->getHeaderLine('Authorization');
         $authHeader = AuthHelper::getAuthorizationToken($this->request);
        $user = $this->authService->getAuthenticatedUser($authHeader);

        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }

        $data = $this->request->getJSON(true);
        $userId = $user['user_id']; 
        $reelId = $data['reels_id'] ?? null;
        $status = $data['status'] ?? null;

        if (!$userId || !$reelId || !in_array($status, [1, 2])) {
            return $this->failValidationError('Missing or invalid fields.');
        }

        $existing = $this->reelLikeModel->getUserReelLike($userId, $reelId);

        if (!$existing) {
            $this->reelLikeModel->insertUserLike([
                'user_id'    => $userId,
                'reels_id'   => $reelId,
                'status'     => $status,
                'created_on' => date('Y-m-d H:i:s'),
                'created_by' => $userId
            ]);
        } else {
            if ($existing['status'] == $status) {
                $this->reelLikeModel->removeUserLike($userId, $reelId);
            } else {
                $this->reelLikeModel->updateUserLike($userId, $reelId, $status);
            }
        }

        $this->reelLikeModel->updateReelLikeCount($reelId);

        return $this->respond([
            'success' => true,
            'message' => 'Reel like action processed',
            'data' => [
                'user_id'    => $userId,
                'reels_id'   => $reelId,
                'status'     => $status
            ]
        ]);
    }
}

