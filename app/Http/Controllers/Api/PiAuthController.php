<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PiAuthController extends Controller
{
    /**
     * التحقق من صحة بيانات المستخدم من Pi
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticate(Request $request)
    {
        try {
            // التحقق من البيانات المدخلة
            $data = $request->validate([
                'accessToken' => 'required|string|min:10',
                'username' => 'nullable|string|max:255',
                'uid' => 'nullable|string|max:255',
            ]);

            Log::info('Pi authentication attempt', [
                'username' => $data['username'] ?? 'unknown',
                'ip' => $request->ip()
            ]);

            // التحقق من صحة التوكن مع Pi API
            $piResponse = $this->verifyWithPiAPI($data['accessToken']);

            if (!$piResponse['valid']) {
                Log::warning('Pi token verification failed', [
                    'username' => $data['username'] ?? 'unknown'
                ]);

                return response()->json([
                    'error' => 'invalid_token',
                    'message' => 'فشل التحقق من التوكن'
                ], 401);
            }

            // استخراج بيانات المستخدم من Pi API response
            $piUser = $piResponse['user'] ?? [];
            $username = $piUser['username'] ?? $data['username'] ?? 'pi_user_' . Str::random(8);
            $uid = $piUser['uid'] ?? $data['uid'] ?? null;

            // استخراج public key من Pi API response
            $publicKey = $piUser['public_key'] ?? $piUser['publicKey'] ?? $piUser['pubKey'] ?? null;

            if (!$uid) {
                throw new \Exception('UID مفقود من Pi');
            }

            // يمكن أن لا يكون public key موجود دائماً، لكن UID ضروري
            Log::debug('Pi user data extracted', [
                'uid' => $uid,
                'username' => $username,
                'has_public_key' => !empty($publicKey)
            ]);

            // البحث عن المستخدم أو إنشاء مستخدم جديد باستخدام Pi uid
            $user = User::firstOrCreate(
                ['pi_id' => $uid], // استخدام Pi uid كـ unique identifier
                [
                    'name' => $username,
                    'pi_username' => $username,
                    'public_key' => $publicKey,
                    'email' => $piUser['email'] ?? null,
                    'pi_access_token' => $data['accessToken'],
                    'meta' => $piUser
                ]
            );

            // تحديث بيانات المستخدم
            $user->update([
                'pi_username' => $username,
                'pi_access_token' => $data['accessToken'],
                'public_key' => $publicKey,
                'meta' => array_merge($user->meta ?? [], $piUser),
                'last_login_at' => now()
            ]);

            // إصدار token جديد
            $tokenName = 'api-token-' . Str::random(8);
            $token = $user->createToken($tokenName)->plainTextToken;

            Log::info('Pi authentication successful', [
                'user_id' => $user->id,
                'username' => $username
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'user' => $user->only(['id', 'name', 'pi_username', 'email']),
                'token' => $token
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error in Pi authentication', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'error' => 'validation_error',
                'message' => 'بيانات غير صحيحة',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Pi authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'authentication_error',
                'message' => 'حدث خطأ أثناء المصادقة'
            ], 500);
        }
    }

    /**
     * التحقق من التوكن مع Pi API
     *
     * @param string $token
     * @return array
     */
    private function verifyWithPiAPI(string $token): array
    {
        try {
            $endpoint = env('PI_VERIFY_ENDPOINT', 'https://api.minepi.com/v1/user');
            $apiKey = env('PI_API_KEY');

            if (!$apiKey) {
                Log::warning('PI_API_KEY is not configured');
                return ['valid' => false];
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json'
            ])->timeout(10)->get($endpoint);

            if ($response->successful()) {
                return [
                    'valid' => true,
                    'user' => $response->json()
                ];
            }

            Log::warning('Pi API verification failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return ['valid' => false];

        } catch (\Exception $e) {
            Log::error('Pi API verification error', [
                'error' => $e->getMessage()
            ]);

            return ['valid' => false];
        }
    }

    /**
     * الحصول على بيانات المستخدم الحالي
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'error' => 'unauthorized',
                    'message' => 'المستخدم غير مصرح'
                ], 401);
            }

            Log::info('User info retrieved', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'user' => $user->only(['id', 'name', 'pi_username', 'email', 'created_at'])
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving user info', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'server_error',
                'message' => 'حدث خطأ في الخادم'
            ], 500);
        }
    }

    /**
     * تسجيل خروج المستخدم
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                // حذف جميع tokens المستخدم
                $user->tokens()->delete();

                Log::info('User logged out', [
                    'user_id' => $user->id
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'logout_failed',
                'message' => 'فشل تسجيل الخروج'
            ], 500);
        }
    }
}
