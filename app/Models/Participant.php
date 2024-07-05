<?php

namespace App\Models;

use App\Models\Election;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = ['election_id', 'name', 'is_candidate', 'role'];

    public function election()
    {
        return $this->belongsTo(Election::class);
    }
}
