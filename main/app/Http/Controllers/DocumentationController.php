<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

class DocumentationController extends Controller
{
    protected $converter;
    protected $wikiPath;
    protected $docsPath;
    
    public function __construct()
    {
        // Configure markdown converter with GitHub Flavored Markdown
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        
        $this->converter = new MarkdownConverter($environment);
        $this->wikiPath = base_path('../.qoder/repowiki/en/content');
        $this->docsPath = base_path('../docs');
    }
    
    /**
     * Show documentation index
     */
    public function index()
    {
        $wikiStructure = $this->getWikiStructure();
        $docsFiles = $this->getDocsFiles();
        
        return view('documentation.index', [
            'wikiStructure' => $wikiStructure,
            'docsFiles' => $docsFiles
        ]);
    }
    
    /**
     * Show wiki document
     */
    public function showWiki(Request $request, $path = null)
    {
        $filePath = $this->wikiPath . '/' . ($path ?? 'Project Overview.md');
        
        // Security: prevent directory traversal
        $realPath = realpath($filePath);
        if (!$realPath || strpos($realPath, realpath($this->wikiPath)) !== 0) {
            abort(404, 'Documentation not found');
        }
        
        if (!File::exists($realPath)) {
            abort(404, 'Documentation not found');
        }
        
        $markdown = File::get($realPath);
        $html = $this->converter->convert($markdown);
        
        $wikiStructure = $this->getWikiStructure();
        $docsFiles = $this->getDocsFiles();
        
        return view('documentation.show', [
            'title' => $this->formatTitle(basename($filePath, '.md')),
            'content' => $html,
            'type' => 'wiki',
            'currentPath' => $path,
            'wikiStructure' => $wikiStructure,
            'docsFiles' => $docsFiles
        ]);
    }
    
    /**
     * Show docs document
     */
    public function showDocs($file)
    {
        $filePath = $this->docsPath . '/' . $file . '.md';
        
        // Security: prevent directory traversal
        $realPath = realpath($filePath);
        if (!$realPath || strpos($realPath, realpath($this->docsPath)) !== 0) {
            abort(404, 'Documentation not found');
        }
        
        if (!File::exists($realPath)) {
            abort(404, 'Documentation not found');
        }
        
        $markdown = File::get($realPath);
        $html = $this->converter->convert($markdown);
        
        $wikiStructure = $this->getWikiStructure();
        $docsFiles = $this->getDocsFiles();
        
        return view('documentation.show', [
            'title' => $this->formatTitle(basename($filePath, '.md')),
            'content' => $html,
            'type' => 'docs',
            'currentPath' => $file,
            'wikiStructure' => $wikiStructure,
            'docsFiles' => $docsFiles
        ]);
    }
    
    /**
     * Search documentation
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
        $results = [];
        
        if (empty($query)) {
            return view('documentation.search', ['results' => [], 'query' => '']);
        }
        
        // Search wiki files
        $wikiFiles = File::allFiles($this->wikiPath);
        foreach ($wikiFiles as $file) {
            if ($file->getExtension() === 'md') {
                $content = File::get($file->getPathname());
                if (stripos($content, $query) !== false) {
                    $relativePath = str_replace($this->wikiPath . '/', '', $file->getPathname());
                    $results[] = [
                        'title' => $this->formatTitle($file->getFilenameWithoutExtension()),
                        'path' => $relativePath,
                        'type' => 'wiki',
                        'excerpt' => $this->getExcerpt($content, $query)
                    ];
                }
            }
        }
        
        // Search docs files
        $docsFiles = File::files($this->docsPath);
        foreach ($docsFiles as $file) {
            if ($file->getExtension() === 'md') {
                $content = File::get($file->getPathname());
                if (stripos($content, $query) !== false) {
                    $results[] = [
                        'title' => $this->formatTitle($file->getFilenameWithoutExtension()),
                        'path' => $file->getFilenameWithoutExtension(),
                        'type' => 'docs',
                        'excerpt' => $this->getExcerpt($content, $query)
                    ];
                }
            }
        }
        
        $wikiStructure = $this->getWikiStructure();
        $docsFilesArray = $this->getDocsFiles();
        
        return view('documentation.search', [
            'results' => $results,
            'query' => $query,
            'wikiStructure' => $wikiStructure,
            'docsFiles' => $docsFilesArray
        ]);
    }
    
    /**
     * Get wiki directory structure
     */
    protected function getWikiStructure()
    {
        $structure = [];
        
        $directories = [
            'Architecture Overview' => 'Architecture Overview',
            'Core Modules' => 'Core Modules',
            'API Reference' => 'API Reference',
            'Configuration' => 'Configuration',
            'Database Schema' => 'Database Schema',
        ];
        
        foreach ($directories as $key => $dir) {
            $dirPath = $this->wikiPath . '/' . $dir;
            if (File::isDirectory($dirPath)) {
                $files = File::files($dirPath);
                $structure[$key] = [];
                foreach ($files as $file) {
                    if ($file->getExtension() === 'md') {
                        $relativePath = str_replace($this->wikiPath . '/', '', $file->getPathname());
                        $structure[$key][] = [
                            'title' => $this->formatTitle($file->getFilenameWithoutExtension()),
                            'path' => $relativePath
                        ];
                    }
                }
            }
        }
        
        // Add root level files
        $rootFiles = File::files($this->wikiPath);
        $structure['Getting Started'] = [];
        foreach ($rootFiles as $file) {
            if ($file->getExtension() === 'md') {
                $relativePath = str_replace($this->wikiPath . '/', '', $file->getPathname());
                $structure['Getting Started'][] = [
                    'title' => $this->formatTitle($file->getFilenameWithoutExtension()),
                    'path' => $relativePath
                ];
            }
        }
        
        return $structure;
    }
    
    /**
     * Get docs files list
     */
    protected function getDocsFiles()
    {
        $files = [];
        $docsFiles = File::files($this->docsPath);
        
        foreach ($docsFiles as $file) {
            if ($file->getExtension() === 'md') {
                $files[] = [
                    'title' => $this->formatTitle($file->getFilenameWithoutExtension()),
                    'path' => $file->getFilenameWithoutExtension()
                ];
            }
        }
        
        return $files;
    }
    
    /**
     * Format title from filename
     */
    protected function formatTitle($filename)
    {
        return str_replace(['-', '_'], ' ', $filename);
    }
    
    /**
     * Get excerpt around search query
     */
    protected function getExcerpt($content, $query, $length = 200)
    {
        $pos = stripos($content, $query);
        if ($pos === false) {
            return substr(strip_tags($content), 0, $length) . '...';
        }
        
        $start = max(0, $pos - 100);
        $excerpt = substr($content, $start, $length);
        $excerpt = strip_tags($excerpt);
        
        // Highlight query
        $excerpt = str_ireplace($query, '<mark>' . $query . '</mark>', $excerpt);
        
        return '...' . $excerpt . '...';
    }
}

