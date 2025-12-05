<?php

namespace Addons\TradingManagement\Modules\Marketplace\Services;

use Addons\TradingManagement\Modules\Marketplace\Models\{BotTemplate, SignalSourceTemplate, CompleteBot};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class MarketplaceService
{
    public function browseBotTemplates(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = BotTemplate::query()->public()->with(['backtest', 'user']);

        return $this->applyFilters($query, $filters)->paginate($perPage);
    }

    public function browseSignalSources(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = SignalSourceTemplate::query()->public()->with('user');

        return $this->applyFilters($query, $filters)->paginate($perPage);
    }

    public function browseCompleteBots(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = CompleteBot::query()->public()->with(['backtest', 'user']);

        return $this->applyFilters($query, $filters)->paginate($perPage);
    }

    public function search(string $type, string $keyword, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $model = $this->getModelClass($type);
        $query = $model::query()->public()
            ->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });

        return $this->applyFilters($query, $filters)->paginate($perPage);
    }

    public function getFeatured(string $type = 'all', int $limit = 6): array
    {
        $featured = [];

        if ($type === 'all' || $type === 'bot') {
            $featured['bots'] = BotTemplate::public()->featured()->limit($limit)->get();
        }
        if ($type === 'all' || $type === 'signal') {
            $featured['signals'] = SignalSourceTemplate::public()->featured()->limit($limit)->get();
        }
        if ($type === 'all' || $type === 'complete') {
            $featured['complete'] = CompleteBot::public()->featured()->limit($limit)->get();
        }

        return $featured;
    }

    public function getPopular(string $type, int $limit = 10)
    {
        return $this->getModelClass($type)::query()
            ->public()
            ->popular()
            ->limit($limit)
            ->get();
    }

    public function getTopRated(string $type, int $limit = 10)
    {
        return $this->getModelClass($type)::query()
            ->public()
            ->topRated()
            ->limit($limit)
            ->get();
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['source_type'])) {
            $query->where('source_type', $filters['source_type']);
        }

        if (isset($filters['price'])) {
            if ($filters['price'] === 'free') {
                $query->where('price', 0);
            } elseif ($filters['price'] === 'paid') {
                $query->where('price', '>', 0);
            } elseif (is_array($filters['price'])) {
                $query->whereBetween('price', $filters['price']);
            }
        }

        if (!empty($filters['min_rating'])) {
            $query->where('avg_rating', '>=', $filters['min_rating']);
        }

        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'popular':
                    $query->orderBy('downloads_count', 'desc');
                    break;
                case 'rating':
                    $query->orderBy('avg_rating', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'price_low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('price', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    protected function getModelClass(string $type): string
    {
        return match($type) {
            'bot' => BotTemplate::class,
            'signal' => SignalSourceTemplate::class,
            'complete' => CompleteBot::class,
            default => BotTemplate::class,
        };
    }
}


