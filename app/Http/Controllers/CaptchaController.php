<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CaptchaController extends Controller
{
    const MAX_FAIL_ATTEMPTS = 3; // 最大失敗次數
    const BLOCK_DURATION = 3600; // 1 小時
    const FAIL_COUNT_EXPIRATION = 3600; // 1 小時

    public function verify(Request $request)
    {
        try {
            $response = $request->input('g-recaptcha-response');
            $ip = $request->ip();

            Log::info('驗證碼驗證請求', [
                'ip' => $ip,
                'timestamp' => now()
            ]);

            // 調用 Google reCAPTCHA API
            if ($this->verifyCaptcha($response, $ip)) {
                $this->clearCaptchaStatus($ip);
                return response()->json(['success' => true]);
            }

            // 驗證失敗，增加失敗計數
            $failCount = $this->incrementFailCount($ip);

            Log::warning('驗證碼驗證失敗', [
                'ip' => $ip,
                'fail_count' => $failCount,
                'timestamp' => now()
            ]);

            // 失敗次數過多，封鎖 IP
            if ($failCount >= self::MAX_FAIL_ATTEMPTS) {
                $this->blockIP($ip, $failCount);
                return response()->json(
                    ['success' => false, 'message' => '驗證失敗次數過多，IP 已被封鎖 1 小時'],
                    422
                );
            }

            return response()->json(
                ['success' => false, 'message' => "驗證失敗，還有 " . (self::MAX_FAIL_ATTEMPTS - $failCount) . " 次機會"],
                422
            );

        } catch (\Exception $e) {
            $ip = $request->ip() ?? '未知';
            Log::error('驗證碼控制器異常', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $ip,
                'timestamp' => now()
            ]);

            return response()->json(
                ['success' => false, 'message' => '驗證異常，請稍後重試'],
                500
            );
        }
    }

    /**
     * 驗證 reCAPTCHA
     *
     * @param string $response
     * @param string $ip
     * @return bool
     */
    private function verifyCaptcha(string $response, string $ip): bool
    {
        $verify = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $response,
            'remoteip' => $ip,
        ]);

        if ($verify->json('success')) {
            Log::info('驗證碼驗證成功', [
                'ip' => $ip,
                'timestamp' => now()
            ]);
            return true;
        }

        return false;
    }

    /**
     * 增加失敗計數
     *
     * @param string $ip
     * @return int
     */
    private function incrementFailCount(string $ip): int
    {
        $failKey = "captcha_fail:$ip";
        $failCount = Redis::incr($failKey);
        Redis::expire($failKey, self::FAIL_COUNT_EXPIRATION);

        return $failCount;
    }

    /**
     * 封鎖 IP
     *
     * @param string $ip
     * @param int $failCount
     * @return void
     */
    private function blockIP(string $ip, int $failCount): void
    {
        Redis::set("bot_status:$ip", 'blocked');
        Redis::expire("bot_status:$ip", self::BLOCK_DURATION);

        Log::error('IP 被封鎖（驗證失敗次數過多）', [
            'ip' => $ip,
            'fail_count' => $failCount,
            'block_duration' => '1 小時',
            'timestamp' => now()
        ]);
    }

    /**
     * 清除驗證狀態
     *
     * @param string $ip
     * @return void
     */
    private function clearCaptchaStatus(string $ip): void
    {
        // 1. 給予通行證，有效期設為 1 小時 (3600秒)
        Redis::setex("bot_status:$ip", 3600, 'verified');

        // 2. 清除失敗計數
        Redis::del("captcha_fail:$ip");

        Log::info('IP 驗證成功，已核發臨時通行證', [
            'ip' => $ip,
            'duration' => '1 hour'
        ]);
    }
}