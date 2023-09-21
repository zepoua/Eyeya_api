<?php

namespace App\Models;

use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['message', 'user_id', 'client_id'];

    public function client(){

        return $this->belongsTo(Client::class);
    }

    public function user(){

        return $this->belongsTo(User::class);
    }
}
