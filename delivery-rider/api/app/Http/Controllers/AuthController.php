<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Authentication Controller
 * 
 * Handles rider authentication. In production, this should
 * integrate with your existing authentication system or
 * implement proper JWT authentication.
 */
class AuthController extends Controller
{
    /**
     * Login rider
     * 
     * POST /api/auth/login
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
            ], 422);
        }
        
        // Sample authentication - In production, verify against database
        // For development, accept any email with password 'password123'
        if ($password !== 'password123') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password'
            ], 401);
        }
        
        // Generate sample token (in production, use proper JWT)
        $token = base64_encode(random_bytes(32));
        
        // Return sample rider data
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'rider' => [
                'id' => 1,
                'name' => 'John Doe',
                'email' => $email,
                'phone' => '+63 912 345 6789',
                'vehicle_type' => 'Motorcycle',
                'is_verified' => true,
                'total_deliveries' => 156,
                'rating' => '4.9',
                'total_earnings' => 45680,
                'member_since' => '2024'
            ]
        ]);
    }
    
    /**
     * Logout rider
     * 
     * POST /api/auth/logout
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // In production, invalidate the token
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
