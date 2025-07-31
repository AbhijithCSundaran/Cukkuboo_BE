<?php

namespace App\Models;

use CodeIgniter\Model;

class ReelLikeModel extends Model
{
    protected $table = 'reel_like';
    protected $primaryKey = 'rlike_id';

    public function getUserReelLike($userId, $reelId)
    {
        return $this->db->table($this->table)
            ->where(['user_id' => $userId, 'reels_id' => $reelId])
            ->get()
            ->getRowArray();
    }

    public function insertUserLike(array $data)
    {
        return $this->db->table($this->table)->insert($data);
    }

    public function updateUserLike($userId, $reelId, $status)
    {
        return $this->db->table($this->table)
            ->where(['user_id' => $userId, 'reels_id' => $reelId])
            ->update([
                'status'    => $status,
                'modify_on' => date('Y-m-d H:i:s'),
                'modify_by' => $userId
            ]);
    }

    public function removeUserLike($userId, $reelId)
    {
        return $this->db->table($this->table)
            ->where(['user_id' => $userId, 'reels_id' => $reelId])
            ->delete();
    }

    public function updateReelLikeCount($reelId)
    {
        $likeCount = $this->db->table($this->table)
            ->where(['reels_id' => $reelId, 'status' => 1])  // status 1 means like
            ->countAllResults();

        return $this->db->table('reels')
            ->where('reels_id', $reelId)
            ->update(['likes' => $likeCount]);
    }
}
