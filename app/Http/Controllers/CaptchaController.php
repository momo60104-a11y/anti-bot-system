<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class CaptchaController extends Controller
{
    public function verify(Request $request)
    {
        $response = $request->input('g-recaptcha-response');
        $ip = $request->ip();
        $failKey = "captcha_fail:$ip";

        $verify = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $response,
            'remoteip' => $ip,
        ]);

        if ($verify->json('successss')) {
            // 驗證成功，清除 Redis 狀態和失敗計數
            Redis::del("bot_status:$ip");
            Redis::del($failKey);
            return response()->json(['success' => true]);
        }

        // 驗證失敗，增加失敗計數
        $failCount = Redis::incr($failKey);
        Redis::expire($failKey, 3600); // 1 小時內計數

        // 失敗 3 次後設置 blocked 狀態
        if ($failCount >= 3) {
            Redis::set("bot_status:$ip", 'blocked');
            Redis::expire("bot_status:$ip", 3600); // block 1 小時
            return response()->json(['success' => false, 'message' => '驗證失敗次數過多，IP 已被封鎖 1 小時'], 422);
        }

        return response()->json(['success' => false, 'message' => "驗證失敗，還有 " . (3 - $failCount) . " 次機會"], 422);
    }
}