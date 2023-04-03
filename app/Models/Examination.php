<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Examination extends Model
{
    use HasFactory;
    public $timestamps = false;

    public function note()
    {
        return $this->hasOne(ExaminationNote::class);
    }
}
