<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Handle rider login
     * 
     * POST /api/auth/login
     * Body: { "email": "rider@demo.com", "password": "password123" }
     */
    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        // Validate input
        if (empty($email) || empty($password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email and password are required'
            ], 400);
        }

        // Demo credentials for testing
        // In production, validate against database
        if ($email === 'rider@demo.com' && $password === 'password123') {
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => 'demo-token-' . bin2hex(random_bytes(16)),
                'rider' => [
                    'id' => 1,
                    'name' => 'John Rider',
                    'email' => 'rider@demo.com',
                    'phone' => '+63 912 345 6789',
                    'status' => 'online',
                    'vehicle_type' => 'Motorcycle',
                    'plate_number' => 'ABC 1234',
                    'license_number' => 'D01-23-456789',
                    'rating' => 4.8,
                    'total_deliveries' => 156
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password'
        ], 401);
    }
}
