<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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

    public function test_login_remembers_user_when_remember_me_is_selected(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
            'remember_token' => null,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
            'remember' => '1',
        ]);

        $recallerName = Auth::guard()->getRecallerName();
        $response
            ->assertRedirect(route('dashboard'))
            ->assertCookie($recallerName);

        expect($user->fresh()->remember_token)->not->toBeNull();

        $recallerValue = $response->getCookie($recallerName)->getValue();
        $this->flushSession();
        Auth::forgetGuards();

        $this->withCookie($recallerName, $recallerValue)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Auth::viaRemember());
    }

    public function test_login_remember_choice_is_preserved_when_credentials_are_invalid(): void
    {
        $response = $this->from('/')
            ->post(route('login.store'), [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
                'remember' => '1',
            ]);

        $response->assertRedirect('/');
        $this->get('/')
            ->assertOk()
            ->assertSee('id="remember"', false)
            ->assertSee('value="1"', false)
            ->assertSee('checked', false);
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

    public function test_password_reset_pages_are_available_to_guests_and_linked_from_login(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'));

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Esqueci minha senha')
            ->assertSee('name="email"', false)
            ->assertSee(route('password.email'));

        $this->get(route('password.reset', [
            'token' => 'token-de-teste',
            'email' => 'pessoa@example.com',
        ]))
            ->assertOk()
            ->assertSee('Crie uma nova senha')
            ->assertSee('name="token" value="token-de-teste"', false)
            ->assertSee('name="email" type="email" value="pessoa@example.com"', false)
            ->assertSee('id="password-reset-requirements"', false)
            ->assertSee('Pelo menos 8 caracteres')
            ->assertSee('Pelo menos uma letra')
            ->assertSee('Pelo menos um número')
            ->assertSee('As senhas devem corresponder')
            ->assertSee(route('password.update'));
    }

    public function test_password_reset_request_stores_a_token_and_sends_the_framework_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'pessoa@example.com']);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas(
                'status',
                'Se houver uma conta associada a esse e-mail, enviaremos um link para redefinir sua senha.',
            );

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        $subject = '';
        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use ($user, &$subject): bool {
                $subject = $notification->toMail($user)->subject;

                return true;
            },
        );
        expect($subject)->toBe('Redefina sua senha');
    }

    public function test_password_reset_request_does_not_reveal_when_an_email_is_not_registered(): void
    {
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'desconhecido@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas(
                'status',
                'Se houver uma conta associada a esse e-mail, enviaremos um link para redefinir sua senha.',
            );

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_password_reset_link_updates_the_password_and_consumes_the_token(): void
    {
        Notification::fake();
        Event::fake([PasswordReset::class]);
        $user = User::factory()->create([
            'email' => 'pessoa@example.com',
            'password' => 'old-password-123',
        ]);
        $token = '';

        $this->post(route('password.email'), ['email' => $user->email]);
        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification, array $channels) use (&$token): bool {
                $token = $notification->token;

                return in_array('mail', $channels, true);
            },
        );

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas(
                'status',
                'Sua senha foi redefinida com sucesso. Você já pode entrar com a nova senha.',
            );

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        Event::assertDispatched(PasswordReset::class);
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sua senha foi redefinida com sucesso. Você já pode entrar com a nova senha.');
    }

    public function test_password_reset_validation_and_invalid_tokens_are_reported_in_portuguese(): void
    {
        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'email-invalido'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors([
                'email' => 'Informe um endereço de e-mail válido.',
            ]);

        $this->from(route('password.reset', [
            'token' => 'token-invalido',
            'email' => 'pessoa@example.com',
        ]))
            ->post(route('password.update'), [
                'token' => 'token-invalido',
                'email' => 'pessoa@example.com',
                'password' => 'senha-123',
                'password_confirmation' => 'senha-diferente',
            ])
            ->assertRedirect(route('password.reset', [
                'token' => 'token-invalido',
                'email' => 'pessoa@example.com',
            ]))
            ->assertSessionHasErrors([
                'password' => 'A confirmação da senha não corresponde.',
            ]);

        $this->from(route('password.reset', [
            'token' => 'token-invalido',
            'email' => 'pessoa@example.com',
        ]))
            ->post(route('password.update'), [
                'token' => 'token-invalido',
                'email' => 'pessoa@example.com',
                'password' => 'senha-123',
                'password_confirmation' => 'senha-123',
            ])
            ->assertRedirect(route('password.reset', [
                'token' => 'token-invalido',
                'email' => 'pessoa@example.com',
            ]))
            ->assertSessionHasErrors([
                'email' => 'O link de redefinição é inválido ou expirou. Solicite um novo link.',
            ]);

        $this->from(route('password.reset', [
            'token' => 'token-invalido',
            'email' => 'pessoa@example.com',
        ]))
            ->post(route('password.update'), [
                'token' => 'token-invalido',
                'email' => 'pessoa@example.com',
                'password' => 'abc12',
                'password_confirmation' => 'abc12',
            ])
            ->assertRedirect(route('password.reset', [
                'token' => 'token-invalido',
                'email' => 'pessoa@example.com',
            ]))
            ->assertSessionHasErrors([
                'password' => 'A senha deve ter pelo menos 8 caracteres.',
            ]);
    }
}
