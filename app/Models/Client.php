<?php

namespace App\Models;

use App\Models\Message;
use App\Models\Notation;
use App\Models\Commentaire;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{

    use HasFactory;

    protected $fillable = ['nom', 'prenom', 'email', 'telephone', 'icone'];

    public function commentaires(){

        return $this->hasMany(Commentaire::class);
    }

    public function notations(){

        return $this->hasMany(Notation::class);
    }

    public function messages(){

        return $this->hasMany(Message::class);
    }

}
