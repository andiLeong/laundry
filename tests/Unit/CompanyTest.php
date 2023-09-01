<?php

namespace Tests\Unit;


use App\Models\Company;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\CanCreateCompany;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use LazilyRefreshDatabase, CanCreateCompany;

    private array $fooInc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fooInc = [
            'id' => 1,
            'name' => 'test',
            'address' => null
        ];
    }

    /** @test */
    public function it_can_all_company_information(): void
    {
        $company = new Company();
        $company->insert($this->fooInc);

        $this->assertEquals(config('company.company'), $company->all());
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_get_all_users()
    {
        $company = $this->setupCompanyAndUser([2, 3], $this->fooInc);
        $this->assertEquals(config('company.users'), $company->users());
    }

    /** @test */
    public function it_can_get_single_user()
    {
        $company = $this->setupCompanyAndUser([2, 3], $this->fooInc);
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
        $company = new Company();
        $company->insert($this->fooInc);

        $company->insert($target);

        $this->assertNull($company->find(99));
        $this->assertEquals($target, $company->find(2));
    }
}
