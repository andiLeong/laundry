<?php


use App\Models\Enum\UserType;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminReadUserTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = 'api/admin/user';
    private string $phone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->phone = '09050887900';
    }

    /** @test */
    public function employee_can_not_access(): void
    {
        $employee = User::factory()->create(['type' => UserType::employee->value]);
        $this->signIn($employee)->getJson($this->endpoint)->assertForbidden();
    }

    /** @test */
    public function list_of_attributes_gets_return(): void
    {
        $users = User::factory(2)->create();
        $service = Service::factory()->create();
        Order::factory()->create([
            'service_id' => $service->id,
            'amount' => 100,
            'user_id' => $users[1]->id,
        ]);

        Order::factory()->create([
            'service_id' => $service->id,
            'amount' => 220,
            'user_id' => $users[1]->id,
        ]);

        $user = $this->fetch()->collect('data')->first(fn($user) => $user['id'] === $users[1]->id);

        $this->assertEquals($users[1]->phone, $user['phone']);
        $this->assertEquals($users[1]->created_at->format('yyyy-mm-dd'), Carbon::parse($user['created_at'])->format('yyyy-mm-dd'));
        $this->assertEquals($users[1]->id, $user['id']);
        $this->assertEquals($users[1]->first_name, $user['first_name']);
        $this->assertEquals($users[1]->last_name, $user['last_name']);
        $this->assertEquals($users[1]->middle_name, $user['middle_name']);
        $this->assertEquals($users[1]->type, $user['type']);
        $this->assertEquals($users[1]->created_at, $user['last_order_date']);
        $this->assertEquals(220 + 100, $user['total_order_amount']);
    }

    /** @test */
    public function sensitive_column_not_hidden(): void
    {
        User::factory()->create(['first_name' => 'kate']);
        $user = $this->fetch()->collect('data')->first();
        $this->assertArrayNotHasKey('password', $user);
    }

    /** @test */
    public function user_is_order_by_latest_record_by_default(): void
    {
        $firstUser = $this->admin();
        $secondUser = User::factory()->create();
        $users = $this->fetch([], $firstUser)->collect('data');

        $this->assertEquals($users[0]['phone'], $secondUser->phone);
        $this->assertEquals($users[1]['phone'], $firstUser->phone);
    }

    /** @test */
    public function it_can_filter_by_phone_number(): void
    {
        $user = User::factory()->create(['phone' => $this->phone]);
        $user2 = User::factory()->create(['phone' => '09272713598']);
        $ids = $this->fetchUsersIds(['phone' => $user->phone]);

        $this->assertTrue($ids->contains($user->id));
        $this->assertFalse($ids->contains($user2->id));
    }

    /** @test */
    public function it_can_filter_by_first_name(): void
    {
        $kate = User::factory()->create(['first_name' => 'kate']);
        $maggie = User::factory()->create(['first_name' => 'maggie']);
        $ids = $this->fetchUsersIds(['first_name' => $kate->first_name]);

        $this->assertTrue($ids->contains($kate->id));
        $this->assertFalse($ids->contains($maggie->id));
    }

    /** @test */
    public function it_can_filter_by_last_name(): void
    {
        $kate = User::factory()->create(['last_name' => 'kate']);
        $maggie = User::factory()->create(['last_name' => 'maggie']);
        $ids = $this->fetchUsersIds(['last_name' => $kate->last_name]);

        $this->assertTrue($ids->contains($kate->id));
        $this->assertFalse($ids->contains($maggie->id));
    }

    /** @test */
    public function it_can_filter_by_total_order_amount_is_large_than(): void
    {
        $service = Service::factory()->create();
        $kate = User::factory()->create(['last_name' => 'kate']);
        $maggie = User::factory()->create(['last_name' => 'maggie']);
        Order::factory()->create([
            'service_id' => $service->id,
            'amount' => 100,
            'user_id' => $kate->id
        ]);
        Order::factory()->create([
            'service_id' => $service->id,
            'amount' => 220,
            'user_id' => $kate->id
        ]);

        $ids = $this->fetchUsersIds(['order_amount_larger_than' => 200]);

        $this->assertTrue($ids->contains($kate->id));
        $this->assertFalse($ids->contains($maggie->id));
    }

    /** @test */
    public function it_can_filter_by_last_order_date_within_this_month(): void
    {
        $service = Service::factory()->create();
        $kate = User::factory()->create(['last_name' => 'kate']);
        $maggie = User::factory()->create(['last_name' => 'maggie']);
        Order::factory()->create([
            'service_id' => $service->id,
            'amount' => 100,
            'user_id' => $kate->id
        ]);
        Order::factory()->create([
            'service_id' => $service->id,
            'amount' => 220,
            'user_id' => $maggie->id,
            'created_at' => now()->subDays(30)
        ]);

        $ids = $this->fetchUsersIds(['last_order_this_month' => true]);

        $this->assertTrue($ids->contains($kate->id));
        $this->assertFalse($ids->contains($maggie->id));

        $ids = $this->fetchUsersIds(['last_order_this_month' => null]);

        $this->assertTrue($ids->contains($kate->id));
        $this->assertTrue($ids->contains($maggie->id));
    }

    /** @test */
    public function only_login_user_can_access()
    {
        $this->getJson($this->endpoint)->assertUnauthorized();
    }

    protected function fetch($query = [], $as = null)
    {
        $query = http_build_query($query);
        return $this->signInAsAdmin($as)->getJson($this->endpoint . '?' . $query);
    }

    public function fetchUsersIds($query = [], $as = null)
    {
        return $this->fetch($query, $as)->collect('data')->pluck('id');
    }
}
