<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
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
        try {
            $ip = $request->ip();
            $ua = $request->header('User-Agent');
            $statusKey = "bot_status:$ip";
            $method = $request->method();
            $path = $request->path();

            // 1. 【新增】UA 基礎驗證
            if (empty($ua) || $this->isSuspiciousUa($ua)) {
                Log::warning('可疑 User-Agent 檢測', [
                    'ip' => $ip,
                    'user_agent' => $ua ?: '(空值)',
                    'method' => $method,
                    'path' => $path,
                    'timestamp' => now()
                ]);
                
                Redis::set($statusKey, 'pending_captcha');
                return $this->redirectToChallenge();
            }

            // 2. 檢查黑名單狀態
            $status = Redis::get($statusKey);

            if ($status === 'blocked') {
                Log::warning('被封鎖 IP 嘗試訪問', [
                    'ip' => $ip,
                    'user_agent' => $ua,
                    'method' => $method,
                    'path' => $path,
                    'status' => $status
                ]);
                
                abort(403, '您的 IP 因驗證失敗多次已被暫時封鎖，1 小時後重試。');
            }

            if ($status === 'pending_captcha') {
                Log::info('待驗證 IP 訪問', [
                    'ip' => $ip,
                    'method' => $method,
                    'path' => $path
                ]);
                
                return $this->redirectToChallenge();
            }

            // 3. 頻率限制 (每分鐘超過 50 次要求驗證)
            $rateKey = "rate_limit:$ip:" . now()->format('Hi');
            $count = Redis::incr($rateKey);
            if ($count == 1) Redis::expire($rateKey, 60);

            if ($count > 50) {
                Log::warning('頻率限制超限', [
                    'ip' => $ip,
                    'request_count' => $count,
                    'time_window' => now()->format('Hi'),
                    'path' => $path
                ]);
                
                Redis::set($statusKey, 'pending_captcha');
                return $this->redirectToChallenge();
            }

            // 正常訪問（可選的詳細日誌）
            // Log::debug('正常流量通過', ['ip' => $ip, 'path' => $path]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('AntiBotMiddleware 異常', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $ip ?? '未知',
                'timestamp' => now()
            ]);
            
            // 如果 Redis 連線異常，直接放行以避免服務中斷
            return $next($request);
        }
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