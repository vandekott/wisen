<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory;
    protected $primaryKey = 'key';

    protected $fillable = ['key', 'phone', 'status', 'auth_step'];

    public $incrementing = false;

}
