<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirects_authenticated_user_to_dashboard(): void
    {
        $user = User::factory()->create([
            'name' => 'Recipe Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Recipe Admin')
            ->assertSee('admin@example.com');
    }

    public function test_invalid_login_returns_to_login_with_a_red_warning(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->from('/')
            ->followingRedirects()
            ->post('/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);

        $response
            ->assertOk()
            ->assertSee('As credenciais informadas não correspondem aos nossos registros.');

        $this->assertGuest();
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_registration_creates_an_account_and_authenticates_the_new_user(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Recipe Admin',
            'email' => 'new-admin@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Recipe Admin',
            'email' => 'new-admin@example.com',
        ]);
    }
}
