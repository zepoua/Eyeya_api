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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
                'icone' => ['required', 'mimes:jpeg,png,jpg', 'max:3072'],
            ],
            [
                'required' => ':attribute est obligatoire.',
                'unique' => ':attribute existe déjà.',
                'numeric' => ':attribute doit être que des chiffres.',
                'digits' => ':attribute doit être de 8 chiffres.',
                'email.email' => 'L\'adresse email doit être une adresse email.',
                'mimes' => ':attribute doit être de type :values.',
                'max' => ':attribute ne doit pas dépasser :max kilo-octets.',
            ],
            [
                'nom' => "Le nom",
                'prenom' => "Le prénom",
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

                        $parametre = [
                            'username'=>'tikegne',
                            'password'=>'Tikegne2@21',
                            'destination'=>'228'.$request->telephone,
                            'source'=>'EYEYA',
                            'message'=>'Votre code de confirmation de votre inscription sur l\'application EYEYA est : '.$coderand.'.'];
                        $reponse_sms = Http::get('http://sendsms.e-mobiletech.com/',$parametre);

						$verif = DB::table('codes')->where('telephone','=',$request->input('telephone'))->get();
						if(count($verif)==0){
							$new_code = new Code;
							$new_code->code = $coderand;
							$new_code->telephone = $request->input('telephone');
							$new_code->save();
						}else{
							DB::table('codes')->where('telephone','=',$request->input('telephone'))->update(['code'=>$coderand]);
						}

                        return response()->json([
                            'status' => 'success',
                            'code' => 201,
                            'message' => 'Code de confirmation d\'inscription envoyé.']);


               }
          }catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Une erreur s\'est produite lors de l\'envoi du code de confirmation. Verifiez votre connexion et réessayez.'], 500);
            }
    }


    public function store(Request $request)
    {
        // try {
        //     $validator = validator(
        //         $request->all(),
        //         [
        //             'code' => ['required', 'numeric', 'digits:6'],
        //         ],
        //         [
        //             'required' => ':attribute est obligatoire.',
        //             'numeric' => ':attribute doit être que des chiffres.',
        //             'digits' => ':attribute doit être de 6 chiffres.',
        //         ],
        //         [
        //             'code' => "Le code de confirmation",
        //         ]
        //        );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'status' => 'error',
        //         'code' => 500,
        //         'message' => $th->getMessage()], 500);        }

        try{
            // if($validator->fails()){
            //     return response()->json([
            //         'status' => 'error',
            //         'code' => 500,
            //         'message' => $validator->errors()->first()], 500);
            // }else{
            //     $telephone = $request->input('telephone');
            //     $coderand = $request->input('code');
            //     $code = Code::where('telephone', $telephone)->value('code');

            //     if ($coderand == $code) {
                    $icone = $request->icone->getClientOriginalName();
                    $request->icone->move(public_path('images'), $icone);
                    $clientData = $request->except('code');
					$clientData['icone']=$icone;
                    $client = Client::create($clientData);
                    return response()->json([
                        'status' => 'success',
                        'code' => 201,
                        'message' => 'Compte Client créé avec succès.',
                        'client_id' => $client->id], 201);
                // } else {
                //     return response()->json([
                //         'status' => 'error',
                //         'code' => 500,
                //         'message' => 'Code de Confirmation non valide.'], 500);
                // }
        //    }
        }catch (Throwable $th) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $th->getMessage().'/'.$request], 500);
            }
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
    public function update(Request $request)
    {
        $client = Client::find($request->id);
        $validator = validator(
            $request->all(),
            [
                'nom' => ['required', 'string'],
                'prenom' => ['required', 'string'],
                'email' => ['required', 'email', Rule::unique('clients', 'email')->ignore($client->id)],
                'telephone' => ['required', 'numeric', 'digits:8', Rule::unique('clients', 'telephone')->ignore($client->id)],
                'icone' => ['required', 'mimes:jpeg,png,jpg', 'max:3072'],
            ],
            [
                'required' => ':attribute est obligatoire.',
                'unique' => ':attribute existe déjà.',
                'numeric' => ':attribute doit être que des chiffres.',
                'digits' => ':attribute doit être de 8 chiffres.',
                'email.email' => 'L\'adresse email doit être une adresse email.',
                'mimes' => ':attribute doit être de type :values.',
                'max' => ':attribute ne doit pas dépasser :max kilo-octets.',
            ],
            [
                'email' => "L'adresse mail",
                'telephone' => "Le numéro de téléphone",
                'icone' => "La photo de profil",
                'nom' => "Le nom",
                'prenom' => "Le prénom",
            ]
        );

        try {
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'code' => 500,
                    'message' => $validator->errors()->first()
                ]);
            } else {
                $ancienIcone = $client->icone;

                // Mise à jour des données sauf l'icone
                $client->update($request->except('icone'));

                // Validation et sauvegarde de la nouvelle icone si elle est fournie
                if ($request->hasFile('icone') && $request->file('icone')->getClientOriginalName() !== $ancienIcone) {
                    $icone = $request->file('icone')->getClientOriginalName();
                    $request->file('icone')->move(public_path('images'), $icone);
                    $client->icone = $icone;
                    $client->save();
                }

                return response()->json([
                    'status' => 'success',
                    'code' => 201,
                    'message' => 'Votre compte a été mis à jour avec succès.'
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur s\'est produite lors de la mise à jour. Veuillez réessayer plus tard.'
            ], 500);
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

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'data' => $response], 201);
    }

    public function login_code(Request $request)
    {
        $validator = validator(
        $request->all(),
        [
            'telephone' => ['required', 'numeric', 'digits:8'],
        ],
        [
            'required' => ':attribute est obligatoire.',
            'numeric' => ':attribute doit être que des chiffres.',
            'digits' => ':attribute doit être de 8 chiffres.',
        ],
        [

            'telephone' => "Le numero de telephone"
        ]
        );

        try{
            if($validator->fails()){
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => $validator->errors()->first()], 500);
            }else{

                $telephone = $request->input('telephone');
                $telephone = Code::where('telephone', $telephone)->value('telephone');

                if ($telephone) {
                    $coderand = rand(100000, 999999);
                        $parametre = [
                            'username'=>'tikegne',
                            'password'=>'Tikegne2@21',
                            'destination'=>'228'.$request->telephone,
                            'source'=>'EYEYA',
                            'message'=>'Votre code de connexion a l\'application EYEYA est : '.$coderand.'.'];
                        $reponse_sms = Http::get('http://sendsms.e-mobiletech.com/',$parametre);

                        DB::table('codes')->where('telephone', $telephone)->update(['code' => $coderand]);

                    return response()->json([
                        'status' => 'success',
                        'code' => 201,
                        'message' => 'Code de Connexion envoyé.'], 201);

                }else
                    return response()->json([
                        'status' => 'error',
                        'code' => 500,
                        'message' => 'Numéro de téléphone invalide.'], 500);
            }
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Une erreur s\'est produite lors de l\'envoi du code de connexion. Veuillez réssayer plus tard.'],500);
        }

    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'code' => ['required', 'numeric', 'digits:6'],
            ], [
                'required' => ':attribute est obligatoire.',
                'numeric' => ':attribute doit être que des chiffres.',
                'digits' => ':attribute doit être de 6 chiffres.',
            ], [
                'code' => 'Le code de confirmation',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $th->getMessage()
            ], 500);
        }

        try {
            $telephone = $request->input('telephone');
            $coderand = $request->input('code');
            $code = Code::where('telephone', $telephone)->value('code');

            if ($coderand == $code) {
                $user = User::where('telephone1', $telephone)->first();

                if ($user) {
                    $clientId = Client::where('telephone', $telephone)->value('id');
                    $user->user_id = $user->id;
                    $user->id = $clientId;

                    return response()->json([
                        'status' => 'success',
                        'code' => 201,
                        'message' => 'Heureux de vous revoir.',
                        'data' => $user
                    ], 201);
                } else {
                    $client = Client::where('telephone', $telephone)->first();
                    return response()->json([
                        'status' => 'success',
                        'code' => 201,
                        'message' => 'Heureux de vous revoir.',
                        'data' => $client
                    ], 201);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => 'Code de Confirmation non valide.'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Une erreur s\'est produite lors de la connexion. Veuillez ressayer plus tard.'
            ], 500);
        }
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
