<?php

namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper; 
use App\Models\ReelsModel;
use App\Models\ReelViewModel;
use App\Models\UserModel;
use App\Libraries\Jwt;
use App\Libraries\AuthService;

class Reels extends ResourceController
{
    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->input = \Config\Services::request();
        $this->db = \Config\Database::connect();
        $this->reelsModel = new ReelsModel();
        $this->reelViewModel = new ReelViewModel();
        $this->UserModel = new UserModel();	
        $this->authService = new AuthService();
    }

    public function addReel()
{
    $data = $this->request->getJSON(true);
    $reels_id = $data['reels_id'] ?? null;

    // $authHeader = $this->request->getHeaderLine('Authorization');
    // $authHeader = apache_request_headers()["Authorization"];
    // $authenticatedUser= $this->authService->getAuthenticatedUser($authHeader);
    // if (!$authenticatedUser) {
    //         return $this->failUnauthorized('Invalid or missing token.');
    //     }
    if (empty($reels_id)) {
        if (empty($data['title']) || empty($data['release_date']) || empty($data['access'])) {
            return $this->failValidationErrors('Title, release date, and access are required.');
        }
    }

    
    $reelData = [
        'title'         => $data['title'] ?? null,
        'description'   => $data['description'] ?? null,
        'release_date'  => $data['release_date'] ?? null,
        'access'        => $data['access'] ?? null,
        'thumbnail'     => $data['thumbnail'] ?? null,
        'video'     => $data['video'] ?? null,
        'views'         => $data['views'] ?? 0,
        'likes'         => $data['likes'] ?? 0,
        'modify_on'     => date('Y-m-d H:i:s')
    ];

    if (empty($reels_id)) {
        // Set default status and creation timestamps
        $reelData['status']     = 1;
        $reelData['created_on'] = date('Y-m-d H:i:s');
        $reelData['created_by'] = $data['created_by'] ?? null;

        $this->reelsModel->addReel($reelData);

        return $this->respond([
            'success'  => true,
            'message' => 'Reel created successfully',
            'data'    => $reelData
        ]);
    } else {
        $existing = $this->reelsModel->find($reels_id);
        if (!$existing) {
            return $this->failNotFound("Reel with ID $reels_id not found.");
        }

        $reelData['status'] = $data['status'] ?? $existing['status'];

        $this->reelsModel->updateReel($reels_id, $reelData);

        return $this->respond([
            'success'  => true,
            'message' => 'Reel updated successfully',
            'data'    => $reelData
        ]);
    }
}

//   public function getAllReels()
// {
//     $pageIndex = (int) $this->request->getGet('pageIndex');
//     $pageSize  = (int) $this->request->getGet('pageSize');
//     $search    = $this->request->getGet('search');
//     // $authHeader = $this->request->getHeaderLine('Authorization');
    //  $authHeader = apache_request_headers()["Authorization"];
//     // $user = $this->authService->getAuthenticatedUser($authHeader);
//     // if(!$user){ 
//     //         return $this->failUnauthorized('Invalid or missing token.');
//     // }
//     if ($pageSize <= 0) {
//         $pageSize = 10;
//     }

//     $offset = $pageIndex * $pageSize;

//     $builder = $this->reelsModel->where('status !=', 9);

//     if (!empty($search)) {
//         $builder->groupStart()
//             ->like('title', $search)
//             ->orLike('access', $search)
//             ->groupEnd();
//     }

//     // If pageIndex < 0, return all (no pagination)
//     if ($pageIndex < 0) {
//         $reels = $builder
//             ->orderBy('reels_id', 'DESC')
//             ->findAll();

//         return $this->response->setJSON([
//             'success'  => true,
//             'message' => 'All reels fetched (no pagination).',
//             'data'    => $reels,
//             'total'   => count($reels)
//         ]);
//     }

//     // Get total count
//     $total = $builder->countAllResults(false); // Don't reset the builder

//     // Get paginated data
//     $reels = $builder
//         ->orderBy('reels_id', 'DESC')
//         ->findAll($pageSize, $offset);

//     return $this->response->setJSON([
//         'success'  => true,
//         'message' => 'Paginated reels fetched successfully.',
//         'data'    => $reels,
//         'total'   => $total
//     ]);
// }

 public function getAllReels()
{
    $pageIndex = (int) $this->request->getGet('pageIndex');
    $pageSize  = (int) $this->request->getGet('pageSize');
    $search    = $this->request->getGet('search');
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);
    if ($pageSize <= 0) {
        $pageSize = 10;
    }

    $offset = $pageIndex * $pageSize;
    $builder = $this->reelsModel->where('status !=', 9);

    if (!empty($search)) {
       
        $search = strtolower(trim(preg_replace('/\s+/', ' ', $search)));
        $accessMap = [
            'free' => '1',
            'premium' => '2'
        ];
        $accessValue = $accessMap[$search] ?? null;

        $builder->groupStart()
            ->like('title', $search, 'both');

        if ($accessValue !== null) {
            $builder->orWhere('access', $accessValue);
        }

        $builder->groupEnd();
    }

    $total = $builder->countAllResults(false);

    $reels = $builder->orderBy('created_on', 'DESC')
                     ->findAll($pageSize, $offset);

    shuffle($reels);

    if ($user) {
        $user_id = $user['user_id'];
        foreach ($reels as &$reel) {
            $reel['is_liked_by_user'] = $this->reelsModel->isLikedByUser($reel['reels_id'], $user_id);
            $isViewed = $this->db->table('reel_view')
                                 ->where('reels_id', $reel['reels_id'])
                                 ->where('user_id', $user_id)
                                 ->countAllResults();

            $reel['is_viewed'] = $isViewed > 0 ? true : false;
        }
    }

    return $this->response->setJSON([
        'success' => true,
        'message' => 'Reels fetched successfully.',
        'data'    => $reels,
        'total'   => $total
    ]);
}
public function getActiveReels()
{
    $pageIndex = (int) $this->request->getGet('pageIndex');
    $pageSize  = (int) $this->request->getGet('pageSize');
    $search    = $this->request->getGet('search');
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);
    if ($pageSize <= 0) {
        $pageSize = 10;
    }

    $offset = $pageIndex * $pageSize;
    $builder = $this->reelsModel->where('status', 1);

    if (!empty($search)) {
       
        $search = strtolower(trim(preg_replace('/\s+/', ' ', $search)));
        $accessMap = [
            'free' => '1',
            'premium' => '2'
        ];
        $accessValue = $accessMap[$search] ?? null;

        $builder->groupStart()
            ->like('title', $search, 'both');

        if ($accessValue !== null) {
            $builder->orWhere('access', $accessValue);
        }

        $builder->groupEnd();
    }

    $total = $builder->countAllResults(false);

    $reels = $builder->orderBy('created_on', 'DESC')
                     ->findAll($pageSize, $offset);

    shuffle($reels);

    if ($user) {
        $user_id = $user['user_id'];
        foreach ($reels as &$reel) {
            $reel['is_liked_by_user'] = $this->reelsModel->isLikedByUser($reel['reels_id'], $user_id);
            $isViewed = $this->db->table('reel_view')
                                 ->where('reels_id', $reel['reels_id'])
                                 ->where('user_id', $user_id)
                                 ->countAllResults();

            $reel['is_viewed'] = $isViewed > 0 ? true : false;
        }
    }

    return $this->response->setJSON([
        'success' => true,
        'message' => 'Reels fetched successfully.',
        'data'    => $reels,
        'total'   => $total
    ]);
}

public function getReelById($id)
{
    
    //$authHeader = AuthHelper::getAuthorizationToken($this->request);
    // $user = $this->authService->getAuthenticatedUser($authHeader);
    // if(!$user){ 
    //         return $this->failUnauthorized('Invalid or missing token.');
    // }
    $data = $this->reelsModel->getReelDetailsById($id);

    return $this->response->setJSON([
        'success' => true,
        'message' => 'Reel details fetched successfully.',
        'data' => $data
    ]);
}


public function deleteReel($reels_id)
{
    //$authHeader = AuthHelper::getAuthorizationToken($this->request);
    // $user = $this->authService->getAuthenticatedUser($authHeader);
    // if (!$user)
    //     return $this->failUnauthorized('Invalid or missing token.');

    $status = 9;

    if ($this->reelsModel->softDeleteReelById($status, $reels_id)) {
        return $this->respond([
            'success' => true,
            'message' => "Reel with ID $reels_id marked as deleted successfully.",
            'data'=>[]
        ]);
    } else {
        return $this->failServerError("Failed to delete reel with ID $reels_id.");
    }
}



}