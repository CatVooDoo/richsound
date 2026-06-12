<?php

declare(strict_types=1);

/* Shared desktop player bar / mobile mini player (.dashboard__player).
   Optional context from the including view:
     $initialTrack — array with title/author_name/cover_path/duration to
                     pre-render the bar before JS boots (home page). */

$pbTrack = is_array($initialTrack ?? null) ? $initialTrack : [];

$pbH = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$pbFmt = static function (mixed $s): string {
    $t = max(0, (int) $s);
    return sprintf('%d:%02d', intdiv($t, 60), $t % 60);
};
$pbCoverAttr = static function (array $track) use ($pbH): string {
    $path = trim((string) ($track['cover_path'] ?? $track['coverUrl'] ?? ''));
    if ($path === '') { return ''; }
    if (preg_match('~^https?://~i', $path) !== 1 && !str_starts_with($path, '/')) {
        $path = '/' . ltrim($path, '/');
    }
    return ' style="background-image: linear-gradient(180deg,rgba(0,0,0,.28),rgba(0,0,0,.18)), url(\'' . $pbH($path) . '\');"';
};
?>
    <footer class="dashboard__player" data-player-root>

        <!-- ① Now playing -->
        <div class="player__now">
            <div class="player__art" data-player-cover<?= $pbCoverAttr($pbTrack) ?>></div>
            <div class="player__info">
                <p class="player__title" data-player-title><?= $pbH($pbTrack['title'] ?? 'Трек не выбран') ?></p>
                <p class="player__artist" data-player-artist><?= $pbH($pbTrack['author_name'] ?? $pbTrack['artist'] ?? 'Выбери трек') ?></p>
            </div>
            <button class="player__btn player__btn--heart" type="button" aria-label="В избранное" hidden>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m12 21-1.45-1.32C5.4 15 2 11.86 2 8a5 5 0 0 1 9.08-2.92A5 5 0 0 1 20 8c0 3.86-3.4 7-8.55 11.68z"/>
                </svg>
            </button>
        </div>

        <!-- ② Controls + progress -->
        <div class="player__center">
            <div class="player__controls">
                <button class="player__btn player__btn--shuffle" type="button" title="Перемешать" aria-label="Перемешать">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/>
                        <polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/>
                    </svg>
                </button>
                <button class="player__btn player__btn--prev" type="button" data-player-prev aria-label="Предыдущий трек">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="19 20 9 12 19 4 19 20"/><rect x="5" y="4" width="2" height="16" rx="1"/>
                    </svg>
                </button>
                <button class="player__btn player__btn--play" type="button" data-player-toggle aria-label="Воспроизвести">
                    <svg class="player__icon-play" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    <svg class="player__icon-pause" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <rect x="6" y="4" width="4" height="16" rx="2"/><rect x="14" y="4" width="4" height="16" rx="2"/>
                    </svg>
                </button>
                <button class="player__btn player__btn--next" type="button" data-player-next aria-label="Следующий трек">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="5 4 15 12 5 20 5 4"/><rect x="17" y="4" width="2" height="16" rx="1"/>
                    </svg>
                </button>
                <button class="player__btn player__btn--repeat" type="button" title="Повторить" aria-label="Повторить">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                        <polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                    </svg>
                </button>
            </div>

            <div class="player__bar">
                <span class="player__time" data-player-current-time>0:00</span>
                <div class="player__slider" data-player-slider-progress>
                    <div class="player__slider-rail"></div>
                    <div class="player__slider-fill"></div>
                    <input class="player__range" type="range" min="0" max="100" value="0" step="0.1" data-player-progress aria-label="Позиция воспроизведения">
                </div>
                <span class="player__time" data-player-duration><?= $pbH($pbFmt($pbTrack['duration'] ?? 0)) ?></span>
            </div>
        </div>

        <!-- ③ Volume + queue -->
        <div class="player__side">
            <button class="player__btn" type="button" aria-label="Громкость">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                    <path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
                </svg>
            </button>
            <div class="player__slider player__slider--vol" data-player-slider-volume>
                <div class="player__slider-rail"></div>
                <div class="player__slider-fill"></div>
                <input class="player__range" type="range" min="0" max="1" step="0.01" value="0.8" data-player-volume aria-label="Громкость">
            </div>
            <button class="player__btn player__btn--queue" type="button" title="Очередь" aria-label="Очередь">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            </button>
        </div>

    </footer>
