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
        $client = Client::create($request->all());
        return response()->json(['Client créé avec succès',$client]);
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
        $client->update($request->all());
        return response()->json(['Client mise a jour avec succès',$client]);

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

        return response()->json(['message' => 'Commentaire enregistré avec succès', 'Commentaire'=> $commentaire->commentaire_lib]);
    }


    public function enreg_notation(Request $request)
    {
        $notation = Notation::create($request->all());

        return response()->json(['message' => 'Notation enregistré avec succès', 'nbre_etoile'=> $notation->nbre_etoiles ]);
    }

    public function list_message()
    {

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
