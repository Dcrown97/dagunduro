<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = ["id"];

    public function departments()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
