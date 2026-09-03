<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function createRegister(){
        return view('auth.register');
    }

    public function register(Request $request){
        
        $validate = $request->validate([
            'name'=> ['required', 'string','max:255'],
            'email'=> ['required', 'string', 'email', 'unique:users, email'],
            'pass'=> ['required', 'string', 'confirmed', 'min:8']
        ]);

        User::create([
            'name'=> $validated['name'],
            'emai'=> $validated['email'],
            'password'=> Hash::make([$validate['password']]),
        ]);

        return redirect()
            -> route('login')
            -> with(
                'success',
                'Register success'
            );

    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors([
                    'email' => 'Las credenciales proporcionadas no son válidas.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('/dashboard'));

        }
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }

}