<?php

namespace App\Models;

use App\Models\Participant;
use App\Models\ElectionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Election extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'participants', 'results', 'user_id', 'status_id'];

    protected $casts = [
        'participants' => 'array',
        'results' => 'array',
    ];

    public function status()
    {
        return $this->belongsTo(ElectionStatus::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }
}
