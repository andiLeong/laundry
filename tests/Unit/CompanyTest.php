<?php

namespace Tests\Unit;


use App\Models\Company;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_can_all_company_information(): void
    {
        $company = new Company(config());
        $company->insert([
            'id' => 1,
            'name' => 'test',
            'address' => null
        ]);

        $this->assertEquals(config('company.company'), $company->all());
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_get_all_users()
    {
        $company = new Company(config());
        $company->insert([
            'id' => 1,
            'name' => 'test',
            'address' => null
        ]);

        $company->insertUser([
            'id' => 2,
            'company_id' => 1,
        ]);

        $company->insertUser([
            'id' => 3,
            'company_id' => 1,
        ]);

        $this->assertEquals(config('company.users'), $company->users());
    }

    /** @test */
    public function it_can_get_single_user()
    {
        $company = new Company(config());
        $company->insert([
            'id' => 1,
            'name' => 'test',
            'address' => null
        ]);

        $company->insertUser([
            'id' => 2,
            'company_id' => 1,
        ]);

        $company->insertUser([
            'id' => 3,
            'company_id' => 1,
        ]);

        $user = $company->getIdByUser(3);

        $this->assertEquals(['id' => 3, 'company_id' => 1], $user);
    }

    /** @test */
    public function it_can_find_company_by_id()
    {
        $target = [
            'id' => 2,
            'name' => 'test',
            'address' => null
        ];
        $company = new Company(config());
        $company->insert([
            'id' => 1,
            'name' => 'test',
            'address' => null
        ]);

        $company->insert($target);

        $this->assertNull($company->find(99));
        $this->assertEquals($target, $company->find(2));
    }
}
