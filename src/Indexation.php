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
        $this->config = Yaml::parseFile('../config/production/config.yaml');
        $this->client = new Client([
            'base_uri' => $_ENV['API_URL']
        ]);

        $this->header = [
            'Content-Type' => 'application/json',
            'X-api-key' => $_ENV['API_KEY'],
        ];
    }

    public function run()
    {
        $documents = $this->getDocuments();

        $this->client->post('search/index/' . $this->config['osuny']['website']['id'], [
            RequestOptions::HEADERS => $this->header,
            RequestOptions::BODY => json_encode($documents),
        ]);
    }

    public function getDocuments()
    {
        $documents = [];
        $data = $this->getData($_ENV['CONTENT_DIR']);
        $config = Yaml::parseFile('../config/production/config.yaml');

        /** @var Document $item */
        foreach ($data as $item) {
            $search = $item->matter('search');
            $taxonomies = $item->matter('taxonomies');
            $category = '';
            if (!empty($taxonomies)) {
                foreach ($taxonomies as $taxonomy) {
                    if (!empty($taxonomy['categories']) && $taxonomy['name'] === 'Types de contenus') {
                        $category = $taxonomy['categories'][0]['name'];
                    }
                }
            }

            $documents[] = [
                'sourceId' => $search['id'],
                'sourceUrl' => $config['baseURL'] . $search['url'],
                'title' => $search['title'],
                'identifier' => $search['id'],
                'summary' => $search['summary'],
                'body' => $search['body'],
                'category' => $category,
            ];
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

