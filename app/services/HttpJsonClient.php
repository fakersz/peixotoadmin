<?php
/** Cliente HTTP JSON mínimo com timeout e erros sanitizados. */

class HttpJsonException extends RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $errorCode,
        string $message,
        public readonly ?array $response = null
    ) {
        parent::__construct($message);
    }
}

class HttpJsonClient
{
    public function request(string $method, string $url, array $headers, ?array $payload, int $timeout): array
    {
        $ch = curl_init($url);
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $headerLines,
            CURLOPT_TIMEOUT        => max(1, $timeout),
            CURLOPT_CONNECTTIMEOUT => min(10, max(1, $timeout)),
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false) {
            throw new HttpJsonException(0, 'network_error', $curlError ?: 'Falha de rede.');
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new HttpJsonException($status, 'invalid_json', 'Resposta inválida do serviço externo.');
        }

        if ($status < 200 || $status >= 300) {
            $code = (string) ($data['error']['code'] ?? $data['code'] ?? ('http_' . $status));
            throw new HttpJsonException($status, $code, self::safeErrorMessage($status, $code), $data);
        }

        return $data;
    }

    public static function safeErrorMessage(int $status, string $code): string
    {
        return match ($status) {
            401 => 'Credencial inválida ou ausente.',
            403 => 'A integração não tem permissão para acessar este recurso.',
            404 => 'Recurso não encontrado ou não compartilhado com a integração.',
            408, 504 => 'A consulta excedeu o tempo limite.',
            429 => 'Limite de requisições atingido. Tente novamente em instantes.',
            500, 502, 503, 529 => 'Serviço externo temporariamente indisponível.',
            default => $code ? 'Erro externo: ' . $code : 'Erro externo inesperado.',
        };
    }
}
