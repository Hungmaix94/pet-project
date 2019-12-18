<?php

namespace Modules\Auth\Validations;

use App\Base\Validator;

class UserValidation extends Validator
{

    /**
     * Get validation rules
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required',
            'user_settings'=> 'required'
        ];
    }

    /**
     * Get validation labels
     *
     * @return array
     */
    public function messages()
    {
        return [

        ];
    }
}