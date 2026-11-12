<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (ValidationException $e) {
            // Tentativa de login falhou (senha errada ou bloqueio por excesso).
            AuditLogger::log('auth.login_failed', 'Tentativa de login falhou', [
                'email' => Str::lower((string) $request->input('email')),
            ]);

            throw $e;
        }

        $request->session()->regenerate();

        AuditLogger::log('auth.login', 'Login bem-sucedido');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = $request->user()?->id;

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        AuditLogger::log('auth.logout', 'Logout', [], $userId);

        return redirect('/');
    }

    /**
     * Encerra todas as sessões do usuário (todos os dispositivos).
     * Também invalida cookies "remember me" antigos.
     */
    public function destroyAll(Request $request): RedirectResponse
    {
        $user = $request->user();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Apaga as sessões persistidas (SESSION_DRIVER=database).
        DB::table('sessions')->where('user_id', $user->id)->delete();

        // Um novo remember_token invalida cookies de "lembrar-me" já emitidos.
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        AuditLogger::log('auth.logout_all', 'Todas as sessões do usuário foram encerradas', [], $user->id);

        return redirect()
            ->route('login')
            ->with('status', 'Todas as sessões foram encerradas. Entre novamente.');
    }
}
