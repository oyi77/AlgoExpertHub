<?php

namespace Addons\MultiChannelSignalAddon\App\Adapters;

use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebScrapeAdapter extends BaseChannelAdapter
{
    /**
     * Connect to web scraping source.
     *
     * @param ChannelSource $channelSource
     * @return bool
     */
    public function connect(ChannelSource $channelSource): bool
    {
        try {
            $this->channelSource = $channelSource;
            $this->config = $channelSource->config ?? [];

            // Validate URL
            $url = $this->getConfig('url');
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                $this->logError("Invalid URL configured");
                return false;
            }

            // Try to access URL
            if (!$this->validateUrlAccessibility()) {
                return false;
            }

            $this->connected = true;
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to connect to web scraping source: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch messages from web scraping source.
     *
     * @return Collection
     */
    public function fetchMessages(): Collection
    {
        if (!$this->connected) {
            $this->connect($this->channelSource);
        }

        $messages = collect();

        try {
            $url = $this->getConfig('url');
            $selector = $this->getConfig('selector');
            $selectorType = $this->getConfig('selector_type', 'css');

            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ])
                ->get($url);

            if (!$response->successful()) {
                $this->logError("Failed to fetch URL: HTTP {$response->status()}");
                return $messages;
            }

            $html = $response->body();

            if ($selectorType === 'xpath') {
                $content = $this->extractWithXPath($html, $selector);
            } else {
                $content = $this->extractWithCssSelector($html, $selector);
            }

            if ($content) {
                $lastContentHash = $this->getConfig('last_content_hash');
                $currentHash = hash('sha256', $content);

                if ($lastContentHash !== $currentHash) {
                    $messages->push([
                        'text' => $content,
                        'url' => $url,
                        'hash' => $currentHash,
                        'timestamp' => now()->timestamp,
                    ]);

                    $config = $this->channelSource->config;
                    $config['last_content_hash'] = $currentHash;
                    $this->channelSource->update(['config' => $config]);
                }
            }

        } catch (\Exception $e) {
            $this->logError("Failed to scrape content: " . $e->getMessage());
        }

        return $messages;
    }

    /**
     * Validate channel configuration.
     *
     * @param array $config
     * @return bool
     */
    public function validateConfig(array $config): bool
    {
        if (empty($config['url']) || !filter_var($config['url'], FILTER_VALIDATE_URL)) {
            return false;
        }

        if (empty($config['selector'])) {
            return false;
        }

        return true;
    }

    /**
     * Get the adapter type.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'web_scrape';
    }

    /**
     * Validate URL accessibility.
     *
     * @return bool
     */
    protected function validateUrlAccessibility(): bool
    {
        try {
            $url = $this->getConfig('url');
            $response = Http::timeout(10)->head($url);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extract content using CSS selector.
     *
     * @param string $html
     * @param string $selector
     * @return string|null
     */
    protected function extractWithCssSelector(string $html, string $selector): ?string
    {
        try {
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            
            $xpathQuery = $this->cssToXPath($selector);
            $nodes = $xpath->query($xpathQuery);
            
            if ($nodes && $nodes->length > 0) {
                $content = [];
                foreach ($nodes as $node) {
                    $content[] = trim($node->textContent);
                }
                return implode("\n", $content);
            }
        } catch (\Exception $e) {
            Log::error("CSS selector extraction failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Extract content using XPath.
     *
     * @param string $html
     * @param string $xpathQuery
     * @return string|null
     */
    protected function extractWithXPath(string $html, string $xpathQuery): ?string
    {
        try {
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            
            $nodes = $xpath->query($xpathQuery);
            
            if ($nodes && $nodes->length > 0) {
                $content = [];
                foreach ($nodes as $node) {
                    $content[] = trim($node->textContent);
                }
                return implode("\n", $content);
            }
        } catch (\Exception $e) {
            Log::error("XPath extraction failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Convert simple CSS selector to XPath.
     *
     * @param string $selector
     * @return string
     */
    protected function cssToXPath(string $selector): string
    {
        if (strpos($selector, '.') === 0) {
            $className = substr($selector, 1);
            return "//*[@class='{$className}']";
        }
        
        if (strpos($selector, '#') === 0) {
            $id = substr($selector, 1);
            return "//*[@id='{$id}']";
        }
        
        return "//{$selector}";
    }
}
