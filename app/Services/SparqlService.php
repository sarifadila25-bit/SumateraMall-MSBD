<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

class SparqlService
{
    protected string $queryEndpoint;

    protected string $updateEndpoint;

    public function __construct()
    {
        $this->queryEndpoint = (string) config('services.fuseki.query_url');
        $this->updateEndpoint = (string) config('services.fuseki.update_url');
    }

    /**
     * @return array<int, array<string, string>>
     *
     * @throws ConnectionException
     */
    public function query(string $sparql, int $cacheMinutes = 5, int $timeoutSeconds = 30): array
    {
        $fetch = function () use ($sparql, $timeoutSeconds): array {
            $response = Http::timeout(max(1, $timeoutSeconds))
                ->accept('application/sparql-results+json')
                ->get($this->queryEndpoint, ['query' => $sparql])
                ->throw();

            $data = $response->json();

            if (! is_array($data) || ! isset($data['results']['bindings']) || ! is_array($data['results']['bindings'])) {
                throw new RuntimeException('Fuseki returned an invalid SPARQL results document.');
            }

            return $this->formatResults($data);
        };

        if ($cacheMinutes <= 0) {
            return $fetch();
        }

        $cacheKey = 'sparql_'.md5($sparql);

        return Cache::remember($cacheKey, $cacheMinutes * 60, $fetch);
    }

    /**
     * @throws ConnectionException
     */
    public function update(string $sparql): bool
    {
        Http::timeout(30)
            ->withBody($sparql, 'application/sparql-update')
            ->post($this->updateEndpoint)
            ->throw();

        return true;
    }

    /**
     * @return array<int, array<string, string>>
     *
     * @throws ConnectionException
     */
    public function queryExternal(string $sparql, string $endpoint = 'https://dbpedia.org/sparql'): array
    {
        $data = Http::timeout(15)
            ->accept('application/sparql-results+json')
            ->get($endpoint, ['query' => $sparql, 'format' => 'json'])
            ->throw()
            ->json();

        if (! is_array($data) || ! isset($data['results']['bindings']) || ! is_array($data['results']['bindings'])) {
            throw new RuntimeException('The external endpoint returned an invalid SPARQL results document.');
        }

        return $this->formatResults($data);
    }

    public function internalResource(string $uri, string $type): string
    {
        $prefix = 'http://sumateramall.org/resource/'.$type.'/';
        $slug = str_starts_with($uri, $prefix) ? substr($uri, strlen($prefix)) : '';

        if (! in_array($type, ['Mall', 'City', 'Province', 'Category'], true)
            || ! preg_match('/\A[a-z0-9_]+\z/', $slug)) {
            throw new RuntimeException('Invalid SumateraMall resource URI.');
        }

        return '<'.$prefix.$slug.'>';
    }

    /**
     * Encode a value as a quoted SPARQL string literal.
     *
     * @throws JsonException
     */
    public function literal(string $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, string>>
     */
    protected function formatResults(array $data): array
    {
        $results = [];

        foreach ($data['results']['bindings'] as $row) {
            $formatted = [];

            foreach ($row as $key => $value) {
                if (isset($value['value']) && is_string($value['value'])) {
                    $formatted[$key] = $value['value'];
                }
            }

            $results[] = $formatted;
        }

        return $results;
    }
}
