<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_survey')->withTimestamps();
    }

    public function clientSurveys()
    {
        return $this->hasMany(ClientSurvey::class);
    }
}
