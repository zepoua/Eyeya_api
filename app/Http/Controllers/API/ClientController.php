<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Client;
use App\Models\Message;
use App\Models\Commentaire;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\Notation;
use Illuminate\Support\Facades\Http;
use Throwable;
use App\Events\MessagesEvent;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Client::all());
    }

    public function store_verification(Request $request){
        $validator = validator(
            $request->all(),
            [
                'nom' => ['required', 'string'],
                'prenom' => ['required', 'string'],
                'email' => ['required', 'email', 'unique:clients,email'],
                'telephone' => ['required', 'numeric', 'digits:8', 'unique:clients,telephone'],
                'icone' => 'required'
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
                'icone' => "La photo de profil"
            ]
           );
          try{
            if($validator->fails()){
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => $validator->errors()->first()], 500);
               }else{

                    $coderand = rand(100000, 999999);

                    $new_code = new Code;
                    $new_code->code = $coderand;
                    $new_code->telephone = $request->input('telephone');
                    $new_code->save();

                    $parametre = [
                    'username'=>'tikegne',
                    'password'=>'Tikegne2@21',
                    'destination'=>'228'.$request->telephone,
                    'source'=>'EYEYA',
                    'message'=>'Votre code de confirmation de votre inscription sur l\'application EYEYA est : '.$coderand.' garder le '];
                    $reponse_sms = Http::get('http://sendsms.e-mobiletech.com/',$parametre);

                    return response()->json([
                        'status' => 'success',
                        'code' => 201,
                        'message' => 'Code de confirmation envoye.']);
               }
          }catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Une erreur s\'est produite lors de l\'envoi du code de confirmation.'], 500);}
    }


    public function store(Request $request)
    {
        try {
            $validator = validator(
                $request->all(),
                [
                    'code' => ['required', 'numeric', 'digits:6'],
                ],
                [
                    'required' => ':attribute est obligatoire',
                    'numeric' => ':attribute doit être que des chiffres',
                    'digits' => ':attribute doit être de 6 chiffres',
                ],
                [
                    'code' => "Le code de confirmation",
                ]
               );
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $th->getMessage()], 500);        }

      try{
        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $validator->errors()->first()], 500);
        }else{
                $telephone = $request->input('telephone');
                $coderand = $request->input('code');
                $code = Code::where('telephone', $telephone)->value('code');

                if ($coderand == $code) {
                    $clientData = $request->except('code');
                    $client = Client::create($clientData);
                    return response()->json([
                        'status' => 'success',
                        'code' => 201,
                        'message' => 'Compte Client créé avec succès.',
                        'client_id' => $client->id], 201);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'code' => 500,
                        'message' => 'Code de Confirmation non valide.'], 500);
                }
           }
      }catch (Throwable $th) {
        return response()->json([
            'status' => 'error',
            'code' => 500,
            'message' => $th->getMessage()], 500);   }
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
                'email' => ['required', 'email'],
                'telephone' => ['required', 'numeric', 'digits:8'],
                'icone' => 'required'
            ],
            [
                'required' => ':attribute est obligatoire',
                'numeric' => ':attribute doit être que des chiffres',
                'digits' => ':attribute doit être de 8 chiffres',
                'email.email' => 'L\'adresse email doit être une adresse email',
            ],
            [
                'email' => "L'adresse mail",
                'telephone' => "Le numéro de téléphone",
                'icone' => "La photo de profil"

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


  /*  public function message(){

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
        $request->validate([
            'message' => 'required|string',
        ]);

        $message = Message::create($request->all());

        event(new MessagesEvent($message));
        broadcast(new MessagesEvent($message));


        return response()->json(['message' => 'message enregistré avec succès', 'message'=> $message->message]);
    }*/


}
