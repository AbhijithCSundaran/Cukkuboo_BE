<?php
 
namespace App\Models;
 
use CodeIgniter\Model;
 
class CategoryModel extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'category_id';
    protected $allowedFields = [
        'category_id', 'category_name', 'description', 'status',
        'created_on', 'created_by', 'modify_on', 'modify_by'
    ];
 
    public $useAutoIncrement = false;
    public $useTimestamps = false;
 
    /**
     * Add a new category
     */
    public function addCategory($data)
    {
        return $this->db->table($this->table)->insert($data);
    }
 
    /**
     * Update category by ID
     */
    public function updateCategory($category_id, $data)
    {
        return $this->db->table($this->table)
                        ->where('category_id', $category_id)
                        ->update($data);
    }
 
    /**
     * Soft delete is handled in controller, but this method can be used if needed
     */
    public function deleteCategory($category_id)
    {
        return $this->db->table($this->table)
                        ->where('category_id', $category_id)
                        ->delete();
    }
 
    /**
     * Check if a category exists
     * Accepts either ID or Name
     */
    public function categoryExists($value)
    {
        $column = is_numeric($value) ? 'category_id' : 'category_name';
        return $this->db->table($this->table)
                        ->where($column, $value)
                        ->where('status !=', 9)
                        ->get()
                        ->getRow();
    }
}