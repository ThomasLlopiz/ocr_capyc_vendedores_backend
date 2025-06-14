<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only('resend');
        $this->middleware('signed')->only('verify');
    }

    public function show(Request $request)
    {
        return $request->user() ? redirect('/home') : view('auth.verify');
    }

    public function verify(Request $request)
    {
        if ($request->route('id') == $request->user()->id) {
            if (! $request->user()->hasVerifiedEmail()) {
                $request->user()->markEmailAsVerified();
            }
            return response()->json(['message' => 'Email verificado exitosamente'], 200);
        }

        throw new AuthorizationException();
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
