<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('welcome');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'As credenciais informadas não correspondem aos nossos registros.'])
                ->onlyInput('email', 'remember');
        }

        $request->session()->regenerate();

        if ($request->user()->precisa_trocar_senha) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function createRegistration(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = User::query()->create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Sua conta foi criada com sucesso.');
    }

    public function createPasswordRequest(): View
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Informe seu endereço de e-mail.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.max' => 'O endereço de e-mail não pode ter mais de 255 caracteres.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if (in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            return back()->with(
                'status',
                'Se houver uma conta associada a esse e-mail, enviaremos um link para redefinir sua senha.',
            );
        }

        return back()->withErrors([
            'email' => 'Não foi possível solicitar a redefinição da senha. Tente novamente mais tarde.',
        ])->onlyInput('email');
    }

    public function createPasswordReset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'token.required' => 'O link de redefinição está incompleto. Solicite um novo link.',
            'email.required' => 'Informe seu endereço de e-mail.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.max' => 'O endereço de e-mail não pode ter mais de 255 caracteres.',
            'password.required' => 'Informe uma nova senha.',
            'password.confirmed' => 'A confirmação da senha não corresponde.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.letters' => 'A senha deve conter pelo menos uma letra.',
            'password.numbers' => 'A senha deve conter pelo menos um número.',
        ]);

        $status = Password::reset($attributes, function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors([
                'email' => 'O link de redefinição é inválido ou expirou. Solicite um novo link.',
            ]);
        }

        return redirect()->route('login')
            ->with('status', 'Sua senha foi redefinida com sucesso. Você já pode entrar com a nova senha.');
    }

    public function createForcedPasswordChange(): View
    {
        return view('auth.trocar-senha');
    }

    public function updateForcedPassword(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)->letters()->numbers(),
            ],
        ]);

        $request->user()->forceFill([
            'password' => $attributes['password'],
            'precisa_trocar_senha' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Senha atualizada com sucesso.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
