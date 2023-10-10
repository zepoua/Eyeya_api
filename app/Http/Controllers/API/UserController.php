<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Domaine;
use App\Models\Notation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Throwable;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    public function index(){
         return response()->json(User::all());
    }

    public function search(Request $request)
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
        try{
        $validator = validator(
            $request->all(),
            [
                'nom_entreprise' => 'string',
                'nom' => ['required', 'string'],
                'prenom' => ['required', 'string'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'min:8'],
                'adresse' => 'required',
                'telephone1' => ['required', 'numeric', 'digits:8', 'unique:users,telephone1'],
                'telephone2' => ['numeric', 'digits:8', 'unique:users,telephone2'],
                'qualification' => 'required',
                'experience' => 'required',
                'description' => 'required',
                //'image1' => ['mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
                //'image2' => ['mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
                //'image3' => ['mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
                'domaine_id' => 'required',
            ],
            [
                'string' => ':attribute doit être une chaîne de caractère',
                'required' => ':attribute est obligatoire',
                'unique' => ':attribute existe déjà',
                'numeric' => ':attribute doit être que des chiffres',
                'digits' => ':attribute doit être de 8 chiffres',
                'image' => ':attribute doit être une image',
                'mimes' => ':attribute doit être de type jpeg,png,jpg,gif,svg',
                'max' => 'La taille de l\':attribute doit pas dépasser 2048 Ko',
                'min' => ':attribute doit contenir 8 caractères minimum',
                'email.email' => 'L\'adresse email doit être une adresse email',
            ],
            [
                'nom_entreprise' => "Le nom de l'entreprise",
                'nom' => "Le nom",
                'prenom' => "Le prenom",
                'email' => "L'adresse mail",
                'password' => "Le mot de passe",
                'adresse' => "L'adresse",
                'telephone1' => "Le numéro de téléphone 1",
                'telephone2' => "Le numéro de téléphone 2",
                'qualification' => "La/les qualification(s)",
                'experience' => "L'/les expérience(s)",
                'description' => "La description",
                'domaine_id' => "Le domaine",

            ]
           );} catch (Throwable $th) {
            return response($th->getMessage());
        }

        try{
            if($validator->fails()){
                return response()->json($validator->errors()->first());
               }else{
                User::create($request->all());
                return response()->json([
                    'status' => 'success',
                    'code' => 201,
                    'message' => 'Professionnel créé avec succès.'], 201);
               }
        }catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur s\'est produite lors de l\'enregistrement.'], 500);
        }
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
        return response()->json(['Professionnel mise a jour avec succès']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['Professionnel supprime avec succès',User::all()]);
    }

    public function domaine()
    {
        return response()->json(Domaine::all());

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
