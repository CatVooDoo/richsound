<?php

declare(strict_types=1);

/* Full-screen mobile player (Spotify-style sheet).
   Markup only — all state is rendered by mobile-player.js. */
?>
<!-- ── Mobile full-screen player ─────────────────────────────── -->
<div class="mobile-player" id="mobilePlayer" role="dialog" aria-modal="true" aria-label="Полноэкранный плеер" aria-hidden="true">

    <!-- Blurred album art background (set via JS) -->
    <div class="mplayer__bg" data-mplayer-bg></div>
    <!-- Dark gradient overlay on top of blur -->
    <div class="mplayer__overlay"></div>

    <div class="mplayer__content">

        <!-- ── Main view (fills one screen, then queue on scroll) ── -->
        <div class="mplayer__main">

            <!-- Header row -->
            <div class="mplayer__header">
                <button class="mplayer__icon-btn mplayer__close" id="mobilePlayerClose" aria-label="Свернуть">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </button>
                <span class="mplayer__header-label">Сейчас играет</span>
                <button class="mplayer__icon-btn mplayer__more" aria-label="Ещё">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/>
                    </svg>
                </button>
            </div>

            <!-- Cover art -->
            <div class="mplayer__art-wrap">
                <div class="mplayer__art" data-mplayer-cover></div>
            </div>

            <!-- Track info + heart -->
            <div class="mplayer__meta">
                <div class="mplayer__info">
                    <p class="mplayer__title" data-mplayer-title>Трек не выбран</p>
                    <p class="mplayer__artist" data-mplayer-artist>—</p>
                </div>
                <button class="mplayer__icon-btn mplayer__heart" aria-label="В избранное" data-mplayer-heart hidden>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m12 21-1.45-1.32C5.4 15 2 11.86 2 8a5 5 0 0 1 9.08-2.92A5 5 0 0 1 20 8c0 3.86-3.4 7-8.55 11.68z"/>
                    </svg>
                </button>
            </div>

            <!-- Progress bar -->
            <div class="mplayer__progress">
                <div class="mplayer__slider">
                    <div class="mplayer__slider-rail"></div>
                    <div class="mplayer__slider-fill" data-mplayer-fill style="width:0%"></div>
                    <input class="mplayer__range" type="range" min="0" max="100" value="0" step="0.1"
                           data-mplayer-seek aria-label="Позиция воспроизведения">
                </div>
                <div class="mplayer__times">
                    <span data-mplayer-current>0:00</span>
                    <span data-mplayer-duration>0:00</span>
                </div>
            </div>

            <!-- Controls: shuffle · prev · play · next · repeat -->
            <div class="mplayer__controls">
                <button class="mplayer__icon-btn mplayer__mode-btn" aria-label="Перемешать" data-mplayer-shuffle>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/>
                        <polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/>
                    </svg>
                </button>
                <button class="mplayer__icon-btn mplayer__skip" aria-label="Предыдущий" data-mplayer-prev>
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="19 20 9 12 19 4 19 20"/><rect x="5" y="4" width="2" height="16" rx="1"/>
                    </svg>
                </button>
                <button class="mplayer__play-btn" aria-label="Воспроизвести / Пауза" data-mplayer-toggle>
                    <svg class="mplayer__icon-play" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    <svg class="mplayer__icon-pause" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <rect x="6" y="4" width="4" height="16" rx="2"/><rect x="14" y="4" width="4" height="16" rx="2"/>
                    </svg>
                </button>
                <button class="mplayer__icon-btn mplayer__skip" aria-label="Следующий" data-mplayer-next>
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="5 4 15 12 5 20 5 4"/><rect x="17" y="4" width="2" height="16" rx="1"/>
                    </svg>
                </button>
                <button class="mplayer__icon-btn mplayer__mode-btn" aria-label="Повтор" data-mplayer-repeat>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                        <polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                    </svg>
                </button>
            </div>

            <!-- Bottom row: queue button (Spotify-style) -->
            <div class="mplayer__bottom">
                <button class="mplayer__icon-btn mplayer__queue-btn" aria-label="Очередь" data-mplayer-queue-btn>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                </button>
            </div>

        </div><!-- /.mplayer__main -->

        <!-- ── Queue (revealed on scroll, rendered by JS) ──────── -->
        <div class="mplayer__queue">
            <div class="mplayer__queue-header">
                <span class="mplayer__queue-title-label">Далее в очереди</span>
            </div>
            <div class="mplayer__queue-list" data-mplayer-queue-list></div>
        </div>

    </div><!-- /.mplayer__content -->

    <!-- ── Track actions sheet (three-dots menu) ───────────── -->
    <div class="mplayer__menu" data-mplayer-menu hidden>
        <div class="mplayer__menu-backdrop" data-mplayer-menu-close></div>
        <div class="mplayer__menu-sheet" role="menu">
            <div class="mplayer__menu-grip" aria-hidden="true"></div>
            <p class="mplayer__menu-track" data-mplayer-menu-track></p>

            <button class="mplayer__menu-item" type="button" data-mplayer-action="playlist" role="menuitem">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14"></path><path d="M5 12h14"></path>
                </svg>
                Добавить в плейлист
            </button>
            <div class="mplayer__menu-playlists" data-mplayer-menu-playlists hidden></div>

            <button class="mplayer__menu-item" type="button" data-mplayer-action="author" role="menuitem">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>
                </svg>
                К исполнителю
            </button>

            <button class="mplayer__menu-item" type="button" data-mplayer-action="share" role="menuitem">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                </svg>
                Поделиться
            </button>
        </div>
    </div>
</div><!-- /#mobilePlayer -->
