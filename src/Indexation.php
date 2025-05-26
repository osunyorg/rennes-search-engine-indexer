<?php

namespace Rennes\Scripts;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Spatie\YamlFrontMatter\Document;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Symfony\Component\Yaml\Yaml;

class Indexation
{
    protected Client $client;

    protected array $config;

    protected array $header;

    public function __construct()
    {
        $apiUrl = getenv('API_URL');
        $apiKey = getenv('API_KEY');
        $apiBasicAuth = getenv('API_BASIC_AUTH') ?: null;

        if (!$apiUrl || !$apiKey ) {
            die("❌ Error: One or more environment variables are missing.\n");
        }

        $projectRoot = getcwd();
        $this->config = Yaml::parseFile($projectRoot . '/config/production/config.yaml');
        $this->client = new Client([
            'base_uri' => $apiUrl
        ]);

        $this->header = array_merge([
            'Content-Type' => 'application/json',
            'X-api-key'    => $apiKey,
        ], $apiBasicAuth ? ['Authorization' => 'Basic ' . $apiBasicAuth] : []);
    }

    public function run()
    {
        $documents = $this->getDocuments();
        $totalDocs = count($documents);
        if (0 === $totalDocs) {
            echo "⚠️ No documents to index.\n";
            return;
        }

        try {
            $response = $this->client->post('/api/v1/search/index/' . $this->config['osuny']['website']['id'], [
                RequestOptions::HEADERS => $this->header,
                RequestOptions::BODY => json_encode($documents),
            ]);

            echo "✅ Success: code {$response->getStatusCode()} ! Total of {$totalDocs} documents indexed". "\n";

        } catch (RequestException $e) {
            echo '❌ Error while indexing: ' . $e->getMessage() . "\n";
            if ($e->hasResponse()) {
                echo '📡 API Response : ' . $e->getResponse()->getBody() . "\n";
            }
        }
    }

    public function getDocuments()
    {
        $documents = [];
        $projectRoot = getcwd();
        $data = $this->getData($projectRoot . '/content/fr/pages');

        /** @var Document $item */
        foreach ($data as $item) {
            $search = $item->matter('search');
            $taxonomies = $item->matter('taxonomies');
            $category = '';
            if (!empty($taxonomies)) {
                foreach ($taxonomies as $taxonomy) {
                    if (!empty($taxonomy['categories']) && $taxonomy['slug'] === 'rubrique') {
                        $category = ucfirst($taxonomy['categories'][0]['name']);
                    }
                }
            }

            $documents[] = [
                'sourceId' => $search['about_id'],
                'sourceUrl' => $this->config['baseURL'] . $search['url'],
                'title' => $search['title'],
                'identifier' => $search['about_id'],
                'summary' => strip_tags($search['summary']),
                'body' => strip_tags($search['body']),
                'category' => $category,
            ];
            echo "Indexing document: {$search['title']} ({$search['about_id']})\n";
        }

        return $documents;
    }

    public function getData(string $dir, array &$content = [])
    {
        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item !== "." && $item !== "..") {
                $path = $dir . DIRECTORY_SEPARATOR . $item;

                if (is_dir($path)) {
                    $this->getData($path, $content);
                }
                elseif (pathinfo($path, PATHINFO_EXTENSION) === 'html') {
                    $file_content = file_get_contents($path);
                    $data = YamlFrontMatter::parse($file_content);

                    $content[] = $data;
                }
            }
        }

        return $content;
    }
}

