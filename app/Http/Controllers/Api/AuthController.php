<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        Log::info('Users endpoint hit', [
            'user_id'   => $currentUser->id,
            'user_role' => $currentUser->role,
        ]);

        if ($currentUser->role !== 'admin') {
            Log::warning('Unauthorized access attempt to users endpoint', ['user_id' => $currentUser->id]);
            return response()->json(['message' => 'Acceso denegado: Solo administradores pueden listar usuarios.'], 403);
        }

        $users = User::where(function ($query) {
            $query->where('role', '!=', 'admin')
                ->orWhereNull('role')
                ->orWhere('role', '');
        })
            ->select(['id', 'name', 'email', 'role', 'code', 'email_verified_at', 'created_at', 'updated_at'])
            ->get();

        Log::info('Users retrieved', [
            'count' => $users->count(),
            'users' => $users->toArray(),
        ]);

        return response()->json([
            'message' => 'Lista de usuarios obtenida exitosamente',
            'users'   => $users,
        ], 200);
    }

    public function register(Request $request)
    {
        Log::info('Register endpoint hit', ['request' => $request->all()]);

        try {
            $request->validate([
                'name'     => 'required|string|max:255|unique:users,name',
                'email'    => 'required|string|email|unique:users,email',
                'password' => 'required|string|confirmed|min:8',
                'code'     => 'nullable|string|size:6',
            ], [
                'name.required'      => 'El nombre es obligatorio.',
                'name.unique'        => 'El nombre ya está registrado.',
                'email.required'     => 'El correo es obligatorio.',
                'email.email'        => 'El correo debe ser una dirección válida.',
                'email.unique'       => 'El correo ya está registrado.',
                'password.required'  => 'La contraseña es obligatoria.',
                'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed' => 'La confirmación de la contraseña no coincide.',
                'code.size'          => 'El código debe tener exactamente 6 caracteres.',
            ]);

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'code'     => $request->code ?? null,
            ]);

            $user->sendEmailVerificationNotification();

            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'message' => 'Usuario creado exitosamente. Revisa tu email para confirmar.',
                'user'    => $user,
                'token'   => $token,
            ], 201)->header('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        }
    }
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($request->only(['email', 'password']))) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales inválidas'],
            ]);
        }

        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'user'    => $user,
            'token'   => $token,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name'  => 'required|string',
            'code'  => 'nullable|string|size:6',
            'role'  => 'nullable|string|in:user,vendedor,admin,',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Solo administradores pueden actualizar usuarios.'], 403);
        }

        $user->update([
            'name' => $request->name,
            'code' => $request->code,
            'role' => $request->role,
        ]);

        return response()->json(['message' => 'Usuario actualizado exitosamente'], 200);
    }

    public function delete(Request $request)
    {
        $user = $request->user();
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado exitosamente',
        ], 204);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
        ? response()->json(['message' => 'Enlace de recuperación enviado'])
        : response()->json(['message' => 'Error al enviar el enlace'], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => 'required|string|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only(['email', 'password', 'password_confirmation', 'token']),
            function ($user, $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
        ? response()->json(['message' => 'Contraseña restablecida'])
        : response()->json(['message' => 'Error al restablecer la contraseña'], 400);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada exitosamente']);
    }
}
