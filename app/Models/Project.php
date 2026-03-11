<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;
    public function tasks(){
        return $this->hasMany(Task::class);
    }
    public function created_by(){
        return $this->belongsTo(User::class);
    }
    public function updated_by(){
        return $this->belongsTo(User::class);
    }
}
