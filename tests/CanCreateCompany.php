<?php

namespace Tests;

use App\Models\Company;

trait CanCreateCompany
{

    public function setupCompanyAndUser(array $userIds, array $corporation = null)
    {
        $corporation ??= [
            'id' => 1,
            'name' => 'foo inc',
            'address' => 'manila'
        ];

        $company = new Company();
        $company->insert($corporation);
        foreach ($userIds as $id){
            $company->insertUser([
                'id' => $id,
                'company_id' => $corporation['id'],
            ]);
        }

        return $company;
    }
}
