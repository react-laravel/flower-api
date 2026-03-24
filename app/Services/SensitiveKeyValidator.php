<?php

namespace App\Services;

/**
 * Validates setting keys for sensitive patterns.
 * Extracted from SiteSettingController to fix SRP violation.
 */
class SensitiveKeyValidator
{
    /**
     * Patterns used to detect sensitive setting keys that should not be
     * exposed via the public API.
     */
    private const SENSITIVE_PATTERNS = [
        'smtp_', 'aws_', 'password', 'secret', 'token', 'credential',
        'sendgrid_', 'mailgun_', 'twilio_', 'stripe_', 'slack_',
        'github_', 'openai_', 'mailchimp_', 'fb_|facebook_', 'google_',
        'jwt_', 'private_', 'encryption_', 'paypal_',
    ];

    /**
     * Check if a key matches any sensitive pattern.
     */
    public function isSensitive(string $key): bool
    {
        return (bool) preg_match(
            '/(' . implode('|', self::SENSITIVE_PATTERNS) . ')/i',
            $key
        );
    }
}