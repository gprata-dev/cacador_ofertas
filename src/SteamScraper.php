<?php

class SteamScraper {
    private $url = "https://store.steampowered.com/search/?maxprice=free&specials=1&ndl=1";

    /**
     * @return array
     * 
     * Sets cURL options and returns an array of free Steam games
     */
    public function searchFreeGames(): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");

        $html     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            echo "\n[BLOQUEIO] A Steam recusou a conexão! Código HTTP: {$httpCode}\n";
            return [];
        }
        if (empty($html)) {
            return [];
        }

        return $this->extractData($html);
    }

    
    /**
     * @param string $html
     * @return array
     * 
     * Extracts game data from HTML
     */
    private function extractData(string $html): array
    {
        libxml_use_internal_errors(true); 
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $results = [];
        $games   = $xpath->query('//a[contains(@class, "search_result_row")]');

        foreach($games as $game) {
            if (!$game instanceof DOMElement) continue;

            $link = explode('?', $game->getAttribute('href'))[0];
            preg_match('/app\/(\d+)\//', $link, $matches);
            $app_id = $matches[1] ?? null;

            if($app_id) {
                $title_node = $xpath->query('.//span[@class="title"]', $game);
                $title      = $title_node->length > 0 ? $title_node->item(0)->nodeValue : "Título não encontrado";
                
                $results[] = [
                    'app_id' => $app_id,
                    'title'  => $title,
                    'link'   => $link
                ];
            }
        }

        libxml_clear_errors();
        return $results;
    }
}