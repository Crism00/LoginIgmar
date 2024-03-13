<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendVerificationEmail;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */


    public function store(Request $request): RedirectResponse
    {
        try{
            //Se genera un id de rol para el usuario preseteado en 1
            $role_id = 1;
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users', 'lowercase'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);
            if($validator->fails()){
                return redirect()->back()->withErrors($validator->errors());
            }
            //Se busca que exista un usuario con rol de administrador si no existe la variable de role_id se cambia a 2 (Usuario)
            $registeredUsers = User::all()->where('role_id', 1)->count();
            if($registeredUsers > 0){
                $role_id = 2;
            }
            $randomNumber = rand(9999, 1000);
            //Se genera el usuario
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'role_id' => $role_id,
                'verification_code' => Hash::make($randomNumber),
                'two_factor' => 0,
                'password' => Hash::make($request->password),
            ]);
            //Se envia
            if($user->role_id == 1){
                Log::channel('slack')->info('New admin registered', ['user' => $user->name, 'email' => $user->email, 'role' => 'Admin']);
            }
            SendVerificationEmail::dispatch($user, URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->email)]
            ));
            $request->session()->put('email', $user->email);

            return redirect(RouteServiceProvider::HOME);
        }
        catch(\Exception $e){
            Log::channel('slack')->error('Intento fallido de registro con estas credenciales', ['user' => $request->name, 'email' => $request->email, 'error' => $e->getMessage()]);
            return redirect()->back()->withErrors($e->getMessage());
        }
    }
}
