<?php

namespace Tests\Feature;

use App\Models\VerificationToken;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\UserCanBeVerified;

class VerificationTest extends TestCase
{
    use LazilyRefreshDatabase;
    use UserCanBeVerified;

    protected $endpoint = 'api/verification';
    private $phone;
    private int $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->phone = '09050887900';
        $this->token = 95862;
        $this->setUnverifiedUser();
    }

    /** @test */
    public function it_can_verify_user_token(): void
    {
        $this->bindToken($this->token);

        $this->assertFalse($this->user->isVerified());
        $this->verify($this->token)->assertStatus(200);

        $this->assertTrue($this->user->fresh()->isVerified());
    }

    /** @test */
    public function fake_token_can_be_verified(): void
    {
        $realToken = 12345;
        $this->bindToken($realToken);

        $response = $this->verify($this->token);

        $this->assertFalse($this->user->fresh()->isVerified());
        $this->assertValidateMessage('Token is invalid', $response, 'token');
    }

    /** @test */
    public function expired_token_can_not_be_verified(): void
    {
        $expiredToken = $this->token;
        $this->bindToken($expiredToken, [
            'expired_at' => now()->subDays()
        ]);

        $response = $this->verify($expiredToken);

        $this->assertFalse($this->user->fresh()->isVerified());
        $this->assertValidateMessage('Token is invalid', $response, 'token');
    }

    /** @test */
    public function old_token_can_not_be_verified(): void
    {
        $secondToken = 99887;
        $this->bindToken($this->token);
        $this->bindToken($secondToken);

        $response = $this->verify($this->token);
        $this->assertValidateMessage('Token is invalid', $response, 'token');
    }

    /** @test */
    public function none_verification_token_can_not_be_verified(): void
    {
        $response = $this->verify($this->token);
        $this->assertValidateMessage('Token is invalid', $response, 'token');
    }

    /** @test */
    public function verified_user_gets_404(): void
    {
        $this->setVerifiedUser('09111111111', now()->subDays());
        $this->verify($this->token, $this->user->phone)->assertNotFound();
    }

    /** @test */
    public function phone_must_be_valid(): void
    {
        $response = $this->postJson($this->endpoint, ['0956484444']);
        $this->assertValidateMessage('Phone is invalid', $response, 'phone');
    }

    /** @test */
    public function authenticated_user_cant_verify_other_phone()
    {
        $this->signIn()->postJson($this->endpoint, ['0956484444'])->assertForbidden();
    }

    /**
     * @param int $token
     * @param null $phone
     * @return TestResponse
     */
    protected function verify(int $token, $phone = null): \Illuminate\Testing\TestResponse
    {
        return $this->postJson($this->endpoint, [
            'phone' => $phone ?? $this->phone,
            'token' => $token
        ]);
    }

    /**
     * @param int $token
     * @param array $overwrite
     * @return Collection|Model
     */
    protected function bindToken(int $token, $overwrite = [])
    {
        $default = [
            'user_id' => $this->user->id,
            'token' => $token,
        ];

        return VerificationToken::factory()->create(array_merge($default, $overwrite));
    }
}
