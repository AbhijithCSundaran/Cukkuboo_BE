<?php

namespace App\Models;

use CodeIgniter\Model;

class ResumeModel extends Model
{
    protected $table = 'resume_history';
    protected $primaryKey = 'resume_id';
    protected $allowedFields = ['user_id', 'mov_id', 'duration','status', 'created_by', 'created_on', 'modify_by', 'modify_on'];

    public function saveOrUpdate($userId, $movId, $duration)
    {
        $existing = $this->where(['user_id' => $userId, 'mov_id' => $movId])->first();
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $this->update($existing['resume_id'], [
                'duration' => $duration,
                'status'   => 1,
                'modify_by' => $userId,
                'modify_on' => $now
            ]);
        } else {
            return $this->insert([
                'user_id' => $userId,
                'mov_id' => $movId,
                'duration' => $duration,
                 'status'     => 1,
                'created_by' => $userId,
                'created_on' => $now,
                'modify_by' => $userId,
                'modify_on' => $now
            ]);
        }
    }
    public function getAllUsersHistory($search = '')
{
    $builder = $this->builder()
        ->select('resume_history.*, movies_details.title')
        ->join('movies_details', 'movies_details.mov_id = resume_history.mov_id', 'left')
        ->where('resume_history.status !=', 9);  

    if (!empty($search)) {
        $builder->like('movies_details.title', $search);
    }
    $builder->orderBy('resume_history.resume_id', 'DESC');
    return $builder;
}

public function getHistoryById($historyId)
{
    return $this->builder()
        ->select('resume_history.*, movies_details.title, movies_details.thumbnail, movies_details.release_date')
        ->join('movies_details', 'movies_details.mov_id = resume_history.mov_id', 'left')
        ->where('resume_history.resume_id', $historyId)
        ->where('resume_history.status !=', 9)
        ->get()
        ->getRow();  
}
public function getCompletedHistory($userId)
    {
        return $this->select('resume_history.*, movies_details.title, movies_details.thumbnail') 
                    ->join('movies_details', 'movies_details.mov_id = resume_history.mov_id', 'left') 
                    ->where('resume_history.user_id', $userId)
                    ->where('resume_history.status !=', 9) 
                    ->orderBy('resume_history.created_on', 'DESC')
                    ->findAll();
    }
public function softDeleteById($id)
{
    return $this->update($id, ['status' => 9]);
}

}
