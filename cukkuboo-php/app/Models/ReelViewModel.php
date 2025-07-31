<?php

namespace App\Models;

use CodeIgniter\Model;

class ReelViewModel extends Model
{
    protected $table = 'reel_view';
    protected $primaryKey = 'rview_id';

    public function getUserReelView($userId, $reelId)
    {
        return $this->db->table($this->table)
            ->where(['user_id' => $userId, 'reels_id' => $reelId])
            ->get()
            ->getRowArray();
    }

    public function insertUserView(array $data)
    {
        return $this->db->table($this->table)->insert($data);
    }

    public function updateUserView($userId, $reelId)
    {
        return $this->db->table($this->table)
            ->where(['user_id' => $userId, 'reels_id' => $reelId])
            ->update([
                'modify_on' => date('Y-m-d H:i:s'),
                'modify_by' => $userId
            ]);
    }

    public function updateReelViewCount($reelId)
    {
        $views = $this->db->table($this->table)
            ->where(['reels_id' => $reelId])
            ->countAllResults();

        return $this->db->table('reels')
            ->where('reels_id', $reelId)
            ->update(['views' => $views]);
    }
}
