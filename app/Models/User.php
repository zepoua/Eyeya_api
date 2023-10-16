<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Domaine;
use App\Models\Message;
use App\Models\Notation;
use App\Models\Commentaire;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'email',
        'password',
        'adresse',
        'prenom',
        'nom_entreprise',
        'position',
        'telephone1',
        'telephone2',
        'qualification',
        'experience',
        'description',
        'image1',
        'image2',
        'image3',
        'domaine_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function commentaires(){

        return $this->hasMany(Commentaire::class);
    }

    public function notations(){

        return $this->hasMany(Notation::class);
    }

    public function domaine(){

        return $this->belongsTo(Domaine::class);
    }

    public function messages(){

        return $this->hasMany(Message::class);
    }

    public function moyenneNotations()
    {
        $notations = $this->notations;

        if ($notations->count() > 0) {
            $sommeNotes = $notations->sum('nbre_etoiles');
            return $sommeNotes / $notations->count();
        } else {
            return 0;
        }
    }
}
