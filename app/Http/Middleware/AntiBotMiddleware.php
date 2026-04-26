<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AntiBotMiddleware
{
    protected $botKeywords = [
        'python-requests', 'guzzlehttp', 'scrapy', 'headlesschrome', 
        'selenium', 'puppeteer', 'curl', 'wget', 'postman'
    ];

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $ip = $request->ip();
            $statusKey = "bot_status:$ip";
            $status = Redis::get($statusKey);

            // 1. 【優先檢查】如果已經驗證過了，直接放行 (不看 UA 了)
            if ($status === 'verified') {
                return $next($request);
            }

            // 2. 檢查是否被永久封鎖
            if ($status === 'blocked') {
                $this->logWarning($request, '被封鎖 IP 嘗試訪問');
                abort(403, '您的 IP 已被封鎖');
            }

            // 3. 如果是待驗證狀態，繼續顯示驗證頁面
            if ($status === 'pending_captcha') {
                return $this->redirectToChallenge();
            }

            // 4. 【新訪客或過期訪客】才進行 UA 基礎驗證
            if ($this->shouldChallengeUserAgent($request)) {
                return $this->triggerChallenge($request, '可疑 User-Agent 檢測');
            }

            // 5. 頻率限制
            if ($this->isRateLimited($ip)) {
                return $this->triggerChallenge($request, '頻率限制超限');
            }

            return $next($request);

        } catch (\Exception $e) {
            $this->logError($e, $request);
            return $next($request);
        }
    }

    /**
     * 檢查 User-Agent 是否異常
     */
    private function shouldChallengeUserAgent(Request $request): bool
    {
        $ua = $request->header('User-Agent');
        if (empty($ua)) return true;

        $uaLower = strtolower($ua);
        return collect($this->botKeywords)->contains(fn($bot) => Str::contains($uaLower, $bot));
    }

    /**
     * 執行頻率限制邏輯
     */
    private function isRateLimited(string $ip): bool
    {
        $rateKey = "rate_limit:$ip:" . now()->format('Hi');
        $count = Redis::incr($rateKey);
        
        if ($count == 1) {
            Redis::expire($rateKey, 60);
        }

        return $count > 50;
    }

    /**
     * 觸發驗證流程並記錄 Log
     */
    private function triggerChallenge(Request $request, string $reason): Response
    {
        $this->logWarning($request, $reason);
        Redis::set("bot_status:{$request->ip()}", 'pending_captcha');
        return $this->redirectToChallenge();
    }

    /**
     * 統一的日誌記錄
     */
    private function logWarning(Request $request, string $message): void
    {
        Log::warning($message, [
            'ip' => $request->ip(),
            'ua' => $request->header('User-Agent') ?: '(空)',
            'path' => $request->path(),
            'method' => $request->method(),
        ]);
    }

    /**
     * 例外錯誤日誌
     */
    private function logError(\Exception $e, Request $request): void
    {
        Log::error('AntiBotMiddleware 異常', [
            'msg' => $e->getMessage(),
            'ip' => $request->ip(),
            'line' => $e->getLine()
        ]);
    }

    private function redirectToChallenge(): Response
    {
        return response()->view('errors.captcha_challenge', [], 401);
    }
}