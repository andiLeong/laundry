<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use App\Models\VerificationToken;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\CanCreateCompany;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase, CanCreateCompany;

    private array $fooInc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fooInc = [
            'id' => 1,
            'name' => 'foo inc',
            'address' => 'manila'
        ];
    }

    /** @test */
    public function an_user_should_contains_necessary_attributes(): void
    {
        $user = User::factory([
            'phone' => '09000000000',
            'first_name' => 'nancy',
            'middle_name' => 'julie',
            'last_name' => 'macy',
        ])->create();

        $this->assertEquals('09000000000', $user->phone);
        $this->assertEquals('nancy', $user->first_name);
        $this->assertEquals('julie', $user->middle_name);
        $this->assertEquals('macy', $user->last_name);
        $this->assertEquals('customer', $user->type);
    }

    /** @test */
    public function it_may_not_has_middle_name(): void
    {
        $user = User::factory(['middle_name' => null])->create();
        $this->assertNull($user->middle_name);
    }

    /** @test */
    public function it_can_has_orders(): void
    {
        $user = User::factory()->create();
        $orders = Order::factory(2)->create(['user_id' => $user->id]);

        $this->assertTrue(in_array($orders[0]->id, $user->orders->pluck('id')->all()));
        $this->assertTrue(in_array($orders[1]->id, $user->orders->pluck('id')->all()));
    }

    /** @test */
    public function it_can_fetch_the_latest_verification_token()
    {
        $user = User::factory()->create();
        $firstToken = VerificationToken::factory()->create(['user_id' => $user]);
        $secondToken = VerificationToken::factory()->create(['user_id' => $user]);

        $this->assertEquals($secondToken->token, $user->verification->token);
        $this->assertEquals($secondToken->user_id, $user->id);
    }

    /** @test */
    public function it_belongs_to_a_company()
    {
        $customer = $this->customer();
        $customer2 = $this->customer();
        $this->setupCompanyAndUser([$customer->id], $this->fooInc);

        $this->assertEquals($customer->company(), $this->fooInc);
        $this->assertNull($customer2->company());
    }

    /** @test */
    public function it_can_fetch_company_attribute()
    {
        $customer = $this->customer();
        $customer2 = $this->customer();
        $this->setupCompanyAndUser([$customer->id], $this->fooInc);

        $user = User::find($customer->id)->setAppends(['company']);
        $user2 = User::find($customer2->id)->setAppends(['company']);
        $attribute = $user->toArray();

        $this->assertEquals($attribute['company'], $this->fooInc);
        $this->assertNull($user2->toArray()['company']);
    }

    /** @test */
    public function it_can_fetch_company_attribute_on_user_collection()
    {
        $customer = $this->customer();
        $customer2 = $this->customer();
        $this->setupCompanyAndUser([$customer->id], $this->fooInc);

        $users = User::whereIn('id', [$customer2->id, $customer->id])->get()->each->setAppends(['company']);
        $paginateUsers = User::whereIn('id', [$customer2->id, $customer->id])
            ->paginate()
            ->each(fn($user) => $user->setAppends(['company']));

        $this->assertEquals($users[0]['company'], $this->fooInc);
        $this->assertNull($users[1]['company']);

        $this->assertEquals($paginateUsers[0]['company'], $this->fooInc);
        $this->assertNull($paginateUsers[1]['company']);
    }

    /** @test */
    public function it_can_determined_if_a_user_belongs_to_a_company()
    {
        $customer = $this->customer();
        $customer2 = $this->customer();
        $this->setupCompanyAndUser([$customer->id], $this->fooInc);

        $this->assertTrue($customer->isComportedAccount());
        $this->assertFalse($customer2->isComportedAccount());
    }

    /** @test */
    public function it_has_many_shift()
    {
        $branch = Branch::factory()->create();
        $staff = $this->staff(['branch_id' => $branch->id]);
        $shift = Shift::factory()->create([
            'staff_id' => $staff->id,
            'branch_id' => $staff->branch_id
        ]);

        $this->assertEquals($staff->shift[0]->id, $shift->id);
    }
}
