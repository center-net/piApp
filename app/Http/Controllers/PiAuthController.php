<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class PiAuthController extends Controller
{
    // دالة لبدء عملية تسجيل الدخول/التفويض مع Pi
    public function redirectToPi()
    {
        $clientId = config('services.pi.client_id');
        $redirectUri = config('services.pi.redirect_uri');

        // إنشاء حالة لتتبع الطلب (state parameter)
        $state = bin2hex(random_bytes(16));
        session(['pi_auth_state' => $state]);

        // بناء URL لتوجيه المستخدم إلى صفحة تفويض Pi
        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'identify payments',
            'state' => $state,
        ]);

        return redirect('https://oauth.minepi.com/auth?' . $query);
    }

    // نقطة استقبال للرد من Pi بعد التفويض
    public function handlePiCallback(Request $req)
    {
        // التحقق من state parameter لمكافحة CSRF
        $state = session('pi_auth_state');
        if (!$state || $state !== $req->input('state')) {
            return response()->json(['error' => 'Invalid state parameter'], 400);
        }

        // الحصول على رمز الوصول (access token)
        $code = $req->input('code');
        $clientId = config('services.pi.client_id');
        $clientSecret = config('services.pi.client_secret');
        $redirectUri = config('services.pi.redirect_uri');

        $response = Http::asForm()->post('https://oauth.minepi.com/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to exchange code for token'], 400);
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'];

        // الحصول على معلومات المستخدم باستخدام التوكن
        $userResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://api.minepi.com/v1/user');

        if (!$userResponse->successful()) {
            return response()->json(['error' => 'Failed to get user information'], 400);
        }

        $userData = $userResponse->json();

        // إنشاء أو تحديث المستخدم في قاعدة البيانات المحلية
        $user = User::updateOrCreate(
            ['pi_id' => $userData['id']],
            [
                'name' => $userData['username'],
                'email' => $userData['email'] ?? null,
                'pi_access_token' => $accessToken,
                'pi_refresh_token' => $tokenData['refresh_token'] ?? null,
            ]
        );

        // تسجيل الدخول للمستخدم
        auth()->login($user);

        return redirect('/dashboard'); // أو أي صفة أخرى تريدها بعد تسجيل الدخول
    }

    // دالة لإنشاء دفعة جديدة
    public function createPayment(Request $req)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $to = $req->input('to');
        $amount = $req->input('amount');
        $memo = $req->input('memo', 'Payment from my app');

        // التحقق من وجود توكن للمستخدم
        if (!$user->pi_access_token) {
            return response()->json(['error' => 'Pi access token not found'], 401);
        }

        // استدعاء API Pi لإنشاء الدفعة
        $res = Http::withHeaders([
            'Authorization' => 'Bearer ' . $user->pi_access_token,
            'Content-Type' => 'application/json',
        ])->post('https://api.minepi.com/v1/payments', [
            'to' => $to,
            'amount' => $amount,
            'memo' => $memo,
            'user_id' => $user->pi_id, // إضافة ID المستخدم كـ reference
        ]);

        if (!$res->successful()) {
            return response()->json(['error' => 'Payment creation failed', 'details' => $res->json()], 400);
        }

        return $res->json();
    }
}