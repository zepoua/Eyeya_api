<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Client;
use App\Models\Message;
use App\Models\Commentaire;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Notation;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Client::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       $validator = validator(
        $request->all(),
        [
            'nom' => ['required', 'string'],
            'prenom' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:clients,email'],
            'telephone' => ['required', 'numeric', 'digits:8', 'unique:clients,telephone'],
        ],
        [
            'required' => ':attribute est obligatoire',
            'unique' => ':attribute existe déjà',
            'numeric' => ':attribute doit être que des chiffres',
            'digits' => ':attribute doit être de 8 chiffres',
            'email.email' => 'L\'adresse email doit être une adresse email',
        ],
        [
            'nom' => "Le nom",
            'prenom' => "Le prenom",
            'email' => "L'adresse mail",
            'telephone' => "Le numéro de téléphone",
        ]
       );

      try{
        if($validator->fails()){
            return response()->json($validator->errors()->first());
           }else{
            $client = Client::create($request->all());
            return response()->json([
                'status' => 'success',
                'code' => 201,
                'message' => 'Client créé avec succès.',
                'client_id' => $client->id], 201);
           }
      }catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'code' => 500,
            'message' => 'Une erreur s\'est produite lors de l\'enregistrement.'], 500);    }
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        return response()->json($client);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validator = validator(
            $request->all(),
            [
                'nom' => ['required', 'string'],
                'prenom' => ['required', 'string'],
                'email' => ['required', 'email', 'unique:clients,email'],
                'telephone' => ['required', 'numeric', 'digits:8', 'unique:clients,telephone'],
            ],
            [
                'required' => ':attribute est obligatoire',
                'unique' => ':attribute existe déjà',
                'numeric' => ':attribute doit être que des chiffres',
                'digits' => ':attribute doit être de 8 chiffres',
                'email.email' => 'L\'adresse email doit être une adresse email',
            ],
            [
                'email' => "L'adresse mail",
                'telephone' => "Le numéro de téléphone",
            ]
           );

          try{
            if($validator->fails()){
                return response()->json([
                    'status' => 'failed',
                    'code' => 500,
                    'message' => $validator->errors()->first()
                ]);
               }else{
                $client->update($request->all());
                return response()->json([
                    'status' => 'success',
                    'code' => 201,
                    'message' => 'Mise à jour réussie.'], 201);
               }
          }catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur s\'est produite lors de la mise à jour.'], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['Client supprime avec succès',Client::all()]);
    }


    public function enreg_commentaire(Request $request)
    {
        $commentaire = Commentaire::create($request->all());

        $response = [
            'commentaire' => $commentaire->commentaire_lib,
            'client' => $commentaire->client->nom,
            'date' => $commentaire->created_at
        ];

        return response()->json($response);
    }


    public function list_message(){

        $messages = Message::with('user', 'client')->get();

        $response = [];

        foreach ($messages as $message) {
            $response[] = [
                'message' => $message->message,
                'author' => $message->user ? $message->user->nom : $message->client->nom,
            ];
        }
        return response()->json($response);
    }


    public function enreg_message(Request $request)
    {
        $message = Message::create($request->all());

        return response()->json(['message' => 'Commentaire enregistré avec succès', 'Commentaire'=> $message->message]);
    }

}
