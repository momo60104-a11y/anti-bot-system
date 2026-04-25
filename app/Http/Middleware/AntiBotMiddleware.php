<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class AntiBotMiddleware
{
    // 預定義的爬蟲關鍵字黑名單
    protected $botKeywords = [
        'python-requests', 'guzzlehttp', 'scrapy', 'headlesschrome', 
        'selenium', 'puppeteer', 'curl', 'wget', 'postman'
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $ua = $request->header('User-Agent');
        // $ua = 'python-requests/2.25.1';
        $statusKey = "bot_status:$ip";

        // 1. 【新增】UA 基礎驗證
        // 如果沒有 UA，或是 UA 包含黑名單關鍵字，直接要求驗證碼
        if (empty($ua) || $this->isSuspiciousUa($ua)) {
            Redis::set($statusKey, 'pending_captcha');
            return $this->redirectToChallenge();
        }

        // 2. 檢查黑名單狀態
        $status = Redis::get($statusKey);

        if ($status === 'blocked') {
            abort(403, '您的 IP 因驗證失敗多次已被暫時封鎖，1 小時後重試。');
        }

        if ($status === 'pending_captcha') {
            return $this->redirectToChallenge();
        }

        // 3. 頻率限制 (每分鐘超過 50 次要求驗證)
        $rateKey = "rate_limit:$ip:" . now()->format('Hi');
        $count = Redis::incr($rateKey);
        if ($count == 1) Redis::expire($rateKey, 60);

        if ($count > 50) {
            Redis::set($statusKey, 'pending_captcha');
            return $this->redirectToChallenge();
        }

        return $next($request);
    }

    /**
     * 檢查 UA 是否包含可疑字串
     */
    private function isSuspiciousUa($ua)
    {
        // 將 UA 轉小寫後，檢查 botKeywords 中是否有任何一項被包含在 UA 內
        return collect($this->botKeywords)->contains(fn($bot) => Str::contains(strtolower($ua), $bot));
    }

    private function redirectToChallenge() 
    {
        // 確保驗證頁面能正確顯示
        return response()->view('errors.captcha_challenge', [], 401);
    }
}