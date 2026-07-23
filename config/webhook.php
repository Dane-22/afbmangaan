<?php
/**
 * Webhook Configuration
 * AFB Mangaan Attendance System
 * 
 * NOTE: Webhook settings should be configured via environment variables.
 * For security, do not hardcode secrets or URLs in this file.
 */

// Load from environment variables only - no hardcoded fallbacks for security
$webhookConfig = [
    // Security secret for validating make.com webhooks
    // Generate a strong random string and set it in your .env file
    'secret' => getenv('WEBHOOK_SECRET') ?: null,
    
    // make.com webhook URL for exporting data TO Google Sheets (optional)
    // Get this from your make.com scenario webhook module
    'make_webhook_url' => getenv('MAKE_WEBHOOK_URL') ?: null,
    
    // Enable/disable webhook logging for debugging
    'enable_logging' => getenv('WEBHOOK_LOGGING') === 'true' ?: true,
];

/**
 * Get webhook configuration value
 */
function getWebhookConfig($key, $default = null) {
    global $webhookConfig;
    return $webhookConfig[$key] ?? $default;
}
