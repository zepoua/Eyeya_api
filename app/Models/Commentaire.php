<?php

namespace App\Models;

use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Commentaire extends Model
{
    use HasFactory;

    public function client(){

        return $this->belongsTo(Client::class);
    }

    public function user(){

        return $this->belongsTo(User::class);
    }
}
