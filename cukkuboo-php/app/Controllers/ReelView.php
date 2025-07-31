<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper; 
use App\Models\ReelViewModel;
use App\Models\UserModel;
use App\Libraries\AuthService;

class ReelView extends ResourceController
{
    protected $reelViewModel;
    protected $userModel;
    protected $authService;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->input = \Config\Services::request();
        $this->reelViewModel = new ReelViewModel();
        $this->userModel = new UserModel();
        $this->authService = new AuthService();
    }

    public function viewReel()
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

    if (!$reelId || $status != 1) {
        return $this->failValidationError('Invalid or missing data');
    }
    // if ($user['status'] != 1) {
    //     return $this->failUnauthorized('Token expired. You have been logged out.');
    // }
    $existing = $this->reelViewModel->getUserReelView($userId, $reelId);

    if (!$existing) {
        $this->reelViewModel->insertUserView([
            'user_id'    => $userId,
            'reels_id'   => $reelId,
            'status'     => $status,
            'created_on' => date('Y-m-d H:i:s'),
            'created_by' => $userId
        ]);

        $this->reelViewModel->updateReelViewCount($reelId);
    } else {
        $this->reelViewModel->updateUserView($userId, $reelId);
    }

    return $this->respond([
        'success' => true,
        'message' => 'Reel viewed successfully',
        'data'=>[
            'user_id'   => $userId,
            'reels_id'  => $reelId,
            'status'    => $status
        ]
    ]);
}

}
