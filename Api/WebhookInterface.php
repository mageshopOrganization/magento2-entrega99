<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Api;

interface WebhookInterface
{
    /**
     * Receives a webhook event pushed by 99Entrega.
     *
     * The envelope follows the documented format:
     *  { "event": "...", "event_id": "...", "message": "<json string>", "timestamp": 1730114201 }
     *
     * HMAC-SHA256 signature is read from the X-Webhook-Signature header and validated
     * against the raw request body using the signing key configured in admin.
     * Duplicate event_id values are ignored (idempotency).
     *
     * @param string $event     Event type, e.g. "DriverAccepted"
     * @param string $event_id  Idempotency key
     * @param string $message   Event-specific payload as a JSON-encoded string
     * @param int    $timestamp Event timestamp (unix seconds)
     * @return string[]  Status array, e.g. ["status" => "ok", "event_id" => "..."]
     */
    public function receive(string $event, string $event_id, string $message, int $timestamp): array;
}
