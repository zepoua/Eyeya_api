<?php

namespace App\Models;

use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notation extends Model
{
    use HasFactory;

    protected $fillable = ['nbre_etoiles', 'user_id', 'client_id'];

    public function user(){

        return $this->belongsTo(User::class);
    }

    public function client(){

        return $this->belongsTo(Client::class);

    }
}
