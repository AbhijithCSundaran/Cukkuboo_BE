<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'notification'; 
    protected $primaryKey = 'notification_id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',
        'title',
        'content',
        'type',         
        'target', 
        'status',
        'image',
        'is_scheduled',
        'scheduled_time',
        'created_by',
        'created_on',
        'modify_by',
        'modify_on',
    ];
 
    public function getUserNotifications($limit, $offset, $search = null)
    {
        $builder = $this->db->table($this->table);
        $builder->select('notification.*, user.username');
         $builder->join('user', 'user.user_id = notification.created_by', 'left');

        $builder->where('notification.status !=', 9);

        if (!empty($search)) {
            $builder->groupStart()
                    ->like('notification.title', $search)
                    ->orLike('notification.content', $search)
                    ->orLike('user.username', $search)
                    ->groupEnd();
        }

        $total = $builder->countAllResults(false);

        $notifications = $builder->orderBy('notification.created_on', 'DESC')
                                 ->limit($limit, $offset)
                                 ->get()
                                 ->getResultArray();

        return [
            'notifications' => $notifications,
            'total'         => $total
        ];
    }
    public function getUserNotificationsByToken($userId, $pageIndex = 0, $pageSize = 10, $search = '', $now = null, $isPremium = false)
{
    $offset = $pageIndex * $pageSize;
    $now = $now ?? date('Y-m-d H:i:s');
    $individualBuilder = $this->select('n.*, su.status')
                              ->from('notification n')
                              ->join('status_update su', 'su.notification_id = n.notification_id AND su.user_id = ' . (int)$userId, 'left')
                              ->where('n.user_id', $userId)
                              ->where('n.status !=', 9)
                              ->where('n.type', 'individual');

    if (!empty($search)) {
        $individualBuilder->groupStart()
                          ->like('n.title', $search)
                          ->orLike('n.content', $search)
                          ->groupEnd();
    }

    $individualData = $individualBuilder->orderBy('n.created_on', 'DESC')
                                        ->get()->getResultArray();
    $globalBuilder = $this->select('n.*, su.status')
                          ->from('notification n')
                          ->join('status_update su', 'su.notification_id = n.notification_id AND su.user_id = ' . (int)$userId, 'left')
                          ->where('n.type', 'global')
                          ->where('n.status !=', 9);

    if (!empty($search)) {
        $globalBuilder->groupStart()
                      ->like('n.title', $search)
                      ->orLike('n.content', $search)
                      ->groupEnd();
    }

    if ($isPremium) {
        $globalBuilder->groupStart()
            ->where('n.target !=', 'premium')
            ->orGroupStart()
                ->where('n.target', 'premium')
                ->groupStart()
                    ->where('n.is_scheduled', 0)
                    ->orGroupStart()
                        ->where('n.is_scheduled', 1)
                        ->where('n.scheduled_time <=', $now)
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd()
        ->groupEnd();
    } else {
        $globalBuilder->where('n.target !=', 'premium')
                      ->groupStart()
                        ->where('n.is_scheduled', 0)
                        ->orGroupStart()
                            ->where('n.is_scheduled', 1)
                            ->where('n.scheduled_time <=', $now)
                        ->groupEnd()
                      ->groupEnd();
    }

    $globalData = $globalBuilder->orderBy('n.created_on', 'DESC')
                                ->get()->getResultArray();
    $allData = [];
    $seenIds = [];

    foreach (array_merge($individualData, $globalData) as $notif) {
        if (!in_array($notif['notification_id'], $seenIds)) {
            $seenIds[] = $notif['notification_id'];
            $notif['read_status'] = $notif['read_status'] ?? 0;
            $allData[] = $notif;
        }
    }
    usort($allData, fn($a, $b) => strtotime($b['created_on']) - strtotime($a['created_on']));
    $pagedData = array_slice($allData, $offset, $pageSize);
    return [
        'total' => count($allData),
        'data'  => $pagedData
    ];
}
   public function getByUserId($userId)
    {
        return $this->where('user_id', $userId)
                    ->where('status !=', 9)
                    ->orderBy('created_on', 'DESC')
                    ->findAll();
    }
    public function softDelete($notificationId, $userId)
{
    return $this->where('notification_id', $notificationId)
                ->set([
                    'status'     => 9,
                    'modify_by'  => $userId,
                    'modify_on'  => date('Y-m-d H:i:s')
                ])
                ->update();
}

    public function markAllAsRead($userId)
{
    return $this->where('user_id', $userId)
                ->where('status', 1) 
                ->set(['status' => 2]) 
                ->update();
}
public function getById($notificationId)
{
    return $this->update($notificationId, ['status' => 2]);
}
public function hasUnreadNotifications($userId)
{
    return $this->where('user_id', $userId)
                ->where('status', 1) // 1 = unread
                ->countAllResults() > 0;
}
public function create($data) {
    $this->db->insert('notification', $data);
    return $this->db->insert_id();
}
// public function assignToUserNotificationTable($userId, $notificationId) {
//     $this->db->insert('user_notifications', [
//         'user_id' => $userId,
//         'notification_id' => $notificationId,
//         'is_read' => 0,
//         'is_deleted' => 0,
//         'created_at' => date('Y-m-d H:i:s')
//     ]);
// }
}