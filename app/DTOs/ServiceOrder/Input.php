<?php
namespace app\DTOs\ServiceOrder;

use app\Domain\ServiceOrder\Exception\ValidationException;
use app\Domain\ServiceOrder\Money;

/**
 * Leitor/validador de entrada (array de $_POST) usado pelos DTOs.
 *
 * Acumula todos os erros e os lança de uma vez em assertValid(), para o formulário
 * mostrar todos os problemas em uma única resposta.
 *
 * Textos longos NÃO passam por strip_tags: relatórios técnicos legitimamente contêm
 * "<", ">" e "&" (ex.: "VLAN <10>", "ACL ... > 1024"). A defesa contra XSS é escapar na saída
 * (Security::esc nas views); aqui só normalizamos espaços/quebras de linha e limitamos tamanho.
 */
final class Input
{
    /** @var array<string, mixed> */
    private $data;
    /** @var array<string, string> */
    private $errors = [];

    /**
     * @param array<string, mixed> $data normalmente $_POST
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Texto multilinha (relatos, diagnósticos, relatórios).
     *
     * @return string|null null quando vazio e não obrigatório
     */
    public function longText(string $key, string $label, int $maxChars, bool $required): ?string
    {
        $value = $this->raw($key);
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[^\P{C}\n\t]+/u', '', $value) ?? '';
        return $this->finishText(trim($value), $key, $label, $maxChars, $required);
    }

    /**
     * Texto de uma linha (título, modelo, série). Quebras de linha viram espaço.
     *
     * @return string|null null quando vazio e não obrigatório
     */
    public function line(string $key, string $label, int $maxChars, bool $required): ?string
    {
        $value = preg_replace('/\s+/u', ' ', $this->raw($key)) ?? '';
        $value = preg_replace('/\p{C}+/u', '', $value) ?? '';
        return $this->finishText(trim($value), $key, $label, $maxChars, $required);
    }

    /**
     * Valor monetário digitado ("1.234,56" ou "12,5"). Para obrigar a discriminação de custos,
     * o campo precisa ser enviado preenchido, mesmo que seja "0,00".
     */
    public function money(string $key, string $label, bool $required = true): ?Money
    {
        $raw = trim($this->raw($key));
        if ($raw === '') {
            if ($required) {
                $this->errors[$key] = 'Informe ' . $label . ' (use 0,00 se não houver).';
            }
            return null;
        }
        $normalized = str_replace(['R$', ' '], '', $raw);
        if (strpos($normalized, ',') !== false) {
            $normalized = str_replace(['.', ','], ['', '.'], $normalized);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $normalized)) {
            // "1.234" sem vírgula é milhar no padrão brasileiro, não 1,234.
            $normalized = str_replace('.', '', $normalized);
        }
        if (strpos($normalized, '-') === 0) {
            $this->errors[$key] = ucfirst($label) . ' não pode ser negativo.';
            return null;
        }
        if (!preg_match('/^\d{1,11}(\.\d{1,2})?$/', $normalized)) {
            $this->errors[$key] = 'Valor inválido em ' . $label . ' (use o formato 1.234,56).';
            return null;
        }
        return Money::fromDecimal($normalized);
    }

    /**
     * @param callable $isValid fn(string): bool
     */
    public function enum(string $key, string $label, callable $isValid, bool $required = true): ?string
    {
        $value = trim($this->raw($key));
        if ($value === '') {
            if ($required) {
                $this->errors[$key] = 'Selecione ' . $label . '.';
            }
            return null;
        }
        if (!$isValid($value)) {
            $this->errors[$key] = ucfirst($label) . ' inválido.';
            return null;
        }
        return $value;
    }

    public function id(string $key, string $label, bool $required = true): ?int
    {
        $value = trim($this->raw($key));
        if ($value === '') {
            if ($required) {
                $this->errors[$key] = 'Selecione ' . $label . '.';
            }
            return null;
        }
        if (!ctype_digit($value) || (int)$value < 1) {
            $this->errors[$key] = ucfirst($label) . ' inválido.';
            return null;
        }
        return (int)$value;
    }

    public function addError(string $key, string $message): void
    {
        $this->errors[$key] = $message;
    }

    /**
     * @throws ValidationException se algum campo falhou
     */
    public function assertValid(): void
    {
        if ($this->errors !== []) {
            throw new ValidationException($this->errors);
        }
    }

    private function raw(string $key): string
    {
        $value = $this->data[$key] ?? '';
        return is_scalar($value) ? (string)$value : '';
    }

    private function finishText(string $value, string $key, string $label, int $maxChars, bool $required): ?string
    {
        if ($value === '') {
            if ($required) {
                $this->errors[$key] = 'Informe ' . $label . '.';
            }
            return null;
        }
        if (self::length($value) > $maxChars) {
            $this->errors[$key] = ucfirst($label) . ' excede ' . $maxChars . ' caracteres.';
            return null;
        }
        return $value;
    }

    private static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : (int)preg_match_all('/./us', $value);
    }
}
