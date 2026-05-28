<?php

class RedditScraper {
    private $url = "https://www.reddit.com/r/FreeGameFindings/new.rss?limit=5";

    /**
     * @return array
     * 
     * Sets cURL options and returns an array of free r/FreeGameFindings games
     */
    public function searchFreeGames(): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "php:cacadordeofertas:v1.0 (by /u/Fit-Cardiologist-124)");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200 || empty($response)) {
            throw new Exception("Erro HTTP {$httpCode} ou resposta vazia no Reddit");
        }

        return $this->extractData($response);
    }

    /**
     * @param string $xmlResponse
     * @return array
     * 
     * Extracts game data from xml
     */
    private function extractData(string $xmlResponse): array
    {
        $results = [];

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlResponse);

        if ($xml === false) {
            echo "\n[AVISO] A resposta do Reddit não é um XML válido. Ignorando...\n";
            libxml_clear_errors(); 
            return [];
        }

        if (isset($xml->entry)) {        
            foreach ($xml->entry as $entry) {
                $limit24h             = time() - (60 * 60 * 24);
                $publicationTimestamp = strtotime((string)$entry->published);
                if ($limit24h > $publicationTimestamp) {
                    continue;
                }

                $postId = (string)$entry->id;
                $title  = (string)$entry->title;

                $contentHtml = html_entity_decode((string)$entry->content);
                if (preg_match('/<span><a href="([^"]+)">\[link\]<\/a><\/span>/', $contentHtml, $matches)) {
                    $url     = $matches[1];
                } else {
                    $url     = (string)$entry->link['href'];
                }

                $regexTitle = '/(Exiled Giveaways|FGF Giveaway)/i';
                $regexUrl   = '/(givee\.club|gleam\.io|alienwarearena)/i';
                if (preg_match($regexTitle, $title) || preg_match($regexUrl, $url)) {
                    continue;
                }

                $cleanId = explode('t3_', $postId);
                $finalId = isset($cleanId[1]) ? $cleanId[1] : md5($title);

                $results[] = [
                    'app_id' => $finalId,
                    'title'  => $title,
                    'link'   => $url
                ];
            }
        }
    
        libxml_clear_errors();
        return $results;
    }
}