<?php


use App\Models\Salary;
use App\Models\SalaryDetail;
use App\Models\Staff;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminReadSalaryDetailTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = 'api/admin/salary-detail/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->staff();
        $this->staff = Staff::factory()->create([
            'user_id' => $this->user->id,
            'id' => 999,
        ]);
        $this->salary = Salary::factory()->create([
            'staff_id' => $this->staff->id
        ]);
        $this->salaryDetails = SalaryDetail::factory(2)->create([
            'salary_id' => $this->salary->id
        ]);
    }

    /** @test */
    public function it_can_read_staff_own_monthly_salary_details(): void
    {
        $user = $this->staff();
        $staff = Staff::factory()->create([
            'user_id' => $user->id,
            'id' => 888,
        ]);
        $salary2 = Salary::factory()->create([
            'staff_id' => $staff->id
        ]);
        $detail2 = SalaryDetail::factory()->create([
            'salary_id' => $salary2->id
        ]);
        $ids = $this->fetchSalaryIds();

        $this->assertTrue($ids->contains($this->salaryDetails[0]->id));
        $this->assertTrue($ids->contains($this->salaryDetails[1]->id));
        $this->assertFalse($ids->contains($detail2->id));
    }

    /** @test */
    public function it_gets_404_if_salary_not_found(): void
    {
        $this->salary->id = 9999999;
        $message = $this->fetch()->assertNotFound()->json('message');
        $this->assertEquals($message, 'record not found');
    }

    /** @test */
    public function it_gets_404_if_salary_owner_is_the_login_staff(): void
    {
        $user = $this->staff();
        $staff = Staff::factory()->create([
            'user_id' => $user->id,
            'id' => 888,
        ]);
        $salary2 = Salary::factory()->create([
            'staff_id' => $staff->id
        ]);
        $detail2 = SalaryDetail::factory()->create([
            'salary_id' => $salary2->id
        ]);
        $message = $this->fetch([], $user)->assertNotFound()->json('message');
        $this->assertEquals($message, 'hey this is not your record');
    }

    /** @test */
    public function admin_ca_view_any_salary_detail(): void
    {
        $ids = $this->fetch([], $admin = $this->admin())->assertSuccessful()->collect('data')->pluck('id');

        $this->assertNotEquals($admin->id,$this->salaryDetails[0]->staff_id);
        $this->assertNotEquals($admin->id,$this->salaryDetails[1]->staff_id);
        $this->assertTrue($ids->contains($this->salaryDetails[0]->id));
        $this->assertTrue($ids->contains($this->salaryDetails[1]->id));
    }

    protected function fetch($query = [], $as = null, $id = null)
    {
        $id ??= $this->salary->id;
        $this->endpoint = $this->endpoint . $id;
        return $this->fetchAsStaff($query, $as);
    }

    public function fetchSalaryIds($query = [], $as = null, $id = null)
    {
        $as ??= $this->user;
        return $this->fetch($query, $as, $id)->collect('data')->pluck('id');
    }
}
