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
    protected array $indexConfig;

    protected array $mapping;

    protected array $header;

    public function __construct()
    {
        $apiUrl = $_ENV['API_URL'] ?? null;
        $apiKey = $_ENV['API_KEY'] ?? null;
        $apiBasicAuth = $_ENV['API_BASIC_AUTH'] ?: null;
        $appEnv = $_ENV['APP_ENV'] ?? 'dev';

        if (!$apiUrl || !$apiKey) {
            die("❌ Error: One or more environment variables are missing.\n");
        }

        $projectRoot = getcwd();
        $mappingFile = dirname(__DIR__) . '/config/mappings' .
            ($appEnv === 'production' || $appEnv === 'staging' ? '.production' : '') . '.yaml';
        $this->config = Yaml::parseFile($projectRoot . '/config/production/config.yaml');
        $this->indexConfig = Yaml::parseFile($projectRoot . '/config/_default/indexer.yaml');
        $this->mapping = Yaml::parseFile($mappingFile);

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
            $websiteId = $this->config['osuny']['website']['id'];
            $indexMappings = $this->mapping['index_mappings'] ?? [];

            $indexName = null;

            foreach ($indexMappings as $candidateIndexName => $websiteIds) {
                $websiteIds = is_array($websiteIds) ? $websiteIds : [$websiteIds];

                if (in_array($websiteId, $websiteIds, true)) {
                    $indexName = $candidateIndexName;
                    break;
                }
            }

            if ($indexName === null) {
                throw new \LogicException(
                    'No index mapping found for website id: ' . var_export($websiteId, true)
                );
            }

            $response = $this->client->post('/api/v1/search/index/' . $indexName . '/' . $websiteId, [
                RequestOptions::HEADERS => $this->header,
                RequestOptions::BODY => json_encode($documents),
            ]);

            echo "✅ Success: code {$response->getStatusCode()} ! Total of {$totalDocs} documents indexed\n";

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
        $excludeDirs = $this->indexConfig['exclude_dirs'] ?? [];
        $configTaxonomies = $this->indexConfig['taxonomies'] ?? [];
        $hasThematic = $this->indexConfig['has_thematic'] ?? false;

        foreach ($this->indexConfig['content_dirs'] as $contentDir) {
            $data = $this->getData($projectRoot . $contentDir);

            /** @var Document $item */
            foreach ($data as $key => $item) {
                if (!$this->str_contains_any($key, $excludeDirs)) {
                    $search = $item->matter('search');
                    $taxonomies = $item->matter('taxonomies');

                    $document = [
                        'sourceUrl' => $this->config['baseURL'] . $search['url'],
                        'title' => $search['title'],
                        'identifier' => $search['about_id'],
                        'summary' => strip_tags($search['summary']),
                        'body' => strip_tags($search['body']),
                    ];

                    if (!empty($taxonomies)) {
                        foreach ($configTaxonomies as $taxonomyNeeded) {
                            foreach ($taxonomies as $taxonomy) {
                                if (!empty($taxonomy['categories']) && $taxonomy['name'] === $taxonomyNeeded['name']) {
                                    $document[$taxonomyNeeded['field_name']] = ucfirst($taxonomy['categories'][0]['name']);
                                }
                            }
                        }
                    }

                    if ($hasThematic && isset($this->mapping['thematic_mappings'][$this->config['osuny']['website']['id']])) {
                        $document['thematic'] = $this->mapping['thematic_mappings'][$this->config['osuny']['website']['id']]['name'];
                    }

                    $documents[] = $document;
                    echo "✅ Indexing document: {$search['title']} ({$search['about_id']})\n";
                } else {
                    echo "⚠️ Document " . $key . " was ignored by configuration.\n";
                }
            }
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

                    $content[$path] = $data;
                }
            }
        }

        return $content;
    }

    public function str_contains_any(string $haystack, array $needles): bool
    {
        return array_reduce($needles, fn($a, $n) => $a || str_contains($haystack, $n), false);
    }
}

