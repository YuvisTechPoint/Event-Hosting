<?php

namespace HiEvents\Services\Infrastructure\Security;

use Illuminate\Cache\Repository;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Psr\Log\LoggerInterface;

class AuthSecurityService
{
    public function __construct(
        private readonly Repository     $cache,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @throws ThrottleRequestsException
     */
    public function assertNotLockedOut(string $action, string $identifier, string $ip): void
    {
        $attempts = (int) $this->cache->get($this->attemptKey($action, $identifier), 0);

        if ($attempts === 0) {
            return;
        }

        $lockoutUntil = $this->cache->get($this->lockoutKey($action, $identifier));

        if ($lockoutUntil !== null && now()->timestamp < (int) $lockoutUntil) {
            $retryAfter = (int) $lockoutUntil - now()->timestamp;

            throw new ThrottleRequestsException(
                message: __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => $retryAfter]),
                headers: ['Retry-After' => $retryAfter],
            );
        }
    }

    public function recordFailedAttempt(string $action, string $identifier, string $ip): void
    {
        $attemptKey = $this->attemptKey($action, $identifier);
        $attempts = (int) $this->cache->increment($attemptKey);
        $this->cache->put($attemptKey, $attempts, now()->addMinutes($this->decayMinutes($action)));

        $backoffSeconds = $this->backoffSeconds($attempts);
        $this->cache->put(
            $this->lockoutKey($action, $identifier),
            now()->addSeconds($backoffSeconds)->timestamp,
            now()->addSeconds($backoffSeconds),
        );

        $this->logSuspiciousActivity($action, $identifier, $ip);
    }

    public function recordSuccessfulAttempt(string $action, string $identifier): void
    {
        $this->cache->forget($this->attemptKey($action, $identifier));
        $this->cache->forget($this->lockoutKey($action, $identifier));
    }

    private function backoffSeconds(int $attempts): int
    {
        $base = config('security.auth.brute_force_base_seconds', 2);
        $max = config('security.auth.brute_force_max_seconds', 900);

        return min($max, (int) pow($base, $attempts));
    }

    private function decayMinutes(string $action): int
    {
        return match ($action) {
            'login' => config('security.auth.login_decay_minutes', 15),
            'register' => config('security.auth.register_decay_minutes', 60),
            'password_reset' => config('security.auth.password_reset_decay_minutes', 60),
            default => 15,
        };
    }

    private function logSuspiciousActivity(string $action, string $identifier, string $ip): void
    {
        $ipTrackingKey = "auth_security:ips:{$action}:{$identifier}";
        $ips = $this->cache->get($ipTrackingKey, []);
        $ips[$ip] = now()->timestamp;
        $this->cache->put(
            $ipTrackingKey,
            $ips,
            now()->addMinutes(config('security.auth.suspicious_ip_window_minutes', 60)),
        );

        $uniqueIpCount = count($ips);
        if ($uniqueIpCount >= config('security.auth.suspicious_ip_threshold', 5)) {
            $this->logger->warning('Suspicious auth activity: multiple IPs for identifier', [
                'action' => $action,
                'identifier' => $identifier,
                'ip_count' => $uniqueIpCount,
                'ips' => array_keys($ips),
            ]);
        }

        $rapidKey = "auth_security:rapid:{$ip}";
        $rapidCount = (int) $this->cache->increment($rapidKey);
        if ($rapidCount === 1) {
            $this->cache->put(
                $rapidKey,
                $rapidCount,
                now()->addSeconds(config('security.auth.rapid_request_window_seconds', 10)),
            );
        }

        if ($rapidCount >= config('security.auth.rapid_request_threshold', 20)) {
            $this->logger->warning('Suspicious auth activity: rapid requests from IP', [
                'action' => $action,
                'ip' => $ip,
                'request_count' => $rapidCount,
            ]);
        }
    }

    private function attemptKey(string $action, string $identifier): string
    {
        return "auth_security:attempts:{$action}:" . sha1($identifier);
    }

    private function lockoutKey(string $action, string $identifier): string
    {
        return "auth_security:lockout:{$action}:" . sha1($identifier);
    }
}
