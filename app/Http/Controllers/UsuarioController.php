<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');

        $usuarios = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('usuarios.index', compact('usuarios', 'search'));
    }

    public function create(): View
    {
        return view('usuarios.create', ['roles' => $this->availableRoles()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys($this->availableRoles()))],
        ]);
        $senhaProvisoria = Str::random(12);

        $usuario = new User([
            ...$attributes,
            'password' => $senhaProvisoria,
        ]);
        $usuario->forceFill(['precisa_trocar_senha' => true])->save();

        Log::info("Nova conta criada para {$usuario->email}. Senha provisória: {$senhaProvisoria}");

        return redirect()->route('users')
            ->with('success', 'Usuário cadastrado. A senha provisória foi registrada no log do sistema.');
    }

    public function edit(User $usuario): View
    {
        $this->authorizeTarget($usuario);

        return view('usuarios.edit', [
            'usuario' => $usuario,
            'roles' => $this->availableRoles(),
        ]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $this->authorizeTarget($usuario);

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'role' => ['required', Rule::in(array_keys($this->availableRoles()))],
        ]);

        if ($this->isLastAdminBeingDemoted($usuario, $attributes['role'])) {
            return back()->withErrors([
                'role' => 'O último administrador não pode perder o nível de administrador.',
            ]);
        }

        $usuario->update($attributes);

        return redirect()->route('users')->with('success', 'Usuário atualizado com sucesso.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        $this->authorizeTarget($usuario);

        if ($request->user()->is($usuario)) {
            return redirect()->route('users')->withErrors([
                'usuario' => 'Não é possível excluir a própria conta por esta tela.',
            ]);
        }

        if ($usuario->role === 'admin' && User::query()->where('role', 'admin')->count() === 1) {
            return back()->withErrors([
                'usuario' => 'O último administrador não pode ser excluído.',
            ]);
        }

        $usuario->delete();

        return redirect()->route('users')->with('success', 'Usuário excluído com sucesso.');
    }

    private function isLastAdminBeingDemoted(User $usuario, string $role): bool
    {
        return $usuario->role === 'admin'
            && $role !== 'admin'
            && User::query()->where('role', 'admin')->count() === 1;
    }

    /**
     * @return array<string, string>
     */
    private function availableRoles(): array
    {
        $roles = User::ROLES;

        if (auth()->user()->role === 'gerente') {
            unset($roles['admin']);
        }

        return $roles;
    }

    private function authorizeTarget(User $usuario): void
    {
        abort_if(
            auth()->user()->role === 'gerente' && $usuario->role === 'admin',
            403,
            'Gerentes não podem alterar contas de administradores.',
        );
    }
}
