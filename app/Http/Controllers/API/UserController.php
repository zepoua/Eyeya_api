<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Commentaire;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Notation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if ($search = $request->search) {
            $users = User::query()
                ->leftJoin('domaines', 'users.domaine_id', '=', 'domaines.id')
                ->where(function ($query) use ($search) {
                    $query->where('nom_entreprise', 'LIKE', '%' . $search . '%')
                          ->orWhere('nom', 'LIKE', '%' . $search . '%')
                          ->orWhere('prenom', 'LIKE', '%' . $search . '%')
                          ->orWhere('adresse', 'LIKE', '%' . $search . '%')
                          ->orWhere('domaine_lib', 'LIKE', '%' . $search . '%');
                })->get();
            return response()->json($users);
        } else {
            return response()->json(User::all());
        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = User::create($request->all());
        return response()->json(['Professionnel créé avec succès',$user]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json($user);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $user->update($request->all());
        return response()->json(['Professionnel mise a jour avec succès',$user]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['Professionnel supprime avec succès',User::all()]);
    }


    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {

            $user = auth()->user();
            $token = $user->createToken('token')->plainTextToken;

            return response()->json(['message'=>'utilisateur connecte', 'user'=>$user, 'token'=>$token]);
        }else
            return response('identifiants non valides');
    }


    public function list_commentaire($userId)
    {
        $user = User::find($userId);
        $comments = $user->commentaires()->with('client')->get();

        $response = [];

        foreach ($comments as $comment) {
            $response[] = [
                'commentaire' => $comment->commentaire_lib,
                'client ' => $comment->client->nom,
                'date' => $comment->created_at
            ];
        }
        return response()->json($response);

    }

    public function list_notation($userId)
    {
        $averageStars = Notation::where('user_id', $userId)->avg('nbre_etoiles');

        // Retourne la moyenne au format JSON
        return response()->json(['Nbre_etoiles' => $averageStars, 'user_id'=> $userId]);
    }
}
