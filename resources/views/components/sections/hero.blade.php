<section class="container mx-auto">
    <div class="hero py-20">
        <div class="hero-content text-center">
            <div class="space-y-8">
                <div
                    class="max-w-3xl mx-auto flex justify-center gap-x-4 max-sm:hidden"
                    x-init="change"
                    x-data="{
                        'types': ['podcast', 'youtube', 'article'],
                        'currentTypeIndex': 0,
                        change() {
                            setInterval(() => {
                                this.currentTypeIndex >= 2 ? this.currentTypeIndex = 0 : this.currentTypeIndex++;
                            }, 2000);
                        }
                    }"
                >
                    <div class="flex items-center gap-x-1 rounded-btn w-fit py-2 px-3 border border-primary text-primary text-xs font-semibold">
                        <x-heroicon-s-microphone
                            class="size-4"
                            x-bind:class="{ 'animate-pulse': types[currentTypeIndex] === 'podcast' }"
                        />
                        <span>
                            Listen Podcasts
                        </span>
                    </div>
                    <div class="flex items-center gap-x-1 rounded-btn w-fit py-2 px-3 border border-primary text-primary text-xs font-semibold">
                        <x-heroicon-s-video-camera
                            class="size-4"
                            x-bind:class="{ 'animate-pulse': types[currentTypeIndex] === 'youtube' }"
                        />
                        <span>
                            Watch YouTube
                        </span>
                    </div>
                    <div class="flex items-center gap-x-1 rounded-btn w-fit py-2 px-3 border border-primary text-primary text-xs font-semibold">
                        <x-heroicon-s-pencil-square
                            class="size-4"
                            x-bind:class="{ 'animate-pulse': types[currentTypeIndex] === 'article' }"
                        />
                        <span>
                            Read Articles
                        </span>
                    </div>
                </div>
                <h1 class="max-w-4xl sm:text-6xl text-5xl font-bold !mt-4 dark:text-primary">
                    Stay informed. Stay ahead. Laravel news all in one place.
                </h1>
                <h2 class="max-w-3xl mx-auto">
                    Stay on top of the latest news, updates, and trends in the Laravel ecosystem with our curated content
                    from your favorite and most trusted blogs, YouTube channels, and podcasts, all presented in a simple, beautiful design.
                </h2>
                <div class="flex gap-x-2 justify-center">
                    <a
                        wire:navigate
                        class="btn lg:btn-lg btn-outline text-primary hover:bg-primary hover:border-primary"
                        href="{{ route('materials.index') }}"
                    >
                        <x-heroicon-o-newspaper class="inline size-8" />
                        Feed
                    </a>
                    <a
                        wire:navigate
                        class="btn lg:btn-lg bg-primary font-bold text-white border-none hover:bg-primary hover:brightness-90"
                        href="{{ route('register') }}"
                    >
                        <span class="max-sm:hidden">Falling behind?</span> Join now, it's free
                        <x-heroicon-o-arrow-long-right class="inline size-8" />
                    </a>
                </div>
            </div>
        </div>
    </div>

</section>
