<?php

namespace App\Models;

use CodeIgniter\Model;

class WatchLaterModel extends Model
{
    protected $table = 'watch_later';
    protected $primaryKey = 'watch_later_id';
    protected $allowedFields = [
        'user_id', 'mov_id', 'status',
        'created_by', 'created_on', 'modify_by', 'modify_on'
    ];

    public function addToWatchLater($user_id, $mov_id)
    {
        $existing = $this->where('user_id', $user_id)
                         ->where('mov_id', $mov_id)
                         ->where('status !=', 9) 
                         ->first();

        if ($existing) {
            return false;
        }

        return $this->insert([
            'user_id'     => $user_id,
            'mov_id'      => $mov_id,
            'status'      => 1,
            'created_by'  => $user_id,
            'created_on'  => date('Y-m-d H:i:s'),
            'modify_by'   => $user_id,
            'modify_on'   => date('Y-m-d H:i:s')
        ]);
    }
    public function getPaginatedList($pageIndex = 0, $pageSize = 10, $search = '')
{
    $builder = $this->db->table('watch_later wl')
        ->select('wl.*, m.title, m.thumbnail, m.banner')
        ->join('movies_details m', 'm.mov_id = wl.mov_id', 'left')
        ->where('wl.status !=', 9);

    if (!empty($search)) {
        $builder->like('m.title', $search);
    }
    $totalBuilder = clone $builder;
    $total = $totalBuilder->countAllResults();
    $builder->orderBy('wl.watch_later_id', 'DESC')
            ->limit($pageSize, $pageIndex * $pageSize);

    $data = $builder->get()->getResult();

    return ['total' => $total, 'data' => $data];
}


    public function getAllList($search = '')
    {
        
        $builder = $this->db->table('watch_later wl')
            ->select('wl.*, m.title, m.thumbnail, m.banner')
            ->join('movies_details m', 'm.mov_id = wl.mov_id', 'left')
            ->where('wl.status !=', 9);

        if (!empty($search)) {
            $builder->like('m.title', $search);
        }

        $data = $builder->orderBy('wl.watch_later_id', 'DESC')->get()->getResult();
        return ['total' => count($data), 'data' => $data];
    }

    public function getById($watchLaterId)
{
    return $this->select('watch_later.*, movies_details.title, movies_details.thumbnail,movies_details.banner, movies_details.release_date')
                ->join('movies_details', 'movies_details.mov_id = watch_later.mov_id')
                ->where('watch_later.watch_later_id', $watchLaterId)
                ->where('watch_later.status !=', 9)
                ->first();
}

public function getWatchLaterByToken($userId)
{
    return $this->db->table($this->table)
        ->select('watch_later.*, movies_details.title, movies_details.thumbnail,movies_details.banner, movies_details.release_date')
        ->join('movies_details', 'movies_details.mov_id = watch_later.mov_id')
        ->where('watch_later.user_id', $userId)
        ->where('watch_later.status !=', 9)
        ->get()
        ->getResultArray();
}
public function softDeleteById($watchLaterId)
{
    return $this->update($watchLaterId, ['status' => 9]);
}
public function hardDeleteAllHistoryByUser($userId)
{
    return $this->where('user_id', $userId)->delete();
}


}
