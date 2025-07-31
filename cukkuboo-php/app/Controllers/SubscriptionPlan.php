<?php
 
namespace App\Controllers;
 
use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper; 
use App\Models\SubscriptionPlanModel;
use App\Libraries\AuthService;
 
class SubscriptionPlan extends ResourceController
{
    protected $subscriptionPlanModel;
    protected $authService;
 
    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->input = \Config\Services::request();
        $this->subscriptionPlanModel = new SubscriptionPlanModel();
        $this->authService = new AuthService();
    }
 
   public function savePlan()
{
    $data = $this->request->getJSON(true);
    $id = isset($data['subscriptionplan_id']) ? (int)$data['subscriptionplan_id'] : null;
 
    // $authHeader = $this->request->getHeaderLine('Authorization');
   $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);
 
    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }
 
    if (!isset($data['plan_name'], $data['price'], $data['discount_price'], $data['period'])) {
        return $this->failValidationErrors('Plan name, price, discount_price, and period are required.');
    }
 
    // Accept any positive integer for period
    if (!is_numeric($data['period']) || (int)$data['period'] <= 0) {
        return $this->failValidationErrors('Period must be a positive integer.');
    }
 
    $data['period'] = (int)$data['period'];
    $data['features'] = $data['features'] ?? null;
    $data['status'] = $data['status'] ?? 1;
    $data['modify_on'] = date('Y-m-d H:i:s');
    $data['modify_by'] = $user['user_id'];
 
    if (empty($id)) {
        // Create
        $data['created_on'] = date('Y-m-d H:i:s');
        $data['created_by'] = $user['user_id'];
 
        $insertId = $this->subscriptionPlanModel->addPlan($data);
        $data['subscriptionplan_id'] = $insertId;
 
        return $this->respond([
            'success' => true,
            'message' => 'Plan created successfully',
            'data' => $data
        ]);
    } else {
        // Update
        $existing = $this->subscriptionPlanModel->getPlanById($id);
        if (!$existing) {
            return $this->failNotFound("Plan with ID $id not found.");
        }
 
        $this->subscriptionPlanModel->updatePlan($id, $data);
        $data['subscriptionplan_id'] = $id;
 
        return $this->respond([
            'success' => true,
            'message' => 'Plan updated successfully',
            'data' => $data
        ]);
    }
}
 
 
    public function getAll()
    {
        $pageIndex = (int)$this->request->getGet('pageIndex') ?? 0;
        $pageSize = (int)$this->request->getGet('pageSize') ?? 10;
        $search = $this->request->getGet('search');
 
        
        // $authHeader = AuthHelper::getAuthorizationToken($this->request);
        // $user = $this->authService->getAuthenticatedUser($authHeader);
 
        // if (!$user) {
        //     return $this->failUnauthorized('Invalid or missing token.');
        // }
 
        $offset = $pageIndex * $pageSize;
 
        $builder = $this->subscriptionPlanModel->where('status !=', 9);
 
        if (!empty($search)) {
            $builder->groupStart()
                ->like('plan_name', $search)
                ->orLike('features', $search)
                ->groupEnd();
        }
 
        $total = $builder->countAllResults(false);
        $plans = $builder
            ->orderBy('subscriptionplan_id', 'DESC')
            ->findAll($pageSize, $offset);
 
        return $this->respond([
            'success' => true,
            'message'=>'success',
            'data' => $plans,
            'total' => $total
        ]);
    }
 
    public function get($id)
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
        $plan = $this->subscriptionPlanModel->getPlanById($id);
        if (!$plan) {
            return $this->failNotFound('Plan not found.');
        }
 
        return $this->respond([
            'success' => true,
            'message'=>'success',
            'data' => $plan
        ]);
    }
 
    public function delete($id = null)
    {
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }
    
    $status = 9;

    $deleted = $this->subscriptionPlanModel->deletePlanById($status, (int)$id, $user['user_id']);

    if ($deleted) {
        return $this->respond([
            'success' => true,
            'message' => "Plan with ID $id marked as deleted successfully.",
            'data'=>[]
        ]);
    }

    return $this->failServerError("Failed to delete plan with ID $id.");
    }
    

}