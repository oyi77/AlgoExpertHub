<?php

namespace App\Adapters;

use App\Models\ChannelSource;
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
            $selector = $this->getConfig('selector'); // CSS selector or XPath
            $selectorType = $this->getConfig('selector_type', 'css'); // 'css' or 'xpath'

            // Fetch HTML content
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

            // Parse HTML
            if ($selectorType === 'xpath') {
                $content = $this->extractWithXPath($html, $selector);
            } else {
                $content = $this->extractWithCssSelector($html, $selector);
            }

            if ($content) {
                // Check if content is new (compare with last processed)
                $lastContentHash = $this->getConfig('last_content_hash');
                $currentHash = hash('sha256', $content);

                if ($lastContentHash !== $currentHash) {
                    $messages->push([
                        'text' => $content,
                        'url' => $url,
                        'hash' => $currentHash,
                        'timestamp' => now()->timestamp,
                    ]);

                    // Update last content hash
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
            // Simple DOM parsing (for basic CSS selectors)
            // For complex scraping, consider using Goutte
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            
            // Convert CSS selector to XPath (simplified)
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
     * This is a simplified version - for complex selectors, use a proper library.
     *
     * @param string $selector
     * @return string
     */
    protected function cssToXPath(string $selector): string
    {
        // Very basic CSS to XPath conversion
        // For production, consider using a library like Symfony CssSelector
        
        // Handle class selector
        if (strpos($selector, '.') === 0) {
            $className = substr($selector, 1);
            return "//*[@class='{$className}']";
        }
        
        // Handle ID selector
        if (strpos($selector, '#') === 0) {
            $id = substr($selector, 1);
            return "//*[@id='{$id}']";
        }
        
        // Handle tag selector
        return "//{$selector}";
    }

    /**
     * Check robots.txt before scraping.
     *
     * @param string $url
     * @return bool
     */
    protected function checkRobotsTxt(string $url): bool
    {
        try {
            $parsedUrl = parse_url($url);
            $robotsUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/robots.txt';
            
            $response = Http::timeout(5)->get($robotsUrl);
            
            if ($response->successful()) {
                $robotsContent = $response->body();
                // Simple check - in production, use proper robots.txt parser
                if (strpos($robotsContent, 'Disallow: /') !== false) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            // If robots.txt check fails, allow scraping (fail open)
        }

        return true;
    }
}

