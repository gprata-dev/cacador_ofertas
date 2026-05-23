<?php 

class TelegramBot
{
    private $token;
    private $chatId;

    public function __construct(array $config)
    {
        $this->token  = $config['token'];
        $this->chatId = $config['chat_id'];
    }

    /**
     * @param string $message
     * @return array
     * 
     * Send a message to Telegram
     */
    public function sendMessage(string $message): array
    {
        $url  = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $data = [
            'chat_id'                  => $this->chatId,
            'text'                     => $message,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => false
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($result === false) {
            return ['status' => 'error', 'message' => "\n[FALHA TELEGRAM] cURL falhou: " . curl_error($ch) . "\n"];
        } elseif ($httpCode !== 200) {
            return ['status' => 'error', 'message' =>"\n[FALHA TELEGRAM] API rejeitou o envio (Código {$httpCode}): {$result}\n"];
        } else {
            return ['status' => 'success', 'message' => "\n[OK TELEGRAM] Mensagem enviada com sucesso!\n"];
        }
    }
}