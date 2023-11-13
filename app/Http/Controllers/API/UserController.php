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
         ->limit(5)
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


    public function store_verification(Request $request)
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
                    'latitude' => 'required',
                    'longitude' => 'required',
                    'telephone1' => ['required', 'numeric', 'digits:8', 'unique:users,telephone1'],
                    'telephone2' => ['numeric', 'digits:8', 'unique:users,telephone2'],
                    'qualification' => 'required',
                    'experience' => 'required',
                    'description' => 'required',
                    'image1' => 'required',
                    'image2' => 'required',
                    'domaine_id' => 'required',
                ],
                [
                    'string' => ':attribute doit être une chaîne de caractère',
                    'required' => ':attribute est obligatoire',
                    'unique' => ':attribute existe déjà',
                    'numeric' => ':attribute doit être que des chiffres',
                    'digits' => ':attribute doit être de 8 chiffres',
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

                $coderand = rand(100000, 999999);

                $parametre = [
                    'username'=>'tikegne',
                    'password'=>'Tikegne2@21',
                    'destination'=>'228'.$request->telephone1,
                    'source'=>'EYEYA',
                    'message'=>'Votre code de confirmation de votre inscription sur l\'application EYEYA est : '.$coderand];
                $reponse_sms = Http::get('http://sendsms.e-mobiletech.com/',$parametre);

                $new_code = new Code();
                $new_code->code = $coderand;
                $new_code->telephone = $request->input('telephone1');
                $new_code->save();

                return response()->json([
                    'status' => 'success',
                    'code' => 201,
                    'message' => 'Code de confirmation envoye.']);
            }
        }catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Une erreur s\'est produite lors de l\'envoi du code confirmation.'], 500);}
    }
    public function store(Request $request)
    {
        try{
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
        } catch (Throwable $th) {
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

                    $telephone = $request->input('telephone1');
                    $coderand = $request->input('code');
                    $code = Code::where('telephone', $telephone)->value('code');

                    if ($coderand == $code) {

                        try {

                            $client = new Client;
                            $client->nom = $request->input('nom');
                            $client->prenom = $request->input('prenom');
                            $client->email = $request->input('email');
                            $client->telephone = $request->input('telephone1');
                            $client->icone = $request->input('image1');
                            $client->save();

                            $userData = $request->except('code');
                            $user = User::create($userData);

                            return response()->json([
                                'status' => 'success',
                                'code' => 201,
                                'message' => 'Compte Professionnel créé avec succès.',
                                'client_id' => $client->id,
                                'user_id' => $user->id], 201);

                        } catch (\Exception $e) {
                            return response()->json([
                                'status' => 'error',
                                'code' => 500,
                                'message' => 'Adresse Email ou numero de telephone deja utilise.'], 500);
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
                'message' => 'Une erreur s\'est produite lors de l\'inscription.'], 500); }
    }
    public function client_user(Request $request)
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
                    'latitude' => 'required',
                    'longitude' => 'required',
                    'telephone1' => ['required', 'numeric', 'digits:8', 'unique:users,telephone1'],
                    'telephone2' => ['numeric', 'digits:8', 'unique:users,telephone2'],
                    'qualification' => 'required',
                    'experience' => 'required',
                    'description' => 'required',
                    'image1' => 'required',
                    'image2' => 'required',
                    'domaine_id' => 'required',
                ],
                [
                    'string' => ':attribute doit être une chaîne de caractère',
                    'required' => ':attribute est obligatoire',
                    'unique' => ':attribute existe déjà',
                    'numeric' => ':attribute doit être que des chiffres',
                    'digits' => ':attribute doit être de 8 chiffres',
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

                User::create($request->all());

                return response()->json([
                    'status' => 'success',
                    'code' => 201,
                    'message' => 'Professionnel cree avec succes.']);
            }
        }catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Une erreur s\'est produite lors de l\'inscription.'], 500);}
    }

    public function show(Request $request)
    {
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


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $client_id = $request->input('id');
        $client_nom = $request->input('nom');
        $client_prenom = $request->input('prenom');
        $client_email = $request->input('email');
        $client_telephone = $request->input('telephone1');
        $client_icone = $request->input('image1');

        try{
            $validator = validator(
                $request->all(),
                [
                    'nom_entreprise' => 'string',
                    'nom' => ['required', 'string'],
                    'prenom' => ['required', 'string'],
                    'email' => 'required',
                    'password' => ['required', 'min:8'],
                    'adresse' => 'required',
                    'latitude' => 'required',
                    'longitude' => 'required',
                    'telephone1' => 'required',
                    'telephone2' => 'required',
                    'qualification' => 'required',
                    'experience' => 'required',
                    'description' => 'required',
                    'image1' => 'required',
                    'image2' => 'required',
                    'domaine_id' => 'required',
                ],
                [
                    'string' => ':attribute doit être une chaîne de caractère',
                    'required' => ':attribute est obligatoire',
                    'numeric' => ':attribute doit être que des chiffres',
                    'digits' => ':attribute doit être de 8 chiffres',
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
                $userData = $request->except('id');
                $user->update($userData);

                $client = Client::findOrFail($client_id);
                $client->update([
                    'nom' => $client_nom,
                    'prenom' => $client_prenom,
                    'email' => $client_email,
                    'telephone' => $client_telephone,
                    'icone' => $client_icone,
                ]);
                return response()->json([
                    'status' => 'success',
                    'code' => 201,
                    'message' => 'Mise a jour reussie'], 201);
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

    public function domaine()
    {
        $domainesWithCount = DB::table('domaines')
        ->select('domaines.id', 'domaines.domaine_lib', 'domaines.icone', DB::raw('COUNT(users.id) as nombre_users'))
        ->leftJoin('users', 'domaines.id', '=', 'users.domaine_id')
        ->groupBy('domaines.id')
        ->get();
        return response()->json($domainesWithCount);

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

        // Retourne la moyenne au format JSON
        return response()->json(['message'=>'moyenne calcule', 'moyenne_notations'=>$averageStars]);
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
}
