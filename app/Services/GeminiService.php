<?php

namespace app\Services;

class GeminiService {
    private $apiKey;
    private $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct($apiKey = null) {
        // A chave da API deve ser definida no ambiente (ex: arquivo .env)
        $this->apiKey = $apiKey ?? getenv('GEMINI_API_KEY');
    }

    /**
     * Analisa o problema do chamado e retorna sugestões usando o Google Gemini
     *
     * @param string $descricaoProblema A descrição relatada pelo cliente
     * @return string A resposta gerada pela IA
     */
    public function analyzeTicket($descricaoProblema) {
        if (empty($this->apiKey)) {
            return "Erro: Chave de API do Gemini não configurada.";
        }

        $prompt = "Atue como um Consultor Técnico Sênior de TI. " .
                  "Um cliente relatou o seguinte problema no seu chamado de suporte: \n\n\"" . $descricaoProblema . "\"\n\n" .
                  "Forneça um diagnóstico possível, sugestões de solução passo a passo e as possíveis causas raízes para auxiliar o técnico no atendimento.";

        $data = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ];

        $ch = curl_init($this->apiUrl . '?key=' . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Erro na API do Gemini: " . $response);
            return "Não foi possível obter uma análise da IA no momento. Tente novamente mais tarde.";
        }

        $responseData = json_decode($response, true);
        
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            return $responseData['candidates'][0]['content']['parts'][0]['text'];
        }

        return "Nenhuma sugestão foi retornada pela IA.";
    }
}
