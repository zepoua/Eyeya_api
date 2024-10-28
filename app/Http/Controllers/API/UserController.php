<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Domaine;
use App\Models\Notation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Code;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;
use Illuminate\Support\Str;
use App\Events\MessagesEvent;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     */
    // Fonction pour calculer la distance entre deux points géographiques
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $meters = $dist * 60 * 1852; // Conversion des degrés en mètres

        return $meters;
    }

    public function index_global()
    {

         $users = User::select('users.*', 'domaines.domaine_lib as domaine_lib')
         ->leftJoin('domaines', 'users.domaine_id', '=', 'domaines.id')
         ->orderBy('users.id', 'desc')
         ->limit('100')
         ->get();

         $usersWithAverage = $users->map(function ($user) {
             $average = $user->notations()->avg('nbre_etoiles');
             $user['moyenne_notations'] = $average !== null ? $average : 0;
             return $user;
         });

         $sortedUsers = $usersWithAverage->sortByDesc('moyenne_notations')->values()->all();
         return response()->json($sortedUsers);

    }

    public function index(Request $request)
    {
        $domaineId = $request->input('domaine_id');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $distance = $request->input('distance');

        $users = User::select(
            'users.*',
            'domaines.domaine_lib as domaine_lib'
        )
            ->leftJoin('domaines', 'users.domaine_id', '=', 'domaines.id')
            ->when($domaineId, function ($query) use ($domaineId) {
                $query->where('users.domaine_id', $domaineId);
            });

        // Exécutez la requête principale pour obtenir les utilisateurs
        $users = $users->get();

        // Ajoutez la colonne de distance après la requête principale
        $users = $users->map(function ($user) use ($latitude, $longitude) {
            $user->distance = $this->calculateDistance($latitude, $longitude, $user->latitude, $user->longitude);
            return $user;
        });

        $usersWithAverage = $users->map(function ($user) {
            $average = $user->notations()->avg('nbre_etoiles');
            $user['moyenne_notations'] = $average !== null ? $average : 0;
            return $user;
        });

        // Appliquez le filtre sur la distance si spécifié
        if ($distance) {
            $users = $usersWithAverage->filter(function ($user) use ($distance) {
                switch ($distance) {
                    case '1':
                        return $user->distance >= 0 && $user->distance <= 9999;
                    case '2':
                        return $user->distance >= 10000 && $user->distance <= 2000;
                    case '3':
                        return $user->moyenne_notations == 5;
                    case '4':
                        return $user->moyenne_notations >= 4 && $user->moyenne_notations <= 4.999;
                    case '5':
                        return $user->moyenne_notations >= 3 && $user->moyenne_notations <= 3.999;
                    case '6':
                        return $user;
                    // Ajoutez d'autres cas selon vos besoins
                }
            });
        }
        // Triez les utilisateurs par moyenne des notations
        $sortedUsers = $users->sortByDesc('moyenne_notations')->values()->all();
        // Retournez les utilisateurs triés
        return response()->json($sortedUsers);
    }

    public function global_search(Request $request)
    {
        // Récupérer le domaine_id et le terme de recherche depuis la requête
        $search= $request->input('search');

        // Sélectionner les colonnes nécessaires et faire la jointure avec la table 'domaines'
        $users = User::select('users.*', 'domaines.domaine_lib as domaine_lib')
            ->leftJoin('domaines', 'users.domaine_id', '=', 'domaines.id')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('nom_entreprise', 'LIKE', '%' . $search . '%')
                            ->orWhere('nom', 'LIKE', '%' . $search . '%')
                            ->orWhere('prenom', 'LIKE', '%' . $search . '%')
                            ->orWhere('domaine_lib', 'LIKE', '%' . $search . '%')
                            ->orWhere('adresse', 'LIKE', '%' . $search . '%');
                });
            })->get();

        // Ajouter la colonne 'moyenne_notations' à la collection
        $usersWithAverage = $users->map(function ($user) {
            $average = $user->notations()->avg('nbre_etoiles');
            $user['moyenne_notations'] = $average !== null ? $average : 0;
            return $user;
        });

        $sortedUsers = $usersWithAverage->sortByDesc('moyenne_notations')->values()->all();
        return response()->json($sortedUsers);
    }

    public function search(Request $request)
    {
        // Récupérer le domaine_id et le terme de recherche depuis la requête
        $domaineId = $request->input('domaine_id');
        $search= $request->input('search');

        // Sélectionner les colonnes nécessaires et faire la jointure avec la table 'domaines'
        $users = User::select('users.*', 'domaines.domaine_lib as domaine_lib')
            ->leftJoin('domaines', 'users.domaine_id', '=', 'domaines.id')
            ->when($domaineId, function ($query) use ($domaineId) {
                $query->where('users.domaine_id', $domaineId);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('nom_entreprise', 'LIKE', '%' . $search . '%')
                            ->orWhere('nom', 'LIKE', '%' . $search . '%')
                            ->orWhere('prenom', 'LIKE', '%' . $search . '%')
                            ->orWhere('adresse', 'LIKE', '%' . $search . '%');
                });
            })->get();

        // Ajouter la colonne 'moyenne_notations' à la collection
        $usersWithAverage = $users->map(function ($user) {
            $average = $user->notations()->avg('nbre_etoiles');
            $user['moyenne_notations'] = $average !== null ? $average : 0;
            return $user;
        });

        $sortedUsers = $usersWithAverage->sortByDesc('moyenne_notations')->values()->all();
        return response()->json($sortedUsers);
    }

    public function domaine_search(Request $request)
    {
            $search = $request->input('search', '');

            $domainesWithCount = Domaine::with('users')
            ->select('domaines.id', 'domaines.domaine_lib', 'domaines.icone', DB::raw('COUNT(users.id) as nombre_users'))
            ->leftJoin('users', 'domaines.id', '=', 'users.domaine_id')
            ->groupBy('domaines.id')
            ->where(function ($query) use ($search) {
                $query->where('domaine_lib', 'LIKE', '%'.$search.'%');
            })->orderBy('nombre_users', 'desc')
            ->get();
         return response()->json($domainesWithCount);

    }

    // public function store(Request $request)
    // {
    //     try{
    //         $validator = validator(
    //             $request->all(),
    //             [
    //                 'nom_entreprise' => ['required', 'string'],
    //                 'nom' => ['required', 'string'],
    //                 'prenom' => ['required', 'string'],
    //                 'email' => ['required', 'email', 'unique:users,email'],
    //                 'password' => ['required', 'min:8'],
    //                 'adresse' => 'required',
    //                 'latitude' => 'required',
    //                 'longitude' => 'required',
    //                 'telephone1' => ['required', 'numeric', 'digits:8', 'unique:users,telephone1'],
    //                 'telephone2' => ['required','numeric', 'digits:8'],
    //                 'qualification' => ['required', 'string'],
    //                 'experience' => ['required', 'string'],
    //                 'description' => ['required', 'string'],
    //                 'domaine_id' => 'required',
    //                 'image1' => ['required', 'mimes:jpeg,png,jpg', 'max:3072'],
    //                 'image2' => ['required', 'mimes:jpeg,png,jpg', 'max:3072'],
    //                 'image3' => ['nullable', 'mimes:jpeg,png,jpg', 'max:3072'],
    //             ],
    //             [
    //                 'string' => ':attribute doit être une chaîne de caractère.',
    //                 'required' => ':attribute est obligatoire.',
    //                 'unique' => ':attribute existe déjà.',
    //                 'numeric' => ':attribute doit être que des chiffres.',
    //                 'digits' => ':attribute doit être de 8 chiffres.',
    //                 'min' => ':attribute doit contenir 8 caractères minimum.',
    //                 'email.email' => 'L\'adresse email doit être une adresse email.',
    //                 'mimes' => ':attribute doit être de type :values.',
    //                 'max' => ':attribute ne doit pas dépasser :max kilo-octets.',
    //             ],
    //             [
    //                 'nom_entreprise' => "Le nom de l'entreprise",
    //                 'nom' => "Le nom",
    //                 'prenom' => "Le prénom",
    //                 'email' => "L'adresse mail",
    //                 'password' => "Le mot de passe",
    //                 'adresse' => "L'adresse",
    //                 'latitude' => "La position",
    //                 'longititude' => "La position",
    //                 'telephone1' => "Le numéro de téléphone 1",
    //                 'telephone2' => "Le numéro de téléphone 2",
    //                 'qualification' => "La/les qualification(s)",
    //                 'experience' => "L'/les expérience(s)",
    //                 'description' => "La description",
    //                 'image1' => "La photo de profil",
    //                 'image2' => "La photo de couverture",
    //                 'domaine_id' => "Le domaine d'activité",
    //                 'image3' => "La photo optionelle",

    //             ]
    //            );
    //         } catch (Throwable $th) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'code' => 500,
    //                 'message' => $th->getMessage()], 500);            }

    //     try{
    //         if($validator->fails()){
    //             return response()->json([
    //                 'status' => 'error',
    //                 'code' => 500,
    //                 'message' => $validator->errors()->first()], 500);
    //         }else{

    //             $imageName1 = $request->image1->getClientOriginalName();
    //             $request->image1->move(public_path('images'), $imageName1);

    //             $imageName2 = $request->image2->getClientOriginalName();
    //             $request->image2->move(public_path('images'), $imageName2);


    //             if ($request->hasFile('image3')) {
    //                 $imageName3 = $request->image3->getClientOriginalName();
    //                 $request->image3->move(public_path('images'), $imageName3);

    //                 $userData = $request->all();
    //                 $userData['image1']=$imageName1;
    //                 $userData['image2']=$imageName2;
    //                 $userData['image3']=$imageName3;
    //                 $user = User::create($userData);
    //             }else{
    //                 $userData = $request->all();
    //                 $userData['image1']=$imageName1;
    //                 $userData['image2']=$imageName2;
    //                 $user = User::create($userData);
    //             }

    //             $client = new Client;
    //             $client->nom = $request->input('nom');
    //             $client->prenom = $request->input('prenom');
    //             $client->email = $request->input('email');
    //             $client->telephone = $request->input('telephone1');
    //             $client->icone = $imageName1;
    //             $client->save();

    //             return response()->json([
    //                 'status' => 'success',
    //                 'code' => 201,
    //                 'message' => 'Compte Professionnel créé avec succès.',
    //                 'client_id' => $client->id,
    //                 'user_id' => $user->id], 201);

    //         }
    //     }catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'code' => 500,
    //             'message' => 'Une erreur s\'est produite lors de l\'envoi du code de confirmation. Veuillez réessayer plus tard.'], 500);}
    // }

    public function store_verification(Request $request)
    {
        try{
            $validator = validator(
                $request->all(),
                [
                    'nom_entreprise' => ['required', 'string'],
                    'nom' => ['required', 'string'],
                    'prenom' => ['required', 'string'],
                    'email' => ['required', 'email', 'unique:users,email'],
                    'password' => ['required', 'min:8'],
                    'adresse' => 'required',
                    'latitude' => 'required',
                    'longitude' => 'required',
                    'telephone1' => ['required', 'numeric', 'digits:8', 'unique:users,telephone1'],
                    'telephone2' => ['nullable','numeric', 'digits:8'],
                    'qualification' => ['required', 'string'],
                    'experience' => ['required', 'string'],
                    'description' => ['required', 'string'],
                    'domaine_id' => 'required',
                    'image1' => ['required', 'mimes:jpeg,png,jpg', 'max:3072'],
                    'image2' => ['required', 'mimes:jpeg,png,jpg', 'max:3072'],
                    'image3' => ['nullable', 'mimes:jpeg,png,jpg', 'max:3072'],
                ],
                [
                    'string' => ':attribute doit être une chaîne de caractère.',
                    'required' => ':attribute est obligatoire.',
                    'unique' => ':attribute existe déjà.',
                    'numeric' => ':attribute doit être que des chiffres.',
                    'digits' => ':attribute doit être de 8 chiffres.',
                    'min' => ':attribute doit contenir 8 caractères minimum.',
                    'email.email' => 'L\'adresse email doit être une adresse email.',
                    'mimes' => ':attribute doit être de type :values.',
                    'max' => ':attribute ne doit pas dépasser :max kilo-octets.',
                ],
                [
                    'nom_entreprise' => "Le nom de l'entreprise",
                    'nom' => "Le nom",
                    'prenom' => "Le prénom",
                    'email' => "L'adresse mail",
                    'password' => "Le mot de passe",
                    'adresse' => "L'adresse",
                    'latitude' => "La position",
                    'longititude' => "La position",
                    'telephone1' => "Le numéro de téléphone 1",
                    'telephone2' => "Le numéro de téléphone 2",
                    'qualification' => "La/les qualification(s)",
                    'experience' => "L'/les expérience(s)",
                    'description' => "La description",
                    'image1' => "La photo de profil",
                    'image2' => "La photo de couverture",
                    'domaine_id' => "Le domaine d'activité",
                    'image3' => "La photo optionelle",

                ]
               );
            } catch (Throwable $th) {
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => $th->getMessage()], 500);            }

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
                    'destination'=>'228'.$request->telephone1,
                    'source'=>'EYEYA',
                    'message'=>'Votre code de confirmation de votre inscription sur l\'application EYEYA est : '.$coderand.'.'];
                $reponse_sms = Http::get('http://sendsms.e-mobiletech.com/',$parametre);

                $verif = DB::table('codes')->where('telephone','=',$request->input('telephone1'))->get();
                if(count($verif)==0){
                    $new_code = new Code;
                    $new_code->code = $coderand;
                    $new_code->telephone = $request->input('telephone1');
                    $new_code->save();
                }else{
                    DB::table('codes')->where('telephone','=',$request->input('telephone1'))->update(['code'=>$coderand]);
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
                'message' => 'Une erreur s\'est produite lors de l\'envoi du code de confirmation. Veuillez réessayer plus tard.'], 500);}
    }

    public function store(Request $request)
    {
        $validator = validator(
            $request->all(),
            [
                'code' => ['required', 'numeric', 'digits:6'],
            ],
            [
                'required' => ':attribute est obligatoire.',
                'numeric' => ':attribute doit être que des chiffres.',
                'digits' => ':attribute doit être de 6 chiffres.',
            ],
            [
                'code' => "Le code de confirmation",
            ]
        );

        try{
            if($validator->fails()){

                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => $validator->errors()->first()], 500);
                }else{

                    $telephone = $request->input('telephone1');
                    $coderand = $request->input('code');
                    $code = Code::where('telephone', $telephone)->value('code');

                    if ($coderand == $code) {
                        try {
							$imageName1 = $request->image1->getClientOriginalName();
							$request->image1->move(public_path('images'), $imageName1);

							$imageName2 = $request->image2->getClientOriginalName();
							$request->image2->move(public_path('images'), $imageName2);


							if ($request->hasFile('image3')) {
								$imageName3 = $request->image3->getClientOriginalName();
								$request->image3->move(public_path('images'), $imageName3);
                                $userData = $request->except('code');
                                $userData['image1']=$imageName1;
                                $userData['image2']=$imageName2;
                                $userData['image3']=$imageName3;
                                $user = User::create($userData);
							}else{
                                $userData = $request->except('code');
                                $userData['image1']=$imageName1;
                                $userData['image2']=$imageName2;
                                $user = User::create($userData);
                            }

                            $client = new Client;
                            $client->nom = $request->input('nom');
                            $client->prenom = $request->input('prenom');
                            $client->email = $request->input('email');
                            $client->telephone = $request->input('telephone1');
                            $client->icone = $imageName1;
                            $client->save();

                            return response()->json([
                                'status' => 'success',
                                'code' => 201,
                                'message' => 'Compte Professionnel créé avec succès.',
                                'client_id' => $client->id,
                                'user_id' => $user->id
                            ], 201);

                        } catch (\Exception $e) {
                            return response()->json([
                                'status' => 'error',
                                'code' => 500,
                                'message' => 'Adresse Email ou numéro de téléphone deja utilisé.'], 500);
                        }

                    } else {
                        return response()->json([
                            'status' => 'error',
                            'code' => 500,
                            'message' => 'Code de Confirmation non valide.'], 500);
                    }
               }
        }catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Erreur lors de l\'inscription. Veuillez réessayer plus tard.'], 500); }
    }

    public function client_user(Request $request)
    {
        $mot= $request->input('password');

        try{
            $validator = validator(
                $request->all(),
                [
                    'nom_entreprise' => ['required', 'string'],
                    'nom' => ['required', 'string'],
                    'prenom' => ['required', 'string'],
                    'email' => ['required', 'email', 'unique:users,email'],
                    'password' => ['required', 'min:8'],
                    'adresse' => 'required',
                    'latitude' => 'required',
                    'longitude' => 'required',
                    'telephone1' => ['required', 'numeric', 'digits:8', 'unique:users,telephone1'],
                    'telephone2' => ['nullable', 'numeric', 'digits:8'],
                    'qualification' => ['required', 'string'],
                    'experience' => ['required', 'string'],
                    'description' => ['required', 'string'],
                    'domaine_id' => 'required',
                    'image1' => ['required', 'mimes:jpeg,png,jpg', 'max:3072'],
                    'image2' => ['required', 'mimes:jpeg,png,jpg', 'max:3072'],
                    'image3' => ['nullable', 'mimes:jpeg,png,jpg', 'max:3072'],
                ],
                [
                    'string' => ':attribute doit être une chaîne de caractère.',
                    'required' => ':attribute est obligatoire.',
                    'unique' => ':attribute existe déjà.',
                    'numeric' => ':attribute doit être que des chiffres.',
                    'digits' => ':attribute doit être de 8 chiffres.',
                    'min' => ':attribute doit contenir 8 caractères minimum.',
                    'email.email' => 'L\'adresse email doit être une adresse email.',
                    'mimes' => ':attribute doit être de type :values.',
                    'max' => ':attribute ne doit pas dépasser :max kilo-octets.',
                ],
                [
                    'nom_entreprise' => "Le nom de l'entreprise",
                    'nom' => "Le nom",
                    'prenom' => "Le prénom",
                    'email' => "L'adresse mail",
                    'password' => "Le mot de passe",
                    'adresse' => "L'adresse",
                    'latitude' => "La position",
                    'longititude' => "La position",
                    'telephone1' => "Le numéro de téléphone 1",
                    'telephone2' => "Le numéro de téléphone 2",
                    'qualification' => "La/les qualification(s)",
                    'experience' => "L'/les expérience(s)",
                    'description' => "La description",
                    'image1' => "La photo de profil",
                    'image2' => "La photo de couverture",
                    'domaine_id' => "Le domaine d'activite",
                    'image3' => "La photo optionelle",

                ]
               );} catch (Throwable $th) {
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => $th->getMessage()], 500);            }

        try{
            if($validator->fails()){
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => $validator->errors()->first()], 500);
            }else{

                $imageName1 = $request->image1->getClientOriginalName();
                $request->image1->move(public_path('images'), $imageName1);

                $imageName2 = $request->image2->getClientOriginalName();
                $request->image2->move(public_path('images'), $imageName2);


                if ($request->hasFile('image3')) {
                    $imageName3 = $request->image3->getClientOriginalName();
                    $request->image3->move(public_path('images'), $imageName3);
                    $userData = $request->except('image1, image2, image3');
                    $userData['image1']=$imageName1;
                    $userData['image2']=$imageName2;
                    $userData['image3']=$imageName3;
                    $user = User::create($userData);
                }else{
                    $userData = $request->except('image1, image2');
                    $userData['image1']=$imageName1;
                    $userData['image2']=$imageName2;
                    $user = User::create($userData);
                }

                return response()->json([
                    'status' => 'success',
                    'code' => 201,
                    'message' => 'Bravo, Vous êtes maintenant un Professionnel.',
                    'user_id'=> $user->id], 201);
            }
        }catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $th->getMessage()], 500);}
    }

    public function show(Request $request)
    {
        $userId = $request->input('user_id');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $user = User::find($userId);
        $user->increment('vues');

        $user = User::select('users.*', 'domaines.domaine_lib as domaine_lib')
        ->leftJoin('domaines', 'users.domaine_id', '=', 'domaines.id')
        ->find($userId);

        if (!$user) {
            // Gérer le cas où l'utilisateur n'est pas trouvé
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $average = $user->notations()->avg('nbre_etoiles'); // Remplacez par la colonne de notation
        $user['moyenne_notations'] = $average !== null ? $average : 0;
        $user->distance = $this->calculateDistance($latitude, $longitude, $user->latitude, $user->longitude);
        $clientId = Client::select('id')
                    ->where('email', '=', $user->email)
                    ->value('id');

        $user['clientId'] = $clientId;
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $user = User::find($request->user_id);
        $client_id = $request->input('id');
        $client_nom = $request->input('nom');
        $client_prenom = $request->input('prenom');
        $client_email = $request->input('email');
        $client_telephone = $request->input('telephone1');

        try{
            $validator = validator(
                $request->all(),
                [
                    'nom_entreprise' => 'string',
                    'nom' => ['required', 'string'],
                    'prenom' => ['required', 'string'],
                    'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
                    'password' => ['required', 'min:8'],
                    'adresse' => 'required',
                    'latitude' => 'required',
                    'longitude' => 'required',
                    'telephone1' => ['required', 'numeric', 'digits:8', Rule::unique('users', 'telephone1')->ignore($user->id)],
                    'telephone2' => ['required', 'numeric', 'digits:8', Rule::unique('users', 'telephone2')->ignore($user->id)],
                    'qualification' => ['required', 'string'],
                    'experience' => ['required', 'string'],
                    'description' => ['required', 'string'],
                    'domaine_id' => 'required',
                    'image1' => ['nullable', 'mimes:jpeg,png,jpg', 'max:3072'],
                    'image2' => ['nullable', 'mimes:jpeg,png,jpg', 'max:3072'],
                    'image3' => ['nullable', 'mimes:jpeg,png,jpg', 'max:3072'],
                ],
                [
                    'string' => ':attribute doit être une chaîne de caractère',
                    'required' => ':attribute est obligatoire',
                    'unique' => ':attribute existe déjà',
                    'numeric' => ':attribute doit être que des chiffres',
                    'digits' => ':attribute doit être de 8 chiffres',
                    'min' => ':attribute doit contenir 8 caractères minimum',
                    'email' => 'L\'adresse email doit être une adresse email',
                    'mimes' => ':attribute doit être de type :values.',
                    'max' => ':attribute ne doit pas dépasser :max kilo-octets.',
                ],
                [
                    'nom_entreprise' => "Le nom de l'entreprise",
                    'nom' => "Le nom",
                    'prenom' => "Le prenom",
                    'email' => "L'adresse email",
                    'password' => "Le mot de passe",
                    'adresse' => "L'adresse",
                    'latitude' => "La position",
                    'longititude' => "La position",
                    'telephone1' => "Le numéro de téléphone 1",
                    'telephone2' => "Le numéro de téléphone 2",
                    'qualification' => "La/les qualification(s)",
                    'experience' => "L'/les expérience(s)",
                    'description' => "La description",
                    'image1' => "La photo de profil",
                    'image2' => "La photo de couverture",
                    'domaine_id' => "Le domaine d'activite",
                    'image3' => "La photo optionelle",

                ]
               );} catch (Throwable $th) {
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => $th->getMessage()], 500);
        }

        try{
            if($validator->fails()){
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => $validator->errors()->first()], 500);
            }else{
                if ($user->nbre_update < 3) {
                    $ancienimage1 = $user->image1;
                    $ancienimage2 = $user->image2;
                    $ancienimage3 = $user->image3;

                    $user->update($request->except('id, image1, image2, image3'));
                    $client = Client::findOrFail($client_id);
                    $client->update([
                        'nom' => $client_nom,
                        'prenom' => $client_prenom,
                        'email' => $client_email,
                        'telephone' => $client_telephone,
                    ]);

                    if ($request->hasFile('image1') && $request->file('image1')->getClientOriginalName() !== $ancienimage1) {
                        $image1 = $request->image1->getClientOriginalName();
                        $request->image1->move(public_path('images'), $image1);
                        $user->image1 = $image1;
                        $user->save();
                        $client->icone = $image1;
                        $client->save();
                    }

                    if ($request->hasFile('image2') && $request->file('image2')->getClientOriginalName() !== $ancienimage2) {
                        $image2 = $request->image2->getClientOriginalName();
                        $request->image2->move(public_path('images'), $image2);
                        $user->image2 = $image2;
                        $user->save();
                    }

                    if ($request->hasFile('image3') && $request->file('image3')->getClientOriginalName() !== $ancienimage3) {
                        $image3 = $request->image3->getClientOriginalName();
                        $request->image3->move(public_path('images'), $image3);
                        $user->image3 = $image3;
                        $user->save();
                    }

                    $user->increment('nbre_update');

                    return response()->json([
                        'status' => 'success',
                        'code' => 201,
                        'message' => 'Mise a jour reussie. Il vous reste '. 3-$user->nbre_update . ' modifications.'], 201);
                }else{
                    return response()->json([
                        'status' => 'error',
                        'code' => 500,
                        'message' => 'Vous avez atteint le nombre maximal de modification de vos informations personnelles.'], 201);
                }

            }

        }catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Une erreur s\'est produite lors de la mise a jour.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['Professionnel supprime avec succès',User::all()]);
    }


    public function domaine($id)
    {
        // Nombre total de domaines
        $totalDomaines = Domaine::count();

        // Nombre total de domaines
        $totalUsers = User::count();

        // Nombre de personnes avec lesquelles $id a discuté
        $nombrePersonnes = DB::table('messages')
            ->select(DB::raw('COUNT(DISTINCT id_dest) as nombre_personnes'))
            ->where('id_exp', $id)
            ->get()[0]->nombre_personnes;

        //liste des domaines
        $domainesWithCount = DB::table('domaines')
            ->select('domaines.id', 'domaines.domaine_lib', 'domaines.icone', DB::raw('COUNT(users.id) as nombre_users'))
            ->leftJoin('users', 'domaines.id', '=', 'users.domaine_id')
            ->groupBy('domaines.id')
            ->get();

        return response()->json([
            'domaines' => $totalDomaines,
            'discussions' => $nombrePersonnes,
            'users' => $totalUsers,
            'liste_domaines' => $domainesWithCount,
        ]);
    }

    public function liste_domaines()
    {
        //liste ded domaines
        $domainesWithCount = Domaine::all();
        return response()->json($domainesWithCount);
    }

        public function login(Request $request)
    {
        $validator = validator(
            $request->all(),
            [
                'email' => ['required', 'email'],
                'password' => 'required'
            ],
            [
                'required' => ':attribute est obligatoire.',
                'email.email' => 'L\'adresse email doit être une adresse email valide.'
            ],
            [
                'email' => "L'adresse email",
                'password' => "Le mot de passe"
            ]
        );

        try {
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => $validator->errors()->first()
                ], 500);
            }

            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials)) {
                $user = auth()->user();
                $clientId = Client::select('id')
                    ->where('email', '=', $user->email)
                    ->value('id');

                // Ajouter le mot de passe hashé à la réponse
                $user['user_id'] = $user->id;
                $user['id'] = $clientId;

                return response()->json([
                    'status' => 'success',
                    'code' => 201,
                    'message' => 'Heureux de vous revoir.',
                    'user_data' => $user
                ], 201);
            } else {
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => 'Identifiants non valides.'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Une erreur s\'est produite lors de la connexion. Veuillez réessayer plus tard.'
            ], 500);
        }
    }

    public function list_commentaire($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $comments = $user->commentaires()->with('client')->get();

            $response = [];

            foreach ($comments as $comment) {
                $response[] = [
                    'commentaire' => $comment->commentaire_lib,
                    'client' => $comment->client->nom,
                    'date' => $comment->created_at,
                    'icone' => $comment->client->icone
                ];
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function list_notation($userId)
    {
        $averageStars = Notation::where('user_id', $userId)
                                ->avg('nbre_etoiles');

        $vues = User::where('id', $userId)->get('vues')[0]->vues;
        // Retourne la moyenne au format JSON
        return response()->json(['message'=>'moyenne calcule', 'moyenne_notations'=>$averageStars, 'vues' => $vues]);
    }

    public function enreg_notation(Request $request)
    {

        $notation = Notation::updateOrCreate(
            ['user_id' => $request->user_id, 'client_id' => $request->client_id],
            ['nbre_etoiles' => $request->nbre_etoiles]
        );

        // Calculer la moyenne des notations
        $averageRating = DB::table('notations')
            ->where('user_id', $notation->user_id)
            ->avg('nbre_etoiles');

        return response()->json($averageRating);
    }

    public function vues(Request $request){

        $userId = $request->input('user_id');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $user = User::select('users.*', 'domaines.domaine_lib as domaine_lib')
        ->leftJoin('domaines', 'users.domaine_id', '=', 'domaines.id')
        ->find($userId);

        if (!$user) {
            // Gérer le cas où l'utilisateur n'est pas trouvé
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $average = $user->notations()->avg('nbre_etoiles'); // Remplacez par la colonne de notation
        $user['moyenne_notations'] = $average !== null ? $average : 0;
        $user->distance = $this->calculateDistance($latitude, $longitude, $user->latitude, $user->longitude);
        $clientId = Client::select('id')
                    ->where('email', '=', $user->email)
                    ->value('id');

        $user['clientId'] = $clientId;
        return response()->json($user);
    }
}
