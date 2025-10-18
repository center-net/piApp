<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PiAuthController extends Controller
{
    public function authenticate(Request $request)
    {
        $data = $request->validate([
            'accessToken' => 'required|string',
            'username' => 'nullable|string',
            'publicKey' => 'nullable|string',
        ]);

        // مثال: طلب التحقق من Pi (راجع توثيق Pi الفعلي)
        $resp = Http::withHeaders([
            'Authorization' => 'Bearer '.env('PI_API_KEY'),
            'Accept' => 'application/json'
        ])->post(env('PI_VERIFY_ENDPOINT'), [
            'accessToken' => $data['accessToken']
        ]);

        if(!$resp->successful()){
            return response()->json(['error'=>'invalid_token_or_pi_api_error','details'=>$resp->body()], 401);
        }

        $piUser = $resp->json(); // حسب صيغة Pi: عدّل الوصول للحقول المرغوبة
        // على سبيل المثال: $piUser['username']، $piUser['publicKey']

        // استخدم القيم المرجعة أو القيم المرسلة
        $username = $data['username'] ?? ($piUser['username'] ?? null);
        $publicKey = $data['publicKey'] ?? ($piUser['publicKey'] ?? null);

        // إيجاد أو إنشاء المستخدم
        $user = User::firstOrCreate(
            ['public_key' => $publicKey],
            ['pi_username' => $username, 'email' => $piUser['email'] ?? null, 'meta' => $piUser]
        );

        // إصدار token (Sanctum personal access token)
        $token = $user->createToken('api-token-'.Str::random(8))->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    // اختبار endpoint لعرض بيانات المستخدم
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
