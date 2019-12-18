<?php

namespace Modules\Auth\Transformers;

use Modules\Auth\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'picture' => $user->picture,
            'permissions' => $user->permissions,
            'user_settings' =>  null,
            'type' => $user->type,
            'company' => $user->company,
            'token_sharing' => !empty($user->token_sharing)? $user->token_sharing: null
        ];
    }
}