<?php

namespace Modules\Auth\Models;

use App\Base\Model;
use Illuminate\Support\Arr;
use Modules\JD\Models\JobDescription;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $table = 'company';

    protected $fillable = [
        'id', 'name', 'logo', 'cover', 'description', 'status', 'credit', 'area'
    ];


    public function users(){
        return $this->hasMany(User::class, 'company_id', 'id');
    }
}