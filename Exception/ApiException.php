<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Errors returned by the 99Entrega API (errno != 0 or HTTP != 2xx).
 */
class ApiException extends LocalizedException
{
    public function __construct(
        Phrase $phrase,
        ?\Exception $cause = null,
        private readonly int $errno = 0,
        private readonly ?array $payload = null
    ) {
        parent::__construct($phrase, $cause);
    }

    public function getErrno(): int
    {
        return $this->errno;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }
}
