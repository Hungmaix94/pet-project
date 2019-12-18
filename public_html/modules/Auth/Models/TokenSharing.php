<?php

namespace Modules\Auth\Models;

use App\Base\Model;

class TokenSharing extends Model
{
    protected $table = "token_sharings";

    protected $fillable = [
        'id', 'user_id', 'email', 'access_token', 'access_token_sharing', 'link', 'name', 'phone','is_verify'
    ];

    public function tokenSharingObjects()
    {
        return $this->hasMany(TokenSharingObject::class);
    }
}