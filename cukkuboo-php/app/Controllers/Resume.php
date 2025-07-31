<?php

namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper; 
use App\Models\ResumeModel;
use App\Libraries\AuthService;

class Resume extends ResourceController
{
    protected $resumeModel;
    protected $authService;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->input = \Config\Services::request();
        $this->resumeModel = new ResumeModel();
        $this->authService = new AuthService();
    }

    public function saveProgress()
    {
        // $authHeader = $this->request->getHeaderLine('Authorization');
        $authHeader = AuthHelper::getAuthorizationToken($this->request);
        $user = $this->authService->getAuthenticatedUser($authHeader);

        if (!$user || !isset($user['user_id'])) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized user.'
            ]);
        }
        if ($user['status'] != 1) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }
        $data = $this->request->getJSON(true);
        $movId = $data['mov_id'] ?? null;
        $duration = $data['duration'] ?? null;

        if (!$movId || $duration === null) {
            return $this->respond([
                'success' => false,
                'message' => 'Movie ID and duration are required.'
            ]);
        }

        $result = $this->resumeModel->saveOrUpdate($user['user_id'], $movId, $duration);

        return $this->respond([
            'success' => true,
            'message' => $result ? 'Movie progress saved successfully.' : 'Failed to save progress.'
        ]);
    }

    public function getAllHistory()
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user || !isset($user['user_id'])) {
        return $this->respond(['success' => false, 'message' => 'Unauthorized user.']);
    }
    if ($user['status'] != 1) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }
    $pageIndex = (int) $this->request->getGet('pageIndex');
    $pageSize  = (int) $this->request->getGet('pageSize');
    $search    = trim($this->request->getGet('search') ?? '');

    if ($pageSize <= 0) {
        $pageSize = 10;
    }

    $offset = $pageIndex * $pageSize;
    $builder = $this->resumeModel->getAllUsersHistory($search);

    $totalBuilder = clone $builder;
    $totalCount = $totalBuilder->countAllResults(false);

    $history = $builder
        ->orderBy('resume_history.created_by', 'DESC')
        ->limit($pageSize, $offset)
        ->get()
        ->getResult();

    return $this->respond([
        'success' => true,
        'message' => 'All users viewed movie history fetched successfully.',
        'total'   => $totalCount,
        'data'    => $history
    ]);
}

    public function getById($id)
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user || !isset($user['user_id'])) {
        return $this->respond(['success' => false, 'message' => 'Unauthorized user.']);
    }
    if ($user['status'] != 1) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }
    $userId = $user['user_id'];
    $data = $this->resumeModel->getHistoryById($id);

    if (!$data) {
        return $this->respond(['success' => false, 'message' => 'History entry not found.']);
    }

    return $this->respond([
        'success' => true,
        'message' => 'History entry fetched successfully.',
        'data'    => $data
    ]);
}
public function getUserHistory()
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $authUser = $this->authService->getAuthenticatedUser($authHeader);

    if (!$authUser) {
        return $this->failUnauthorized('Invalid or missing token.');
    }
    if ($user['status'] != 1) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }
    $userId = $authUser['user_id'];

    $history = $this->resumeModel->getCompletedHistory($userId); 

    $total = count($history);

    return $this->response->setJSON([
        'success' => true,
        'message' => 'History entries fetched successfully.',
        'total'   => $total,
        'data'    => $history
    ]);
}

public function deleteById($Id)
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user || !isset($user['user_id'])) {
        return $this->respond(['success' => false, 'message' => 'Unauthorized user.']);
    }
    if ($user['status'] != 1) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }
    $deleted = $this->resumeModel->softDeleteById($Id);

    if ($deleted) {
        return $this->respond([
            'success' => true,
            'message' => "History entry ID $Id deleted successfully.",
            'data'    => []
        ]);
    }

    return $this->respond([
        'success' => false,
        'message' => "No active history found for ID $Id to delete or already deleted.",
        'data'    => []
    ]);
}
}
