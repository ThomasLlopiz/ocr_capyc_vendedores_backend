<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        Log::info('Verification endpoint hit', ['id' => $id, 'hash' => $hash, 'redirect' => $request->query('redirect')]);

        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            Log::warning('Invalid verification hash', ['id' => $id]);
            return response()->json(['message' => 'Enlace de verificación inválido.'], 403)->header('Access-Control-Allow-Origin', 'http://127.0.0.1:8500');
        }

        $redirectUrl = $request->query('redirect', env('FRONTEND_URL', 'http://127.0.0.1:8500') . '/login');

        if ($user->hasVerifiedEmail()) {
            Log::info('Email already verified', ['id' => $id]);
            return redirect($redirectUrl . '?verified=false&reason=already_verified')->header('Access-Control-Allow-Origin', 'http://127.0.0.1:8500');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            Log::info('Email verified successfully', ['id' => $id]);
            return redirect($redirectUrl . '?verified=true')->header('Access-Control-Allow-Origin', 'http://127.0.0.1:8500');
        }

        Log::error('Failed to verify email', ['id' => $id]);
        return response()->json(['message' => 'Error al verificar el correo.'], 500)->header('Access-Control-Allow-Origin', 'http://127.0.0.1:8500');
    }

    public function show(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'message'  => $user->hasVerifiedEmail() ? 'El correo ya está verificado.' : 'Por favor verifica tu correo electrónico.',
            'verified' => $user->hasVerifiedEmail(),
        ])->header('Access-Control-Allow-Origin', 'http://127.0.0.1:8500');
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'El correo ya está verificado.'], 200)->header('Access-Control-Allow-Origin', 'http://127.0.0.1:8500');
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Enlace de verificación reenviado.'], 200)->header('Access-Control-Allow-Origin', 'http://127.0.0.1:8500');
    }
}
