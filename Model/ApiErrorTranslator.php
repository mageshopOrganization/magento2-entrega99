<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model;

use Magento\Framework\Phrase;

/**
 * Translates 99Entrega API errno codes into user-facing messages.
 *
 * Storefront-friendly text (shown in checkout if showmethod=1).
 * Technical detail still goes to the log via Helper::logException.
 *
 * To customize a specific message without editing this file, add an entry to
 * i18n/pt_BR.csv (or another locale CSV) — Magento's `__()` translates at runtime.
 */
class ApiErrorTranslator
{
    /**
     * Default user-facing message when an errno is unmapped or the API failed
     * for an unknown reason.
     */
    public function getGenericMessage(): Phrase
    {
        return __('Frete 99Entrega temporariamente indisponível. Tente novamente em instantes.');
    }

    /**
     * Returns a customer-friendly Phrase for the given errno.
     */
    public function translate(int $errno): Phrase
    {
        return match (true) {
            // === Endereço / cobertura ===
            in_array($errno, [3005, 3102, 6101, 6104, 6202], true)
                => __('Endereço fora da área de cobertura 99Entrega.'),

            // === Veículo / método ===
            in_array($errno, [3004, 6103], true)
                => __('Tipo de veículo 99Entrega não disponível para esta entrega.'),

            // === Indisponibilidade temporária / horário ===
            in_array($errno, [3009, 3010, 6203, 6105], true)
                => __('99Entrega indisponível no momento. Tente novamente em instantes.'),

            // === Conta / crédito (problemas do lojista — esconder detalhe) ===
            in_array($errno, [6004, 6005, 6204], true)
                => __('Frete 99Entrega temporariamente indisponível.'),

            // === Estimate expirou (deve refazer cotação) ===
            $errno === 4002
                => __('Cotação expirou. Atualize a página para uma nova cotação.'),

            // === Parâmetros inválidos / dados incompletos ===
            in_array($errno, [1001, 3006, 3007, 6002], true)
                => __('Não foi possível cotar 99Entrega com os dados informados.'),

            default => $this->getGenericMessage(),
        };
    }
}
