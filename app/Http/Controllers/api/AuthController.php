<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register new user
     */
    public function register(Request $request)
    {
        // Validación básica
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:15|unique:users,phone',
            'email' => 'nullable|email|unique:users,email', // Email opcional
            'password' => 'required|string|min:6',
            'user_type' => 'required|in:passenger,driver,both',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Crear usuario
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email, // Puede ser null
                'password' => Hash::make($request->password),
                'user_type' => $request->user_type,
                'status' => 'active',
            ]);

            // Asignar rol
            if ($user->user_type === 'passenger') {
                $user->assignRole('passenger');
            } elseif ($user->user_type === 'driver') {
                $user->assignRole('driver');
            } else {
                $user->assignRole(['passenger', 'driver']);
            }

            // Crear perfil
            $user->profile()->create();

            // Generar código de referido
            $user->generateReferralCode();

            // Generar token
            $token = $user->createToken('ChasquiApp')->accessToken;

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'user_type' => $user->user_type,
                        'referral_code' => $user->referral_code,
                    ],
                    'token' => $token
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user (con phone o email)
     */
    public function login(Request $request)
    {
        // Validación
        $validator = Validator::make($request->all(), [
            'login' => 'required|string', // Puede ser phone o email
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $loginField = $request->login;
            $user = null;

            // Determinar si es email o teléfono
            if (filter_var($loginField, FILTER_VALIDATE_EMAIL)) {
                // Es email
                $user = User::where('email', $loginField)->first();
            } else {
                // Es teléfono
                $user = User::where('phone', $loginField)->first();
            }

            // Verificar credenciales
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            // Verificar que esté activo
            if ($user->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cuenta suspendida o inactiva'
                ], 403);
            }

            // Actualizar última actividad
            $user->update(['last_activity' => now()]);

            // Generar token
            $token = $user->createToken('ChasquiApp')->accessToken;

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'user_type' => $user->user_type,
                        'rating_average' => $user->rating_average,
                        'total_trips' => $user->total_trips,
                        'referral_code' => $user->referral_code,
                    ],
                    'token' => $token
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en login',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();

            $userData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'status' => $user->status,
                'rating_average' => $user->rating_average,
                'total_trips' => $user->total_trips,
                'referral_code' => $user->referral_code,
                'created_at' => $user->created_at,
            ];

            // Si es conductor, incluir info de conductor
            if ($user->driver) {
                $userData['driver'] = [
                    'license_number' => $user->driver->license_number,
                    'driver_status' => $user->driver->driver_status,
                    'documents_verified' => $user->driver->documents_verified,
                    'total_earnings' => $user->driver->total_earnings,
                    'completed_trips' => $user->driver->completed_trips,
                ];
            }

            // Suscripción activa
            if ($user->activeSubscription) {
                $userData['subscription'] = [
                    'plan_name' => $user->activeSubscription->subscriptionPlan->name,
                    'expires_at' => $user->activeSubscription->expires_at,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $userData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update basic profile
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|nullable|email|unique:users,email,' . $request->user()->id,
            'phone' => 'sometimes|required|string|max:15|unique:users,phone,' . $request->user()->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();

            $updates = [];
            if ($request->has('first_name')) $updates['first_name'] = $request->first_name;
            if ($request->has('last_name')) $updates['last_name'] = $request->last_name;
            if ($request->has('email')) $updates['email'] = $request->email;
            if ($request->has('phone')) $updates['phone'] = $request->phone;

            if (!empty($updates)) {
                if (isset($updates['first_name']) || isset($updates['last_name'])) {
                    $updates['name'] = ($updates['first_name'] ?? $user->first_name) . ' ' .
                                     ($updates['last_name'] ?? $user->last_name);
                }
                $user->update($updates);
            }

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register as driver (basic)
     */
    public function registerDriver(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'license_number' => 'required|string|unique:drivers,license_number',
            'license_expiry_date' => 'required|date|after:today',
            'vehicle_type' => 'required|in:motorcycle,car,collective,mototaxi',
            'license_plate' => 'required|string|unique:vehicles,license_plate',
            'brand' => 'required|string',
            'model' => 'required|string',
            'color' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();

            // Verificar si puede ser conductor
            if (!in_array($user->user_type, ['driver', 'both'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de usuario no permite ser conductor'
                ], 400);
            }

            // Verificar si ya es conductor
            if ($user->driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya está registrado como conductor'
                ], 400);
            }

            // Crear registro de conductor
            $driver = $user->driver()->create([
                'license_number' => $request->license_number,
                'license_expiry_date' => $request->license_expiry_date,
                'driver_status' => 'offline',
                'documents_verified' => false,
            ]);

            // Crear vehículo
            $vehicle = $driver->vehicles()->create([
                'vehicle_type' => $request->vehicle_type,
                'brand' => $request->brand,
                'model' => $request->model,
                'license_plate' => $request->license_plate,
                'color' => $request->color,
                'passenger_capacity' => $request->vehicle_type === 'motorcycle' ? 1 : 4,
                'year' => date('Y'),
                'vehicle_status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registrado como conductor. Esperando verificación.',
                'data' => [
                    'driver_id' => $driver->id,
                    'license_number' => $driver->license_number,
                    'vehicle' => $vehicle->brand . ' ' . $vehicle->model,
                    'license_plate' => $vehicle->license_plate,
                    'status' => 'pending_verification'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar conductor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->token()->revoke();

            return response()->json([
                'success' => true,
                'message' => 'Logout exitoso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en logout'
            ], 500);
        }
    }
}
