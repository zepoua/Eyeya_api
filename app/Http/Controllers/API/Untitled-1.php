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

    // Appliquez le filtre sur la distance si spécifié
    if ($distance) {
        $users = $users->filter(function ($user) use ($distance) {
            switch ($distance) {
                case '0-1':
                    return $user->distance >= 0 && $user->distance <= 1;
                case '1-5':
                    return $user->distance > 1 && $user->distance <= 5;
                case '5-10':
                    return $user->distance > 5 && $user->distance <= 10;
                case '10-20':
                    return $user->distance > 10 && $user->distance <= 20;
                case '20+':
                    return $user->distance > 20;
                // Ajoutez d'autres cas selon vos besoins
            }
        });
    }

    // Ajoutez le calcul de la moyenne des notations
    $usersWithAverage = $users->map(function ($user) {
        $average = $user->notations()->avg('nbre_etoiles');
        $user['moyenne_notations'] = $average !== null ? $average : 0;
        return $user;
    });

    // Triez les utilisateurs par moyenne des notations
    $sortedUsers = $usersWithAverage->sortByDesc('moyenne_notations')->values()->all();

    // Retournez les utilisateurs triés
    return response()->json($sortedUsers);
}

// Fonction pour calculer la distance entre deux points géographiques
private function calculateDistance($lat1, $lon1, $lat2, $lon2)
{
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;

    // Conversion des miles en kilomètres
    $kilometers = $miles * 1.609344;

    return $kilometers;
}
