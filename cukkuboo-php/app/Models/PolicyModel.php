<?php

namespace App\Models;

use CodeIgniter\Model;

class PolicyModel extends Model
{
    protected $table            = 'policies';
    protected $primaryKey       = 'policy_id';
    protected $allowedFields    = [
        'user_id', 'type', 'title', 'content', 'status', 'created_by', 'created_on', 'modify_by', 'modify_on'
    ];
    public function getPolicyList($type = '', $search = '', $limit = 10, $offset = 0)
{
    $builder = $this->db->table('policies')
                        ->where('status !=', 9);

    if (!empty($type)) {
        $typeInput = strtolower(trim($type));
        $builder->where('LOWER(type) LIKE', '%' . $typeInput . '%');
    }
    if (!empty($search)) {
        $search = strtolower(trim($search));
        $builder->groupStart()
                ->like('LOWER(title)', $search)
                ->orLike('LOWER(content)', $search)
                ->groupEnd();
    }
    $builder->orderBy('created_on', 'DESC');
    $countBuilder = clone $builder;
    $total = $countBuilder->countAllResults(false);

    $data = $builder->limit($limit, $offset)
                    ->get()
                    ->getResultArray();

    return [
        'total' => $total,
        'data'  => $data
    ];
}

public function getPolicyById($policyId)
{
    return $this->where('policy_id', $policyId)
                ->where('status !=', 9)
                ->first();
}
public function deletePlanById($status, $policyId,$user_id)
    {
    return $this->db->table($this->table)
        ->where('policy_id',$policyId)
        ->update([
            'status' => $status,
            'modify_by' => $user_id,
            'modify_on' => date('Y-m-d H:i:s')
        ]);
    }
    
}