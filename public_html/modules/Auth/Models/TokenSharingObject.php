<?php

namespace Modules\Auth\Models;

use App\Base\Model;
use Illuminate\Support\Arr;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Auth\Authenticatable;
use Modules\JD\Models\JdShare;
use Modules\JD\Models\JobDescription;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableInterface;

class TokenSharingObject extends Model
{
    protected $table = "token_sharing_objects";
    protected $fillable = [
        'token_sharing_id', 'object_ids', 'object_type'
    ];
    const SHARE_JD = 'jd';
    const SHARE_CV = 'cv';
}