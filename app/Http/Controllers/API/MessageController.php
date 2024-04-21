<?php

namespace App\Http\Controllers\API;

use App\Events\MessageReadEvent;
use App\Events\MessagesEvent;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($clientId)
    {
        $discussions = Client::select(
                'clients.id as interlocuteur_id',
                'clients.nom',
                'clients.prenom',
                'clients.icone as avatar',
                DB::raw('SUM(CASE WHEN messages.read_at IS NULL AND messages.id_exp <> ' . $clientId . ' THEN 1 ELSE 0 END) as nombre_messages_non_lus'),
                DB::raw('MAX(messages.date_envoi) as date_dernier_message'),
                DB::raw('(SELECT message FROM messages WHERE (messages.id_exp = ' . $clientId . ' AND messages.id_dest = clients.id) OR (messages.id_dest = ' . $clientId . ' AND messages.id_exp = clients.id) ORDER BY messages.date_envoi DESC LIMIT 1) as dernier_message')
            )
            ->leftJoin('messages', function ($join) use ($clientId) {
                $join->on('clients.id', '=', 'messages.id_exp')
                    ->where('messages.id_dest', '=', $clientId)
                    ->orWhere(function ($query) use ($clientId) {
                        $query->on('clients.id', '=', 'messages.id_dest')
                            ->where('messages.id_exp', '=', $clientId);
                    });
            })
            ->where(function ($query) use ($clientId) {
                $query->where('messages.id_exp', '=', $clientId)
                    ->orWhere('messages.id_dest', '=', $clientId);
            }) // Ajout de la condition pour inclure uniquement les discussions du client
            ->where('clients.id', '<>', $clientId) // Exclure le client lui-même
            ->groupBy('clients.id', 'clients.nom', 'clients.prenom', 'clients.icone')
            ->get();

        return response()->json(['discussions' => $discussions], 200);
    }




    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     $messageContent = $request->input('message');
    //     $expediteurId = $request->input('exp_id');
    //     $destinataireId = $request->input('dest_id');

    //     // Créez un nouveau message en utilisant la fonction du modèle
    //     $message = Message::create([
    //         'message' => $messageContent,
    //         'id_exp' => $expediteurId,
    //         'id_dest' => $destinataireId,
    //         'date_envoi' => now(), ]);

    //         event(new MessagesEvent($message));

    //         return response()->json([
    //             'status' => 'success',
    //             'code' => 201,
    //             'message' => 'Message envoye'], 201);
    // }

    public function message(Request $request)
    {
        $messageContent = $request->input('message');
        $expediteurId = $request->input('exp_id');
        $destinataireId = $request->input('dest_id');

        // Créez un nouveau message en utilisant la fonction du modèle
        $message = Message::create([
            'message' => $messageContent,
            'id_exp' => $expediteurId,
            'id_dest' => $destinataireId,
            'date_envoi' => now()
        ]);

        //event(new MessageReadEvent($message));

        return response()->json([
            'status' => 'success',
            'code' => 201], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Message $message)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Message $message)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Message $message)
    {
        //
    }

    public function messagesBetweenClients($clientId, $interlocuteurId)
    {
        $messages = Message::select(
            'messages.*',
            'clients.nom as interlocuteur_nom',
            'clients.prenom as interlocuteur_prenom',
            'clients.icone as interlocuteur_avatar'
        )
        ->join('clients', function ($join) use ($clientId, $interlocuteurId) {
            $join->on(function ($query) use ($clientId, $interlocuteurId) {
                $query->on('messages.id_exp', '=', 'clients.id')
                    ->where('messages.id_dest', '=', $clientId);
            })
            ->orWhere(function ($query) use ($clientId, $interlocuteurId) {
                $query->on('messages.id_dest', '=', 'clients.id')
                    ->where('messages.id_exp', '=', $clientId);
            });
        })
        ->where(function ($query) use ($clientId, $interlocuteurId) {
            $query->where('messages.id_exp', $clientId)
                ->where('messages.id_dest', $interlocuteurId)
                ->orWhere(function ($query) use ($clientId, $interlocuteurId) {
                    $query->where('messages.id_dest', $clientId)
                        ->where('messages.id_exp', $interlocuteurId);
                });
        })
        ->orderBy('messages.date_envoi', 'asc')
        ->get();

        $msgs = Message::where('id_dest', $clientId)
                        ->where('id_exp', $interlocuteurId)
                        ->whereNull('read_at')
                        ->get();

        foreach ($msgs as $msg) {
            DB::table('messages')->where('id','=',$msg->id)->update(['read_at'=>now()]);
        }

        return response()->json(['messages' => $messages], 200);

    }

}
