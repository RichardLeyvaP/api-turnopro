<?php

namespace App\Services;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class ClientService
{

    public function store($clients_data)
    {
            $client = new Client();
            $client->name = $clients_data['name'];
            $client->surname = $clients_data['surname'];
            $client->second_surname = $clients_data['second_surname'];
            $client->email = $clients_data['email'];
            $client->phone = $clients_data['phone'];
            $client->user_id = $clients_data['user_id'];
            $client->save();

           return $client;
    }

}