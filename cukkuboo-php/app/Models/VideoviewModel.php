<?php

namespace App\Models;

use CodeIgniter\Model;

class VideoviewModel extends Model
{
    protected $table = 'movie_view';
    protected $primaryKey = 'mview_id';

    public function getUserVideoView($userId, $movieId)
    {
        return $this->db->table($this->table)
            ->where(['user_id' => $userId, 'mov_id' => $movieId])
            ->get()
            ->getRowArray();
    }

    public function insertUserView($data)
    {
        return $this->db->table($this->table)->insert($data);
    }

    public function updateUserView($userId, $movieId)
    {
        return $this->db->table($this->table)
            ->where(['user_id' => $userId, 'mov_id' => $movieId])
            ->update([
                'modify_on' => date('Y-m-d H:i:s'),
                'modify_by' => $userId
            ]);
    }

    public function updateVideoViewCount($movieId)
    {
        $views = $this->db->table($this->table)
            ->where(['mov_id' => $movieId])
            ->countAllResults();

        return $this->db->table('movies_details')
            ->where('mov_id', $movieId)
            ->update(['views' => $views]);
    }
}
