<?php

namespace App\Models;

trait HasCompany
{

    public function isComportedAccount(): bool
    {
        return !is_null($this->company());
    }

    public function getCompanyAttribute()
    {
        return $this->company();
    }

    public function company()
    {
        $company = new Company(config());
        $companyUser = $company->getIdByUser($this->id);
        if (is_null($companyUser)) {
            return null;
        }
        return $company->find($companyUser['company_id']);
    }
}
