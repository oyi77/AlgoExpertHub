@php
    $theme = Config::config()->theme ?? 'default';
    $recentBlogs = \App\Models\Content::where('theme', $theme)
        ->where('name', 'blog')
        ->where('type', 'iteratable')
        ->latest()
        ->limit(3)
        ->get();
@endphp

<section class="blog-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Latest Insights</span>
            <h2 class="section-title">Trading Insights & Market Analysis</h2>
            <p class="section-description">Stay informed with expert analysis, trading strategies, and market updates from our team</p>
        </div>

        @if($recentBlogs->count() > 0)
            <div class="blog-grid">
                @foreach($recentBlogs as $blog)
                    @php
                        $blogContent = is_string($blog->content) ? json_decode($blog->content, true) : $blog->content;
                        $title = $blogContent['page_title'] ?? $blogContent['title'] ?? 'Untitled';
                        $description = $blogContent['short_description'] ?? $blogContent['description'] ?? 'Read more about this topic...';
                        $image = $blogContent['image_one'] ?? $blogContent['image'] ?? null;
                        $date = $blog->created_at ?? now();
                    @endphp
                    <article class="blog-card">
                        <div class="blog-image">
                            @if($image)
                                <img src="{{ asset('storage/' . $image) }}" alt="{{ $title }}" loading="lazy">
                            @else
                                <div class="image-placeholder">
                                    <i class="las la-newspaper"></i>
                                </div>
                            @endif
                            <div class="blog-category">Trading Tips</div>
                        </div>
                        <div class="blog-content">
                            <div class="blog-meta">
                                <span class="blog-date">
                                    <i class="las la-calendar"></i>
                                    {{ $date->format('M d, Y') }}
                                </span>
                                <span class="blog-read-time">
                                    <i class="las la-clock"></i>
                                    5 min read
                                </span>
                            </div>
                            <h3 class="blog-title">
                                <a href="{{ route('blog.details', ['id' => $blog->id, 'slug' => Str::slug($title)]) }}">{{ $title }}</a>
                            </h3>
                            <p class="blog-excerpt">{{ Str::limit($description, 120) }}</p>
                            <a href="{{ route('blog.details', ['id' => $blog->id, 'slug' => Str::slug($title)]) }}" class="blog-link">
                                Read More <i class="las la-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="blog-grid">
                <article class="blog-card">
                    <div class="blog-image">
                        <div class="image-placeholder">
                            <i class="las la-chart-line"></i>
                        </div>
                        <div class="blog-category">Market Analysis</div>
                    </div>
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span class="blog-date">
                                <i class="las la-calendar"></i>
                                {{ now()->format('M d, Y') }}
                            </span>
                            <span class="blog-read-time">
                                <i class="las la-clock"></i>
                                5 min read
                            </span>
                        </div>
                        <h3 class="blog-title">
                            <a href="#">Understanding Market Volatility in 2024</a>
                        </h3>
                        <p class="blog-excerpt">Explore the key factors driving market volatility this year and how to adapt your trading strategy accordingly.</p>
                        <a href="#" class="blog-link">
                            Read More <i class="las la-arrow-right"></i>
                        </a>
                    </div>
                </article>

                <article class="blog-card">
                    <div class="blog-image">
                        <div class="image-placeholder">
                            <i class="las la-robot"></i>
                        </div>
                        <div class="blog-category">Trading Strategies</div>
                    </div>
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span class="blog-date">
                                <i class="las la-calendar"></i>
                                {{ now()->subDays(2)->format('M d, Y') }}
                            </span>
                            <span class="blog-read-time">
                                <i class="las la-clock"></i>
                                7 min read
                            </span>
                        </div>
                        <h3 class="blog-title">
                            <a href="#">Automated Trading: A Beginner's Guide</a>
                        </h3>
                        <p class="blog-excerpt">Learn how automated trading bots can help you execute trades 24/7 and maximize your trading opportunities.</p>
                        <a href="#" class="blog-link">
                            Read More <i class="las la-arrow-right"></i>
                        </a>
                    </div>
                </article>

                <article class="blog-card">
                    <div class="blog-image">
                        <div class="image-placeholder">
                            <i class="las la-shield-alt"></i>
                        </div>
                        <div class="blog-category">Risk Management</div>
                    </div>
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span class="blog-date">
                                <i class="las la-calendar"></i>
                                {{ now()->subDays(5)->format('M d, Y') }}
                            </span>
                            <span class="blog-read-time">
                                <i class="las la-clock"></i>
                                6 min read
                            </span>
                        </div>
                        <h3 class="blog-title">
                            <a href="#">Essential Risk Management Techniques</a>
                        </h3>
                        <p class="blog-excerpt">Discover proven risk management strategies to protect your capital and improve your long-term trading performance.</p>
                        <a href="#" class="blog-link">
                            Read More <i class="las la-arrow-right"></i>
                        </a>
                    </div>
                </article>
            </div>
        @endif

        <div class="blog-cta">
            <a href="#" class="btn-view-all">
                View All Articles <i class="las la-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

