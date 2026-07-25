<?php 

class TelegramClient
{
    private string $token;
    private string $chatId;

    /**
     * Initializes the Telegram client with the bot configuration.
     * 
     * @param array $config Telegram bot settings.
     */
    public function __construct(array $config)
    {
        $this->token  = $config['token'];
        $this->chatId = $config['chat_id'];
    }

    /**
     * Send a message to Telegram
     * 
     * @param string $message String message to send
     * @return array          Array with status and return
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
            return ['status' => 'error', 'return'   => "\n[FALHA TELEGRAM] cURL falhou: " . curl_error($ch) . "\n"];
        } elseif ($httpCode !== 200) {
            return ['status' => 'error', 'return'   => "\n[FALHA TELEGRAM] API rejeitou o envio (Código {$httpCode}): {$result}\n"];
        } else {
            return ['status' => 'success', 'return' => "\n[OK TELEGRAM] Mensagem enviada com sucesso!\n"];
        }
    }

    /**
     * Search for new messages sent to the bot
     * 
     * @param int    $offset Last processed message ID
     * @return array         Array of new messages sent to the bot
     */
    public function getUpdates(int $offset = 0): array
    {
        $url = "https://api.telegram.org/bot{$this->token}/getUpdates?offset={$offset}&timeout=5";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200 || empty($response)) {
            return [];
        }

        $data = json_decode($response, true);
        return (isset($data['ok']) && $data['ok'] === true) ? $data['result'] : [];
    }
}