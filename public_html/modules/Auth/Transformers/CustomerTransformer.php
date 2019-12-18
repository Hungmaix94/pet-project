<?php

namespace Modules\Auth\Transformers;

use Modules\Auth\Models\User;
use League\Fractal\TransformerAbstract;

class CustomerTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'picture' => $user->picture,
            'company' => $user->company
        ];
    }
}