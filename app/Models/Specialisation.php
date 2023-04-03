<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialisation extends Model
{
    use HasFactory;
    public $timestamps = false;

    public function examinations()
    {
        return $this->belongsToMany(Examination::class);
    }

    public function note()
    {
        return $this->hasOne(ExaminationNote::class);
    }
}
