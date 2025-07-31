<?php

namespace App\Models;

use CodeIgniter\Model;

class SupportModel extends Model
{
    protected $table            = 'support_issues';
    protected $primaryKey       = 'support_id';
    protected $allowedFields    = [
        'user_id','name', 'email', 'phone', 'issue_type', 'description',
        'screenshot', 'status', 'created_by', 'created_on', 'modify_by', 'modify_on'
    ];
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

public function getAllComplaints($search = '')
{
    $builder = $this->db->table($this->table);
    $builder->select('support_issues.*, support_issues.name');
    $builder->join('user', 'user.user_id = support_issues.user_id', 'left');
    $builder->where('support_issues.status !=', 9); 

    if (!empty($search)) {
        $builder->groupStart();
        $builder->like('support_issues.email', $search);
        $builder->orLike('support_issues.issue_type', $search);
        $builder->orLike('support_issues.description', $search);
        $builder->orLike('support_issues.name', $search); 
        $builder->groupEnd();
    }

    return $builder;
}

public function getComplaintById($supportId)
{
    return $this->db
        ->table('support_issues')
        ->select('support_issues.*, support_issues.name')
        ->join('user', 'user.user_id = support_issues.user_id', 'left')
        ->where('support_issues.support_id', $supportId)
        ->where('support_issues.status !=', 9)
        ->get()
        ->getRowArray();
}
public function getComplaintsByUser($userId)
{
    return $this->db
        ->table('support_issues')
        ->select('support_issues.*, support_issues.name')
        ->join('user', 'user.user_id = support_issues.user_id', 'left')
        ->where('support_issues.user_id', $userId)
        ->where('support_issues.status !=', 9)
        ->orderBy('support_issues.created_on', 'DESC')
        ->get()
        ->getResultArray(); 
}

public function deletePlanById($status, $supportId, $user_id)
    {
    return $this->db->table($this->table)
        ->where('support_id',$supportId)
        ->update([
            'status' => $status,
            'modify_by' => $user_id,
            'modify_on' => date('Y-m-d H:i:s')
        ]);
    }
    
 


}
