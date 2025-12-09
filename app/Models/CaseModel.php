<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseModel extends Model
{
    protected $table = 'cases';
    protected $fillable = ['customer_name','financing_type','amount','description'];

    public function decision()
    {
        return $this->hasOne(Decision::class, 'case_id');
    }
}
