<?php 

namespace Services;

use Models\TrackedProductsModel;
use TelegramClient;

class TelegramService
{
    private TrackedProductsModel $trackedProductsModel;
    private TelegramClient       $telegramClient;
    private string               $chatId;
    private ?int                 $underEditingId        = null;
    private int                  $lastUpdateMsgId       = 0;

    /**
     * Initializes the Telegram command handling service.
     * 
     * @param TrackedProductsModel $trackedProductsModel Model for tracked products.
     * @param TelegramClient       $telegram             Telegram API client.
     * @param array                $telConfig            Telegram bot settings.
     */
    public function __construct(TrackedProductsModel $trackedProductsModel, TelegramClient $telegram, array $telConfig)
    {
        $this->trackedProductsModel = $trackedProductsModel;
        $this->chatId               = $telConfig['chat_id'];
        $this->telegramClient       = $telegram;
    }

    /**
     * Retrive messages to categorize and control user actions to the bot
     * 
     * @return void
     */
    public function handleUserToBotMessages(): void
    {
        $messages = $this->telegramClient->getUpdates($this->lastUpdateMsgId);

        if (!empty($messages)) {
            foreach ($messages as $msg) {
                $this->lastUpdateMsgId = $msg['update_id'] + 1;
                $chatId = $msg['message']['chat']['id'];

                if (!isset($msg['message']['text']) || $chatId != $this->chatId) {
                    continue; 
                }

                $text = trim($msg['message']['text']);

                if ($this->underEditingId !== null) {
                    if ($text === '/cancelar') {
                        $this->underEditingId = null;
                        $telReturn = $this->telegramClient->sendMessage("✅ Edição cancelada.");
                        if ($telReturn['status'] === 'error') {
                            echo "[FALHA TELEGRAM - CANCELAR EDIÇÃO] " . $telReturn['return'] . "\n";
                        }
                        continue;
                    }

                    if (preg_match('/^[\d.,]+$/', $text)) {
                        $newPrice = (float) str_replace(',', '.', $text);

                        $res = $this->trackedProductsModel->updateProduct($newPrice, $this->underEditingId);
                        if($res['status'] === 'error') {
                            $dbError  = "[ERRO BANCO] " . $res['return'] . "\n";
                            echo $dbError;                
                            $errorTelReturn = $this->telegramClient->sendMessage($dbError);
                            if ($errorTelReturn['status'] === 'error') {
                                echo "[FALHA TELEGRAM - EDITAR AUX] " . $errorTelReturn['return'] . "\n";
                            }
                            continue;
                        }

                        if ($res['return'] == 0) {
                            $telReturn = $this->telegramClient->sendMessage("⚠️ O preço informado já era o mesmo ou este produto #{$this->underEditingId} não existe.");
                            if ($telReturn['status'] === 'error') {
                                echo "[FALHA TELEGRAM - EDITAR AUX] " . $telReturn['return'] . "\n";
                            }
                        } else {
                            $telReturn = $this->telegramClient->sendMessage("✅ <b>Produto #{$this->underEditingId} atualizado!</b>\nNovo preço-alvo: R$ " . number_format($newPrice, 2, ',', '.'));
                            if ($telReturn['status'] === 'error') {
                                echo "[FALHA TELEGRAM - EDITAR AUX] " . $telReturn['return'] . "\n";
                            }

                            $this->underEditingId = null; 
                        }
                    } else {
                        $telReturn = $this->telegramClient->sendMessage("⚠️ Formato inválido.\nDigite apenas o valor (ex: 35.90) ou clique em /cancelar.");
                        if ($telReturn['status'] === 'error') {
                            echo "[FALHA TELEGRAM - EDITAR AUX] " . $telReturn['return'] . "\n";
                        }
                    }
                    
                    continue;
                }

                if (str_starts_with($text, '/monitorar')) {
                    if (preg_match('/^\/monitorar\s+(https?:\/\/\S+)\s+([\d.,]+)$/i', $text, $matches)) {
                        $productUrl  = $matches[1];
                        $targetPrice = (float) str_replace(',', '.', $matches[2]);

                        $res = $this->trackedProductsModel->insertProduct($productUrl, $targetPrice);
                        if($res['status'] === 'error') {
                            $dbError  = "[ERRO BANCO] " . $res['return'] . "\n";
                            echo $dbError;                
                            $errorTelReturn = $this->telegramClient->sendMessage($dbError);
                            if ($errorTelReturn['status'] === 'error') {
                                echo "[FALHA TELEGRAM - MONITORAR] " . $errorTelReturn['return'] . "\n";
                            }
                            continue;
                        }

                        if($res['return'] == 0) {
                            $telReturn = $this->telegramClient->sendMessage("⚠️ O produto {$productUrl} já estava sendo monitorado.");
                            if ($telReturn['status'] === 'error') {
                                echo "[FALHA TELEGRAM - MONITORAR] " . $telReturn['return'] . "\n";
                            }
                        } else {
                            $telReturn = $this->telegramClient->sendMessage("✅ <b>Produto #{$res['return']} adicionado!</b>\n<b>Alvo:</b> R$ " . number_format($targetPrice, 2, ',', '.') . "\n<b>URL:</b> " . $productUrl);
                            if ($telReturn['status'] === 'error') {
                                echo "[FALHA TELEGRAM - MONITORAR] " . $telReturn['return'] . "\n";
                            }
                        }
                    } else {
                        $telReturn = $this->telegramClient->sendMessage("⚠️ Uso correto: /monitorar [LINK] [PREÇO]\nEx: /monitorar https://amazon.com.br/dp/123 35.90");
                        if ($telReturn['status'] === 'error') {
                            echo "[FALHA TELEGRAM - MONITORAR] " . $telReturn['return'] . "\n";
                        }
                    }

                } elseif ($text === '/listar') {
                    $res = $this->trackedProductsModel->getAllProducts();
                    if($res['status'] === 'error') {
                        $dbError  = "[ERRO BANCO] " . $res['return'] . "\n";
                        echo $dbError;                
                        $errorTelReturn = $this->telegramClient->sendMessage($dbError);
                        if ($errorTelReturn['status'] === 'error') {
                            echo "[FALHA TELEGRAM - LISTAR] " . $errorTelReturn['return'] . "\n";
                        }
                        continue;
                    }
                    $products = $res['return'];
                    if (empty($products)) {
                        $telReturn = $this->telegramClient->sendMessage("📭 Nenhum produto sendo rastreado no momento.");
                        if ($telReturn['status'] === 'error') {
                            echo "[FALHA TELEGRAM - LISTAR] " . $telReturn['return'] . "\n";
                        }
                    } else {
                        $msgList = "📋 <b>Produtos Sendo Rastreados:</b>\n\n";
                        foreach ($products as $prod) {
                            $actualPrice = $prod['last_price'] ? "R$ " . number_format($prod['last_price'], 2, ',', '.') : "Aguardando...";
                            $targetPrice = "R$ " . number_format($prod['target_price'], 2, ',', '.');
                            $name        = htmlspecialchars($prod['product_name']);

                            $msgList    .= "🔹 <b>ID: {$prod['id']}</b> - {$name}\n";
                            $msgList    .= "Alvo: {$targetPrice} | Atual: {$actualPrice}\n";
                            $msgList    .= "✍️ /editar_{$prod['id']} | ❌ /deletar_{$prod['id']}\n\n";
                        }
                        $telReturn = $this->telegramClient->sendMessage($msgList);
                        if ($telReturn['status'] === 'error') {
                            echo "[FALHA TELEGRAM - LISTAR] " . $telReturn['return'] . "\n";
                        }
                    }

                } elseif (str_starts_with($text, '/editar')) {
                    if (preg_match('/^\/editar_(\d+)$/i', $text, $matches)) {
                        $idProd               = (int)$matches[1];
                        $this->underEditingId = $idProd;

                        $telReturn = $this->telegramClient->sendMessage("✏️ <b>Editando o produto #{$idProd}</b>\n\nDigite o novo valor desejado\nOu clique em /cancelar se mudou de ideia.");
                        if ($telReturn['status'] === 'error') {
                            echo "[FALHA TELEGRAM - EDITAR] " . $telReturn['return'] . "\n";
                        }
                    } else {
                        $telReturn = $this->telegramClient->sendMessage("⚠️ Uso correto: /editar_[ID]\nEx: /editar_1");
                        if ($telReturn['status'] === 'error') {
                            echo "[FALHA TELEGRAM - EDITAR] " . $telReturn['return'] . "\n";
                        }
                    }

                } elseif (str_starts_with($text, '/deletar')) {
                    if (preg_match('/^\/deletar_?(\d+)$/i', $text, $matches)) {
                        $prodId = (int)$matches[1];

                        $res = $this->trackedProductsModel->deleteProduct($prodId);
                        if($res['status'] === 'error') {
                            $dbError  = "[ERRO BANCO] " . $res['return'] . "\n";
                            echo $dbError;                
                            $errorTelReturn = $this->telegramClient->sendMessage($dbError);
                            if ($errorTelReturn['status'] === 'error') {
                                echo "[FALHA TELEGRAM - DELETAR] " . $errorTelReturn['return'] . "\n";
                            }
                            continue;
                        }

                        if ($res['return'] == 0) {
                            $telReturn = $this->telegramClient->sendMessage("⚠️ O produto #{$prodId} não existe.");
                            if ($telReturn['status'] === 'error') {
                                echo "[FALHA TELEGRAM - DELETAR] " . $telReturn['return'] . "\n";
                            }
                        } else {
                            $telReturn = $this->telegramClient->sendMessage("✅ Produto #{$prodId} removido do monitoramento.");
                            if ($telReturn['status'] === 'error') {
                                echo "[FALHA TELEGRAM - DELETAR] " . $telReturn['return'] . "\n";
                            }
                        }
                    } else {
                        $telReturn = $this->telegramClient->sendMessage("⚠️ Uso correto: /deletar_[ID]\nEx: /deletar_5");
                        if ($telReturn['status'] === 'error') {
                            echo "[FALHA TELEGRAM - DELETAR] " . $telReturn['return'] . "\n";
                        }
                    }
                }
            }
        }
    }
}