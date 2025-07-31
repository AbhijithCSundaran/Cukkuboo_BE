<?php namespace App\Models;

use CodeIgniter\Model;

class SubscriptionPlanModel extends Model
{
    protected $table = 'subscriptionplan'; 
    protected $primaryKey = 'subscriptionplan_id'; 

    protected $allowedFields = [
        'plan_name', 'price', 'period', 'discount_price',
        'features', 'status','modify_by','modify_on', 'created_on', 'created_by'
    ];

    public function addPlan($data)
    {
        return $this->insert($data);
    }

    public function updatePlan($id, $data)
    {
        return $this->update($id, $data);
    }

    public function getPlanById($id)
    {
        return $this->find($id);
    }
    public function deletePlanById($status, $id, $user_id)
    {
    return $this->db->table($this->table)
        ->where('subscriptionplan_id', $id)
        ->update([
            'status' => $status,
            'modify_by' => $user_id,
            'modify_on' => date('Y-m-d H:i:s')
        ]);
    }
    
 

}
