<?php

namespace App\Models;

use CodeIgniter\Model;

class UsersubModel extends Model
{
    protected $table = 'user_subscription';
    protected $primaryKey = 'user_subscription_id';

    protected $allowedFields = [
        'user_id',
        'subscriptionplan_id',
        'plan_name',
        'price',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'created_on',
        'modify_by',
        'modify_on'
    ];
    protected $useTimestamps = false;
    protected $returnType = 'array';


    public function saveUserSubscription(array $payload, $id = null)
{
    if ($id) {
        $this->update($id, $payload);
        return $id;
    } else {
        return $this->insert($payload);
    }
}

    
    public function getAllSubscriptions()
    {
        return $this->orderBy($this->primaryKey, 'DESC')->findAll();
    }

    
    public function getUserSubscriptionById($userId, $subscriptionId)
    {
        return $this->where('user_subscription_id', $subscriptionId)
                    ->where('user_id', $userId)
                    ->whereIn('status', [1, 2, 3])
                    ->first();
    }

    public function getUserSubscriptions($userId)
    {
        return $this->where('user_id', $userId)
                    ->whereIn('status', [1, 2, 3])
                    ->findAll();
    }
    public function DeleteSubscriptionById($status, $id, $modifiedBy = null)
{
    return $this->update($id, [
        'status'     => $status,
        'modify_on'  => date('Y-m-d H:i:s'),
        'modify_by'  => $modifiedBy
    ]);
}

public function cancelUserSubscription($userId)
{
    $today = date('Y-m-d');

    return $this->where('user_id', $userId)
                ->where('status', 1) 
                ->where('end_date >=', $today) 
                ->set(['status' => 3]) 
                ->update();
}

public function countCurrentMonthSubscribers()
    {
    return $this->where('status', 2) 
                ->where('MONTH(created_on)', date('m'))
                ->where('YEAR(created_on)', date('Y'))
                ->countAllResults();
    }
public function currentTotalRevenue()
{
    return $this->selectSum('price')
        ->whereNotIn('status', [3, 9])
        ->where('MONTH(start_date)', date('m'))
        ->where('YEAR(start_date)', date('Y'))
        ->get()
        ->getRow()
        ->price ?? 0;
}
public function getTransactions()
{
    return $this->select('user_subscription.*, user.username')
                ->join('user', 'user.user_id = user_subscription.user_id', 'left')
                ->whereNotIn('user_subscription.status', [3, 9])
                ->where('MONTH(user_subscription.created_on)', date('m'))
                ->where('YEAR(user_subscription.created_on)', date('Y'))
                ->orderBy('user_subscription.created_on', 'DESC')
                ->findAll(10, 0);
}


   
}
