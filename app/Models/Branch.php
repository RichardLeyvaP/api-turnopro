<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Branch extends Model
{
    use HasFactory;
    use HasRelationships;

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
    public function businessType()
    {
        return $this->belongsTo(BusinessTypes::class, 'business_type_id');
    }
    public function branchstores(){
        return $this->belongsToMany(Store::class)->withTimestamps();
    }

    public function stores(){
        return $this->belongsToMany(Store::class, 'branch_store')->withTimestamps();
    }

    public function branchServices(){
        return $this->hasMany(BranchService::class);
    }

    public function services(){
        return $this->belongsToMany(Service::class)->withPivot('id', 'ponderation')->withTimestamps();
    }

    public function professionals(){
        return $this->belongsToMany(Professional::class)->withPivot('ponderation','id')->withTimestamps();
    }

    public function workplaces()
    {
        return $this->hasMany(Workplace::class);
    }

    public function schedule()
    {
        return $this->hasOne(Schedule::class);
    }

    public function rules(){
        return $this->belongsToMany(Rule::class, 'branch_rule')->withPivot('id')->withTimestamps();
    }

    public function tails(){
        return $this->hasManyDeep(Tail::class, ['branch_professional' ,Professional::class, ClientProfessional::class, Car::class, Reservation::class]);
    }
    /*public function reservations(){
        return $this->hasManyDeep(Reservation::class, ['branch_professional' ,Professional::class, ClientProfessional::class, Car::class]);
    }*/

    public function cars(){
        return $this->hasManyDeep(Car::class, ['branch_professional' ,Professional::class, ClientProfessional::class]);
    }

    public function notifications(){
        return $this->HasMany(Notification::class);
    }

    public function finances(){
        return $this->HasMany(Finance::class);
    }

    public function boxes(){
        return $this->hasOne(Box::class);
    }

    public function cardGift(){
        return $this->hasMany(CardGift::class);
    }

    public function records()
    {
        return $this->hasMany(Record::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function professionalPayments()
    {
        return $this->hasMany(ProfessionalPayment::class, 'branch_id');
    }

    public function associates(){
        return $this->belongsToMany(Associated::class, 'associate_branch')->withPivot('id')->withTimestamps();
    }

    public function clientSurveys(){
        return $this->hasMany(ClientSurvey::class);
    }

}