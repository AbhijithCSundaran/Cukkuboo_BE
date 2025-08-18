<?php

namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper; 
use App\Models\NotificationModel;
use App\Models\UserModel;
use App\Libraries\AuthService;

class Notification extends ResourceController
{
    protected $notificationModel;
    protected $authService;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->input = \Config\Services::request();
        $this->db = \Config\Database::connect();
        $this->notificationModel = new NotificationModel();
        $this->UserModel = new UserModel();
        $this->authService = new AuthService();
    }

    public function createOrUpdate()
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);
    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }

    $notificationModel = new NotificationModel();
    $userModel = new UserModel(); 
    $data = $this->request->getJSON(true);
    $notificationId = $data['notification_id'] ?? null;

    // $notificationData = [
    //     'user_id'   => $user['user_id'],
    //     'title'     => $data['title'] ?? '',
    //     'content'   => $data['content'] ?? '',
    //     'status'    => $data['status'] ?? 1,
    // ];
    $notificationData = [
    'user_id'       => $user['user_id'],   
    'title'         => $data['title'] ?? '',
    'content'       => $data['content'] ?? '',  
    'type'          => 'global',  
    'target'        => in_array($data['target'] ?? '', ['all', 'premium', 'free']) ? $data['target'] : 'all',
    'is_scheduled'  => !empty($data['is_scheduled']) ? true : false,
    'scheduled_time'=> !empty($data['scheduled_time']) ? $data['scheduled_time'] : null,
    'image'         => $data['image'] ?? null,
    'status'        => 1,
];
    if ($notificationId) {
        $notificationData['modify_by'] = $user['user_id'];
        $notificationData['modify_on'] = date('Y-m-d H:i:s');

        $updated = $notificationModel->update($notificationId, $notificationData);
        $notificationData['notification_id'] = $notificationId;

        return $this->respond([
            'success' => true,
            'message' => $updated ? 'Notification updated' : 'Update failed',
            'data' => $notificationData
        ]);
    } else {
        $notificationData['created_by'] = $user['user_id'];
        $notificationData['created_on'] = date('Y-m-d H:i:s');
        $notificationData['status'] = 1;

        $insertedId = $notificationModel->insert($notificationData);
        $notificationData['notification_id'] = $insertedId; 
    
        return $this->respond([
            'success' => true,
            'message' => 'Notification created',
            'data' => $notificationData
        ]);
    }
}
    public function getAllNotifications()
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

    $pageIndex = (int) $this->request->getGet('pageIndex');
    $pageSize  = (int) $this->request->getGet('pageSize');
    $search    = $this->request->getGet('search');

    if ($pageSize <= 0) {
        $pageSize = 10;
    }

    $offset = $pageIndex * $pageSize;
    $notificationsModel = new notificationModel();
    $data = $notificationsModel->getUserNotifications($pageSize, $offset, $search);

    return $this->respond([
        'success' => true,
        'message' => 'Notifications fetched successfully.',
        'data'    => $data['notifications'],
        'total'   => $data['total']
    ]);
}

    public function delete($notification_id = null)
    {
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }

    if (!$notification_id) {
        return $this->failNotFound('Notification ID not provided.');
    }
    $deleted = $this->notificationModel->softDelete($notification_id, $user['user_id']);

    if ($deleted) {
        return $this->respondDeleted([
            'success' => true,
            'message' => "Notification with ID $notification_id marked as deleted successfully.",
            'data' => []
        ]);
    } else {
        return $this->failServerError("Failed to delete notification with ID $notification_id.");
    }
    }

//    public function markAllAsReadOrUnread()
// {
//     // $authHeader = $this->request->getHeaderLine('Authorization');
//     $authHeader = AuthHelper::getAuthorizationToken($this->request);
//     $user = $this->authService->getAuthenticatedUser($authHeader);

//     if (!$user) {
//         return $this->failUnauthorized('Invalid or missing token.');
//     }
//     if ($user['status'] != 1) {
//         return $this->failUnauthorized('Token expired. You have been logged out.');
//     }

//     $userId = $user['user_id'];

//     // Check if any unread notifications (status=1)
//     $unreadCount = $this->notificationModel
//         ->where('user_id', $userId)
//         ->where('status', 1)
//         ->countAllResults();

//     if ($unreadCount > 0) {
//         // Mark unread as read (1 -> 2)
//         $this->notificationModel
//             ->where('user_id', $userId)
//             ->where('status', 1)
//             ->set(['status' => 2])
//             ->update();

//         return $this->respond([
//             'success' => true,
//             'message' => 'All unread notifications marked as read.'
//         ]);
//     }

//     // Otherwise, check if any read notifications (status=2)
//     $readCount = $this->notificationModel
//         ->where('user_id', $userId)
//         ->where('status', 2)
//         ->countAllResults();

//     if ($readCount > 0) {
//         // Mark read as unread (2 -> 1)
//         $this->notificationModel
//             ->where('user_id', $userId)
//             ->where('status', 2)
//             ->set(['status' => 1])
//             ->update();

//         return $this->respond([
//             'success' => true,
//             'message' => 'All read notifications marked as unread.',
//         ]);
//     }
//     return $this->respond([
//         'success' => true,
//         'message' => 'No notifications to update.',
//     ]);
// }
   public function getUserNotifications($userId = null)
{
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $authUser   = $this->authService->getAuthenticatedUser($authHeader);

    if (!$authUser) {
        return $this->failUnauthorized('Invalid or missing token.');
    }
    if ($authUser['status'] != 1) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }
    if ($userId === null) {
        $userId = $authUser['user_id'];
    }

    $pageIndex = (int) ($this->request->getGet('pageIndex') ?? 0);
    $pageSize  = (int) ($this->request->getGet('pageSize') ?? 10);
    $search    = trim($this->request->getGet('search') ?? '');
    $isPremium = strtolower($authUser['subscription']) === 'premium';

    $result = $this->notificationModel->getUserNotificationsByToken($userId, $pageIndex, $pageSize, $search, null, $isPremium);

    return $this->respond([
        'success' => true,
        'message' => 'Notifications fetched successfully.',
        'total'   => $result['total'],
        'data'    => $result['data']
    ]);
}

public function getNotificationById($notificationId = null)
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $authUser = $this->authService->getAuthenticatedUser($authHeader);

    if (!$authUser) {
        return $this->failUnauthorized('Invalid or missing token.');
    }
    if ($authUser['status'] != 1) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }

    if ($notificationId === null) {
        return $this->failValidationErrors('Notification ID is required.');
    }
    $this->notificationModel->getById($notificationId);
    $notification = $this->notificationModel->find($notificationId);

    if (!$notification || $notification['status'] == 9) {
        return $this->failNotFound('Notification not found.');
    }

    return $this->respond([
        'success' => true,
        'message' => 'Notification fetched successfully.',
        'data' => $notification
    ]);
}
   public function markAllAsReadOrUnread()
{
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }

    if ($user['status'] != 1) {
        return $this->failUnauthorized('Token expired. You have been logged out.');
    }

    $userId = $user['user_id'];
    $now    = date('Y-m-d H:i:s');

    $db = \Config\Database::connect();
    $statusUpdateTable = $db->table('status_update');
    $hasUnreadNormal = $this->notificationModel
        ->where('user_id', $userId)
        ->where('status', 1)
        ->where('type !=', 'global')
        ->countAllResults() > 0;
    $hasUnreadGlobal = $statusUpdateTable
        ->select('su.*')
        ->from('status_update su')
        ->join('notification n', 'n.notification_id = su.notification_id', 'inner')
        ->where('su.user_id', $userId)
        ->where('su.status', 1)
        ->where('n.status !=', 9)
        ->where('(n.is_scheduled = 0 OR (n.is_scheduled = 1 AND n.scheduled_time <= "'.$now.'"))')
        ->countAllResults() > 0;

    $targetStatusNormal = ($hasUnreadNormal || $hasUnreadGlobal) ? 2 : 1;
    $this->notificationModel
        ->where('user_id', $userId)
        ->where('type !=', 'global')
        ->where('status !=', 9)
        ->set(['status' => $targetStatusNormal])
        ->update();
    $globalNotifications = $this->notificationModel
        ->where('type', 'global')
        ->where('status !=', 9)
        ->where('(is_scheduled = 0 OR (is_scheduled = 1 AND scheduled_time <= "'.$now.'"))')
        ->findAll();

    foreach ($globalNotifications as $notification) {
        $entry = $statusUpdateTable
            ->where('notification_id', $notification['notification_id'])
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        if ($entry) {
            $statusUpdateTable
                ->where('notification_id', $notification['notification_id'])
                ->where('user_id', $userId)
                ->update([
                    'status'    => $targetStatusNormal,
                    'modify_on' => $now,
                    'modify_by' => $userId,
                ]);
        } else {
            $statusUpdateTable->insert([
                'notification_id' => $notification['notification_id'],
                'user_id'         => $userId,
                'status'          => $targetStatusNormal,
                'title'           => $notification['title'],
                'content'         => $notification['content'],
                'created_by'      => $userId,
                'created_on'      => $now,
                'modify_by'       => $userId,
                'modify_on'       => $now,
            ]);
        }
    }

    $message = ($targetStatusNormal == 2) 
        ? 'All notifications marked as read.' 
        : 'All notifications marked as unread.';

    return $this->respond([
        'success' => true,
        'message' => $message,
    ]);
}
}