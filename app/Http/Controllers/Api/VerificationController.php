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
        Log::info('Verification endpoint hit', ['id' => $id, 'hash' => $hash]);

        $user = User::findOrFail($id);

        // Validar hash
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            Log::warning('Invalid verification hash', ['id' => $id]);
            return response()->json(['message' => 'Enlace de verificación inválido.'], 403);
        }

        // Si ya estaba verificado, igual redirigimos al frontend
        if ($user->hasVerifiedEmail()) {
            return redirect($request->query('redirect', env('FRONTEND_URL') . '/login'));
        }

        // Si no estaba verificado, lo marcamos como verificado
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            Log::info('Email verified successfully', ['id' => $id]);
            return redirect($request->query('redirect', env('FRONTEND_URL') . '/login'));
        }

        Log::error('Failed to verify email', ['id' => $id]);
        return response()->json(['message' => 'Error al verificar el correo.'], 500);
    }

    public function show(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'message'  => $user->hasVerifiedEmail()
            ? 'El correo ya está verificado.'
            : 'Por favor verifica tu correo electrónico.',
            'verified' => $user->hasVerifiedEmail(),
        ]);
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'El correo ya está verificado.'], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Enlace de verificación reenviado.'], 200);
    }
}
