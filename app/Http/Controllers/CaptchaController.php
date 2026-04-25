<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CaptchaController extends Controller
{
    public function verify(Request $request)
    {
        try {
            $response = $request->input('g-recaptcha-response');
            $ip = $request->ip();
            $failKey = "captcha_fail:$ip";

            Log::info('驗證碼驗證請求', [
                'ip' => $ip,
                'timestamp' => now()
            ]);

            // 調用 Google reCAPTCHA API
            $verify = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => env('RECAPTCHA_SECRET_KEY'),
                'response' => $response,
                'remoteip' => $ip,
            ]);

            if ($verify->json('success')) {
                // 驗證成功，清除 Redis 狀態和失敗計數
                Redis::del("bot_status:$ip");
                Redis::del($failKey);
                
                Log::info('驗證碼驗證成功', [
                    'ip' => $ip,
                    'timestamp' => now()
                ]);
                
                return response()->json(['success' => true]);
            }

            // 驗證失敗，增加失敗計數
            $failCount = Redis::incr($failKey);
            Redis::expire($failKey, 3600); // 1 小時內計數

            Log::warning('驗證碼驗證失敗', [
                'ip' => $ip,
                'fail_count' => $failCount,
                'timestamp' => now()
            ]);

            // 失敗 3 次後設置 blocked 狀態
            if ($failCount >= 3) {
                Redis::set("bot_status:$ip", 'blocked');
                Redis::expire("bot_status:$ip", 3600); // block 1 小時
                
                Log::error('IP 被封鎖（驗證失敗次數過多）', [
                    'ip' => $ip,
                    'fail_count' => $failCount,
                    'block_duration' => '1 小時',
                    'timestamp' => now()
                ]);
                
                return response()->json(
                    ['success' => false, 'message' => '驗證失敗次數過多，IP 已被封鎖 1 小時'],
                    422
                );
            }

            return response()->json(
                ['success' => false, 'message' => "驗證失敗，還有 " . (3 - $failCount) . " 次機會"],
                422
            );

        } catch (\Exception $e) {
            Log::error('驗證碼控制器異常', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $ip ?? '未知',
                'timestamp' => now()
            ]);

            return response()->json(
                ['success' => false, 'message' => '驗證異常，請稍後重試'],
                500
            );
        }
    }
}