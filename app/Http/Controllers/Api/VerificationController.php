<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class VerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only('resend');
        $this->middleware('signed')->only('verify');
    }

    public function show(Request $request)
    {
        if ($request->user()) {
            return response()->json(['message' => 'Email ya verificado o en proceso'], 200);
        }
        return response()->json(['message' => 'Verificación pendiente. Usa el enlace del correo.'], 403);
    }

    public function verify(Request $request)
    {
        // Obtener el ID desde la ruta
        $userId = $request->route('id');

        // Buscar el usuario por ID
        $user = User::find($userId);

        if (! $user) {
            throw new AuthorizationException('Usuario no encontrado');
        }

        // Verificar si el email ya está verificado
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email ya verificado'], 200);
        }

        // Validar la firma del enlace
        if (! URL::hasValidSignature($request)) {
            throw new AuthorizationException('Enlace de verificación inválido');
        }

        // Marcar el email como verificado
        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email verificado exitosamente'], 200);
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email ya verificado'], 400);
        }

        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'Enlace de verificación enviado'], 200);
    }
}
