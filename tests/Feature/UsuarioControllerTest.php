<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UsuarioControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_user_with_a_temporary_password_logged_for_first_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $loggedMessage = '';
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message) use (&$loggedMessage): bool {
                $loggedMessage = $message;

                return str_contains($message, 'novo@recipetool.com')
                    && preg_match('/Senha provisória: \S+$/', $message) === 1;
            });

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Novo Funcionário',
            'email' => 'novo@recipetool.com',
            'role' => 'funcionario',
        ]);

        $user = User::query()->where('email', 'novo@recipetool.com')->sole();
        preg_match('/Senha provisória: (\S+)$/', $loggedMessage, $matches);

        $response->assertRedirect(route('users'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'funcionario',
            'precisa_trocar_senha' => true,
        ]);
        $this->assertSame(12, strlen($matches[1]));
        $this->assertTrue(Hash::check($matches[1], $user->password));
    }

    public function test_user_index_searches_name_or_email_and_keeps_search_when_paginating(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (range(1, 11) as $number) {
            User::factory()->create([
                'name' => 'Pessoa Encontrada',
                'email' => $number === 1
                    ? 'pessoa1@recipetool.test'
                    : fake()->unique()->safeEmail(),
                'role' => 'funcionario',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('users', ['search' => 'Pessoa Encontrada']))
            ->assertOk()
            ->assertSee('Pessoa Encontrada')
            ->assertSee('Pagination Navigation')
            ->assertViewHas('usuarios', function ($usuarios): bool {
                parse_str(parse_url($usuarios->nextPageUrl(), PHP_URL_QUERY), $query);

                return $usuarios->total() === 11
                    && ($query['search'] ?? null) === 'Pessoa Encontrada'
                    && ($query['page'] ?? null) === '2';
            });

        $this->actingAs($admin)
            ->get(route('users', ['search' => 'pessoa1@recipetool.test']))
            ->assertOk()
            ->assertSee('Pessoa Encontrada');
    }

    public function test_admin_can_update_and_delete_another_user_but_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'funcionario']);

        $this->actingAs($admin)
            ->put(route('users.update', $user), [
                'name' => 'Funcionário Atualizado',
                'email' => $user->email,
                'role' => 'gerente',
            ])
            ->assertRedirect(route('users'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Funcionário Atualizado',
            'role' => 'gerente',
        ]);

        $this->delete(route('users.destroy', $user))
            ->assertRedirect(route('users'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        $this->delete(route('users.destroy', $admin))
            ->assertRedirect(route('users'));
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_manager_can_manage_users_but_employee_is_forbidden_and_does_not_see_the_link(): void
    {
        $manager = User::factory()->create(['role' => 'gerente']);
        $employee = User::factory()->create(['role' => 'funcionario']);

        $this->actingAs($manager)
            ->get(route('users'))
            ->assertOk()
            ->assertSee('Gerenciar usuários');

        $this->get(route('users.create'))
            ->assertOk()
            ->assertDontSee('value="admin"', false);

        $this->post(route('users.store'), [
            'name' => 'Administrador Indevido',
            'email' => 'novo-admin@recipetool.test',
            'role' => 'admin',
        ])->assertSessionHasErrors('role');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->get(route('users.edit', $admin))->assertForbidden();
        $this->delete(route('users.destroy', $admin))->assertForbidden();

        $this->actingAs($employee)
            ->get(route('users'))
            ->assertForbidden();

        $this->get(route('stock.index'))->assertOk();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('users'));
    }

    public function test_admin_cannot_remove_the_last_administrator_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'funcionario',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
        ]);

        $this->delete(route('users.destroy', $admin))
            ->assertSessionHasErrors('usuario');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_login_sends_a_first_access_user_directly_to_password_change(): void
    {
        $user = User::factory()->create([
            'password' => 'TemporaryPassword123!',
            'precisa_trocar_senha' => true,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'TemporaryPassword123!',
        ])->assertRedirect(route('password.change'));
    }

    public function test_first_access_is_limited_to_password_change_or_logout_until_password_is_updated(): void
    {
        $user = User::factory()->create([
            'role' => 'funcionario',
            'precisa_trocar_senha' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.change'));

        $this->get(route('ingredients'))
            ->assertRedirect(route('password.change'));

        $this->get(route('users'))
            ->assertRedirect(route('password.change'));

        $this->get(route('password.change'))
            ->assertOk()
            ->assertSee('Este é seu primeiro acesso');

        $this->put(route('password.change.update'), [
            'password' => 'MinhaSenhaSegura123!',
            'password_confirmation' => 'MinhaSenhaSegura123!',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse($user->precisa_trocar_senha);
        $this->assertTrue(Hash::check('MinhaSenhaSegura123!', $user->password));

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_first_access_password_confirmation_must_match_without_clearing_the_password_change_flag(): void
    {
        $user = User::factory()->create([
            'password' => 'TemporaryPassword123!',
            'precisa_trocar_senha' => true,
        ]);

        $this->actingAs($user)
            ->put(route('password.change.update'), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'DifferentPassword123!',
            ])
            ->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue($user->precisa_trocar_senha);
        $this->assertTrue(Hash::check('TemporaryPassword123!', $user->password));
    }
}
