<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectionLog extends Model
{
    use HasFactory;

    protected $fillable = ['election_id', 'message'];
}
