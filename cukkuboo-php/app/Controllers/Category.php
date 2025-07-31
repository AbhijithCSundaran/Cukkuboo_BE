<?php
 
namespace App\Controllers;
 
use App\Models\CategoryModel;
use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper;
use App\Libraries\Jwt;
use App\Libraries\AuthService;
 
class Category extends ResourceController
{
    protected $categoryModel;
    protected $authService;
 
    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->input = \Config\Services::request();
        $this->categoryModel = new CategoryModel();
        $this->authService = new AuthService();
    }
 
    public function saveCategory()
    {
        // $authHeader = $this->request->getHeaderLine('Authorization');
         $authHeader = AuthHelper::getAuthorizationToken($this->request);
        $user = $this->authService->getAuthenticatedUser($authHeader);
 
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        if ($user['status'] = 2) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }
 
        $data = $this->request->getJSON(true);
        $category_id = $data['category_id'] ?? null;
 
        if (empty($data['category_name']) || !isset($data['description'])) {
            return $this->failValidationError('category_name and description are required.');
        }
 
        $categoryData = [
            'category_name' => $data['category_name'],
            'description'   => $data['description'],
            'modify_on'     => date('Y-m-d H:i:s'),
        ];
 
        if (empty($category_id)) {
            if ($this->categoryModel->categoryExists($data['category_name'])) {
                return $this->fail('Category with this category_name already exists.');
            }
 
            $categoryData['status']     = 1; // Active
            $categoryData['created_on'] = date('Y-m-d H:i:s');
            $categoryData['created_by'] = $user['user_id'];
 
            $this->categoryModel->addCategory($categoryData);
 
            return $this->respondCreated([
                'success' => true,
                'message' => 'Category created successfully.',
                'data'    => $categoryData
            ]);
        } else {
            if (!$this->categoryModel->categoryExists($category_id)) {
                return $this->failNotFound('Category not found.');
            }
 
            $categoryData['modify_by'] = $user['user_id'];
            $this->categoryModel->updateCategory($category_id, $categoryData);
 
            return $this->respond([
                'success' => true,
                'message' => 'Category updated successfully.',
                'data'    => array_merge(['category_id' => $category_id], $categoryData)
            ]);
        }
    }
public function getCategoryById($id = null)
{
    if ($user['status'] = 2) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }
    if ($id === null) {
        return $this->failValidationError('Category ID is required.');
    }

    $category = $this->categoryModel
                     ->where('category_id', $id)
                     ->where('status !=', 9)
                     ->first();

    if (!$category) {
        return $this->failNotFound('Category not found.');
    }

    return $this->respond([
        'success' => true,
        'message'=>'success',
        'data'    => $category
    ]);
}
    public function categorylist()
    {
        if ($user['status'] = 2) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
        }
        $pageIndex = (int) $this->request->getGet('pageIndex');
        $pageSize  = (int) $this->request->getGet('pageSize');
        $search    = $this->request->getGet('search');
 
        if ($pageSize <= 0) {
            $pageSize = 10;
        }
 
        $offset = $pageIndex * $pageSize;
 
        $builder = $this->categoryModel->where('status', 1); // Only active
 
        if (!empty($search)) {
            $builder->groupStart()
                    ->like('category_name', $search)
                    ->orLike('description', $search)
                    ->groupEnd();
        }
 
        $total = $builder->countAllResults(false);
 
        $categories = $builder
            ->orderBy('category_id', 'DESC')
            ->findAll($pageSize, $offset);
 
        return $this->response->setJSON([
            'success' => true,
            'message'=>'success',
            'data'    => $categories,
            'total'   => $total
        ]);
    }
 
    public function delete($category_id = null)
    {
        // $authHeader = $this->request->getHeaderLine('Authorization');
        $authHeader = AuthHelper::getAuthorizationToken($this->request);
        $user = $this->authService->getAuthenticatedUser($authHeader);
 
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        if ($user['status'] = 2) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
        }
 
        if (!$category_id || !$this->categoryModel->categoryExists($category_id)) {
            return $this->failNotFound('Category not found.');
        }
 
        // Soft delete
        $updateData = [
            'status'      => 9,
            'modify_on'   => date('Y-m-d H:i:s'),
            'modify_by' => $user['user_id']
        ];
 
        $this->categoryModel->updateCategory($category_id, $updateData);
 
        return $this->respondDeleted([
            'success' => true,
            'message' => 'Category marked as deleted successfully.',
            'data' => []
        ]);
    }
}
 