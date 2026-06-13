<?php

declare(strict_types=1);

/* PLAYER_CONFIG + player scripts.
   Context from the including view (all optional):
     $playerPlaylist / $playlist — array of tracks for the player
     $initialIndex               — int, start index (default 0)
     $playerFetchUrl             — string, fetch playlist from API when empty
     $likedTrackIds              — int[] of liked track ids (from controller)
     $userPlaylists / $playlists — user's playlists for the "add to playlist" menu
     $user                       — current user array or null */

$pcPlaylist = is_array($playerPlaylist ?? null)
    ? $playerPlaylist
    : (is_array($playlist ?? null) ? $playlist : []);
$pcLiked = is_array($likedTrackIds ?? null) ? array_values(array_map('intval', $likedTrackIds)) : [];
$pcIndex = (int) ($initialIndex ?? 0);
$pcFetch = isset($playerFetchUrl) && is_string($playerFetchUrl) ? $playerFetchUrl : null;
$pcAuth  = isset($user) && is_array($user);

$pcRawPlaylists = is_array($userPlaylists ?? null)
    ? $userPlaylists
    : (is_array($playlists ?? null) ? $playlists : []);
$pcUserPlaylists = [];
foreach ($pcRawPlaylists as $pl) {
    if (is_array($pl) && isset($pl['id'], $pl['title'])) {
        $pcUserPlaylists[] = ['id' => (int) $pl['id'], 'title' => (string) $pl['title']];
    }
}
?>
<?php /* data-turbo-permanent: Turbo Drive keeps this exact node alive across
         page visits — playback survives navigation. Must share the same id
         on every page of the listening surface. */ ?>
<audio id="playerAudio" preload="none" data-player-audio data-turbo-permanent></audio>
<script src="/assets/js/vendor/turbo.umd.js" data-turbo-eval="false"></script>
<script data-turbo-eval="false">
/* Forms (logout, uploads) stay native — Turbo drives links only */
if (window.Turbo) { window.Turbo.setFormMode('off'); }
</script>
<script>
window.PLAYER_CONFIG = {
    csrfToken:     '<?= \App\Core\Csrf::token() ?>',
    playlist:      <?= json_encode($pcPlaylist, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>,
    initialIndex:  <?= $pcIndex ?>,
    likedTrackIds: <?= json_encode($pcLiked) ?>,
    userPlaylists: <?= json_encode($pcUserPlaylists, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
    isAuth:        <?= $pcAuth ? 'true' : 'false' ?><?= $pcFetch !== null ? ",\n    fetchUrl:      '" . htmlspecialchars($pcFetch, ENT_QUOTES, 'UTF-8') . "'" : '' ?>
};
</script>
<script src="/assets/js/player.js"></script>
<script src="/assets/js/mobile-player.js"></script>
