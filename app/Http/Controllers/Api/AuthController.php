<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OTPService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    protected OTPService $otpService;

    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Register new user with complete profile data
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'phone'      => 'required|string|min:10|max:15|unique:users,phone',
            'nickname'   => 'nullable|string|max:255',
            'age'        => 'nullable|integer|min:1|max:150',
            'country_ar' => 'nullable|string|max:255',
            'country_en' => 'nullable|string|max:255',
            'gender'     => 'nullable|in:male,female',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $user = User::create($request->only(['name', 'phone', 'nickname', 'age', 'country_ar', 'country_en', 'gender']));
        $token = auth('api')->login($user);

        return $this->successResponse('Account created successfully', $user, $token, 201);
    }
    /**
     * Login - Send OTP to registered phone number
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:10|max:15',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return $this->errorResponse('Phone number not registered. Please register first.', 404);
        }

        $otp = $this->otpService->generateOTP();
        $this->otpService->storeOTP($user, $otp);
        $sent = $this->otpService->sendOTP($request->phone);

        $isLocal = config('app.env') === 'local';

        $message = $sent
            ? 'OTP sent successfully to ' . $request->phone
            : ($isLocal 
                ? 'Local Mode: Twilio skipped/failed. Use the code shown or 1111.' 
                : 'OTP generated (check database). SMS failed - verify your Twilio account.');

        return response()->json([
            'success'         => true,
            'message'         => $message,
            'otp'             => $otp, 
            'magic_code'      => $isLocal ? '1111' : null,
            'otp_in_database' => true,
        ]);
    }

    /**
     * Verify OTP code and authenticate user
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp'   => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found.', 404);
        }

        $isValid = ($request->otp === '1111' && config('app.env') === 'local')
            || $this->otpService->verifyOTP($request->phone, $request->otp)
            || $this->otpService->isOTPValid($user, $request->otp);

        if (!$isValid) {
            return $this->errorResponse('Invalid or expired OTP code.', 401);
        }

        $token = auth('api')->login($user);

        return $this->successResponse('Login successful', $user, $token);
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user'    => $this->formatUser($request->user()),
        ]);
    }
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name'          => 'nullable|string|max:255',
            'nickname'      => 'nullable|string|max:255',
            'age'           => 'nullable|integer|min:1|max:150',
            'country_ar'    => 'nullable|string|max:255',
            'country_en'    => 'nullable|string|max:255',
            'gender'        => 'nullable|in:male,female',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $updateData = array_filter(
            $request->only(['name', 'nickname', 'age', 'country_ar', 'country_en', 'gender']),
            fn($value) => !is_null($value)
        );

        if ($request->hasFile('profile_image')) {
            // Delete old image if it exists in uploads
            if ($user->profile_image && file_exists(public_path('uploads/' . $user->profile_image))) {
                @unlink(public_path('uploads/' . $user->profile_image));
            }
            
            // Store directly in public/uploads/profile_images
            $path = $request->file('profile_image')->store('profile_images', 'public_uploads');
            $updateData['profile_image'] = $path;
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user'    => $this->formatUser($user),
        ]);
    }

    /**
     * Logout user (invalidate token)
     */
    public function logout(Request $request): JsonResponse
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    private function formatUser(User $user): array
    {
        return [
            'id'            => $user->id,
            'name'          => $user->name,
            'phone'         => $user->phone,
            'nickname'      => $user->nickname,
            'age'           => $user->age,
            'country_ar'    => $user->country_ar,
            'country_en'    => $user->country_en,
            'gender'        => $user->gender,
            'profile_image' => $user->profile_image 
                ? (str_starts_with($user->profile_image, 'http') 
                    ? $user->profile_image 
                    : (file_exists(public_path('uploads/' . $user->profile_image)) 
                        ? url('uploads/' . $user->profile_image) 
                        : url('storage/' . $user->profile_image)))
                : null,
            'hobbies'       => $user->hobbies()->get(['hobbies.id', 'hobbies.name']),
        ];
    }

    /**
     * Return validation error response
     */
    private function validationError($validator): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422);
    }

    /**
     * Return error response
     */
    private function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /**
     * Return success response with user data
     */
    private function successResponse(string $message, User $user, ?string $token = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'user'    => $this->formatUser($user),
        ];

        if ($token) {
            $response['token'] = $token;
        }

        return response()->json($response, $status);
    }

    /**
     * Delete old profile image if exists
     */
    private function deleteOldProfileImage(User $user): void
    {
        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }
    }
}
