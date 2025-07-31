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
        'message',
        'type',         
        'target', 
        'status',
        'is_sheduled',
        'sheduled_time',
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
    public function getUserNotificationsbyToken($userId, $pageIndex = 0, $pageSize = 10, $search = '')
{
    $offset = $pageIndex * $pageSize;

    $builder = $this->where('user_id', $userId)
                    ->where('status !=', 9);

    if (!empty($search)) {
        $builder->groupStart()
                ->like('title', $search)
                ->orLike('content', $search)
                ->groupEnd();
    }

    $total = $builder->countAllResults(false);

    $data = $builder->orderBy('created_on', 'DESC')
                    ->limit($pageSize, $offset)
                    ->findAll();

    return [
        'total' => $total,
        'data' => $data
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
                    ->where('user_id', $userId)
                    ->set(['status' => 9])
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

}
