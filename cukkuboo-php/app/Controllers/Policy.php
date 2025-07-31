<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper;
use App\Models\PolicyModel;
use App\Libraries\AuthService;

class Policy extends ResourceController
{
    protected $policyModel;
    protected $authService;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->input = \Config\Services::request();
        $this->policyModel = new PolicyModel();
        $this->authService = new AuthService();
    }
    public function createPolicy()
{
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }

    $userId = $user['user_id'];
    $json = $this->request->getJSON(true); 

    $policyId  = $json['policy_id'] ?? null;
    $type      = $json['type'] ?? null;
    $title     = $json['title'] ?? null;
    $content   = $json['content'] ?? null;
    $status    = $json['status'] ?? 1;

    if (empty($type)){
        return $this->failValidationErrors('type is required.');
    }
    if (empty($title)){
        return $this->failValidationErrors('title is required.');
    }
    if (empty($content)){
        return $this->failValidationErrors('content is required.');
    }
    $data = [
        'type'     => $type,
        'title'    => $title,
        'content'  => $content,
        'status'   => $status
    ];

    if ($policyId) {
        $existing = $this->policyModel->find($policyId);
        if (!$existing || $existing['status'] == 9) {
            return $this->failNotFound('Policy not found or already deleted.');
        }

        $data['modify_by'] = $userId;
        $data['modify_on'] = date('Y-m-d H:i:s');

        if ($this->policyModel->update($policyId, $data)) {
            $updated = $this->policyModel->find($policyId);
            return $this->respond([
                'success' => true,
                'message' => 'Policy updated successfully.',
                'data'    => $updated
            ]);
        } else {
            return $this->failServerError('Failed to update policy.');
        }
    } else {
        $data['created_by'] = $userId;
        $data['created_on'] = date('Y-m-d H:i:s');

        if ($this->policyModel->insert($data)) {
            $newId = $this->policyModel->getInsertID();
            $created = $this->policyModel->find($newId);
            return $this->respondCreated([
                'success' => true,
                'message' => 'Policy created successfully.',
                'data'    => $created
            ]);
        } else {
            return $this->failServerError('Failed to create policy.');
        }
    }
}
public function getAllPolicy()
{
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }
    if ($user['status'] != 1) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }
    $type      = $this->request->getGet('type');
    $search    = trim($this->request->getGet('search') ?? '');
    $pageIndex = (int) $this->request->getGet('pageIndex');
    $pageSize  = (int) $this->request->getGet('pageSize');

    $pageSize = $pageSize > 0 ? $pageSize : 10;
    $offset   = $pageIndex * $pageSize;

    $result = $this->policyModel->getPolicyList($type, $search, $pageSize, $offset);

    return $this->respond([
        'success'    => true,
        'message'    => 'Policies fetched successfully.',
        'total'      => $result['total'],
        // 'pageIndex'  => $pageIndex,
        // 'pageSize'   => $pageSize,
        'data'       => $result['data']
    ]);
}

public function getPolicyById($policyId = null)
{
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }
    if ($user['status'] != 1) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }
    if (empty($policyId)) {
        return $this->failValidationErrors('Policy ID is required.');
    }

    $policy = $this->policyModel->find($policyId);

    if (!$policy || $policy['status'] == 9) {
        return $this->failNotFound('Policy not found or deleted.');
    }

    return $this->respond([
        'success' => true,
        'message' => 'Policy fetched successfully.',
        'data'    => $policy
    ]);
}
public function deletePolicy($policyId= null)
    {
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }

    $status = 9;

    $deleted = $this->policyModel->deletePlanById($status, (int)$policyId,$user['user_id']);

    if ($deleted) {
        return $this->respond([
            'success' => true,
            'message' => "Policy $policyId marked as deleted successfully.",
            'data'=>[]
        ]);
    }

    return $this->failServerError("Failed to delete policy with ID $policyId.");
    }
    

}

