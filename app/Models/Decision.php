<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Decision extends Model
{
    protected $fillable = ['case_id', 'ai_decision', 'ai_explanation'];

        public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }
}
