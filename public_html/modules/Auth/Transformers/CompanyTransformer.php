<?php

namespace Modules\Auth\Transformers;

use Modules\Auth\Models\Company;
use Modules\Auth\Models\User;
use League\Fractal\TransformerAbstract;

class CompanyTransformer extends TransformerAbstract
{
    public function transform(Company $company)
    {
        return $company->toArray();
    }
}