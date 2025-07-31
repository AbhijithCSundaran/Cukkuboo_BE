<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper; 
use App\Models\VideoviewModel;
use App\Models\UserModel;
use App\Libraries\AuthService;

class VideoView extends ResourceController
{
    protected $videoviewModel;
    protected $userModel;
    protected $authService;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->input = \Config\Services::request();
        $this->videoviewModel = new VideoviewModel();
        $this->userModel = new UserModel();	
        $this->authService = new AuthService();
    }

    public function viewVideo()
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }
    if ($user['status'] != 1) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }
    $data = $this->request->getJSON(true);
    $movieId = $data['mov_id'] ?? null;

    if (!$movieId) {
        return $this->fail('Movie ID is required.', 422);
    }

    $userId = $user['user_id'];

    $existing = $this->videoviewModel->getUserVideoView($userId, $movieId);

    if (!$existing) {
        $this->videoviewModel->insertUserView([
            'user_id'    => $userId,
            'mov_id'     => $movieId,
            'created_on' => date('Y-m-d H:i:s'),
            'created_by' => $userId
        ]);

        $this->videoviewModel->updateVideoViewCount($movieId);
    } else {
        $this->videoviewModel->updateUserView($userId, $movieId);
    }

    return $this->respond([
        'success' => true,
        'message' => 'Movie view recorded.',
        'data' => [
            'user_id' => $userId,
            'mov_id'  => $movieId
        ]
    ]);
}

}
