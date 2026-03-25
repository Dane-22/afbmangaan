<?php
/**
 * Webhook Configuration
 * AFB Mangaan Attendance System
 * 
 * NOTE: Since .env is gitignored, webhook settings can be configured here.
 * For security, you should set these via environment variables in production.
 */

// Load from environment if available, otherwise use defaults
$webhookConfig = [
    // Security secret for validating make.com webhooks
    // Generate a strong random string and set it in your environment or here
    'secret' => getenv('WEBHOOK_SECRET') ?: 'change-this-to-a-random-secret-key',
    
    // make.com webhook URL for exporting data TO Google Sheets (optional)
    // Get this from your make.com scenario webhook module
    'make_webhook_url' => getenv('MAKE_WEBHOOK_URL') ?: 'https://hook.eu1.make.com/toplmvfj479kvfkx4f0erlifwrsnstml',
    
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
