<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Jobs\SendVerificationCode;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\IpUtils;

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
        try{
            $recaptcha_response = $request->input('g-recaptcha-response');
            if (is_null($recaptcha_response)) {
                return redirect()->back()->with('status', 'Please Complete the Recaptcha to proceed');
            }
            $url = "https://www.google.com/recaptcha/api/siteverify";

            $body = [
                'secret' => env('RECAPTCHA_SITE_SECRET'),
                'response' => $recaptcha_response,
                'remoteip' => IpUtils::anonymize($request->ip()) //anonymize the ip to be GDPR compliant. Otherwise just pass the default ip address
            ];
            $response = Http::asForm()->post($url, $body);
            $result = json_decode($response);

            if ($response->successful() && $result->success == true) {

                $user = User::where('email', $request->email)->first();
                if(!Hash::check($request->password, $user->password)){
                    return redirect()->back()->withErrors(['email' => 'Bad credentials', 'password' => 'Bad credentials']);
                }
                

                if($user->two_factor == false && $user->role_id == 1){
                    $randomNumber = rand(9999, 1000);
                    $user->code = Hash::make($randomNumber);
                    $user->save();
                    $request->session()->put('user', $user);
                    SendVerificationCode::dispatch($randomNumber, $user);
                    return redirect()->route('codeVerificationView');
                }
                Auth::login($user);
                $request->session()->regenerate();
                if($request->user()->role_id == 1){
                    Log::channel('slack')->critical('Inicio de sesión de administrador: ' . $request->user()->name . ' con correo: ' . $request->user()->email . ' a las ' . date('H:i:s') . ' del día ' . date('d/m/Y') . '.'.' Rol: Administrador');
                }
                else{
                    Log::channel('slack')->info('Inicio de sesión de usuario: ' . $request->user()->name . ' con correo: ' . $request->user()->email . ' a las ' . date('H:i:s') . ' del día ' . date('d/m/Y') . '.');
                }
                return redirect()->intended(RouteServiceProvider::HOME);
            } else {
                return redirect()->back()->with('status', 'Please Complete the Recaptcha Again to proceed');
            }
        }
        catch(\Exception $e){
            Log::channel('slack')->error('Error al iniciar sesión: ' . $e->getMessage() . ' en la línea ' . $e->getLine() . ' del archivo ' . $e->getFile() . ' a las ' . date('H:i:s') . ' del día ' . date('d/m/Y') . '.');
            return redirect()->back()->withErrors(['email' => 'Bad credentials', 'password' => 'Bad credentials']);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        User::where('id', $user->id)->update(['two_factor' => false]);
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function codeVerification(Request $request): RedirectResponse
    {
        try{
            $user = $request->session()->get('user');
            if(Hash::check($request->code, $user->code)){
                $user->two_factor = true;
                $user->save();
                $request->session()->forget('user');
                $request->session()->regenerate();
                Auth::login($user);
                return redirect()->intended(RouteServiceProvider::HOME);
            }
            else{
                return redirect()->back()->withErrors(['code' => 'Bad code']);
            }
        }
        catch(\Exception $e){
            Log::channel('slack')->error('Error al iniciar sesión: ' . $e->getMessage() . ' en la línea ' . $e->getLine() . ' del archivo ' . $e->getFile() . ' a las ' . date('H:i:s') . ' del día ' . date('d/m/Y') . '.');
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function resendCode(Request $request): RedirectResponse
    {
        try{
            $user = $request->session()->get('user');
            $randomNumber = rand(9999, 1000);
            $user->code = $randomNumber;
            $user->save();
            $request->session()->put('user', $user);
            SendVerificationCode::dispatch($randomNumber, $user);
            return redirect()->back()->withErrors('Email resent');
        }
        catch(\Exception $e){
            Log::channel('slack')->error('Error al iniciar sesión: ' . $e->getMessage() . ' en la línea ' . $e->getLine() . ' del archivo ' . $e->getFile() . ' a las ' . date('H:i:s') . ' del día ' . date('d/m/Y') . '.');
            return redirect()->back()->withErrors('Error al reenviar el codigo Codigo 403');
        }
    }
}
