<?php

class RedditScraper {
    private $url = "https://www.reddit.com/r/FreeGameFindings/new.json?limit=5";

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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            echo "\n[BLOQUEIO REDDIT] Código HTTP: {$httpCode}\n";
            return [];
        }
        if (empty($response)) {
            return [];
        }

        return $this->extractData($response);
    }

    /**
     * @param string $jsonResponse
     * @return array
     * 
     * Extracts game data from json
     */
    private function extractData(string $jsonResponse): array
    {
        $data    = json_decode($jsonResponse, true);
        $results = [];

        if (isset($data['data']['children'])) {
            foreach ($data['data']['children'] as $post) {
                $postData = $post['data'];
                
                $postId = $postData['id'];
                $title  = $postData['title'];
                $url    = $postData['url'];

                if(stripos($title, 'Exiled') !== false || str_contains($url, 'givee.club') || str_contains($url, 'gleam.io')) {
                    continue;
                }

                $results[] = [
                    'app_id' => $postId,
                    'title'  => $title,
                    'link'   => $url
                ];
            }
        }

        return $results;
    }
}