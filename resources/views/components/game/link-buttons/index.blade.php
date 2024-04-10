@props([
    'allowedLinks' => [
        'code-notes',
        'forum-topic',
        'game-files',
        'guide',
        // 'manage-hashes', // supported, but is not a default
        'set-requestors',
        'suggested-games',
        'tickets',
        'view-hashes',
    ],
    'isViewingOfficial' => false,
    'variant' => 'stacked', // 'stacked' | 'row'
    'game' => null, // Game
])

<?php

use App\Community\Enums\TicketFilters;
use App\Enums\Permissions;
use App\Models\ForumTopic;
use App\Models\Ticket;
use App\Platform\Enums\AchievementFlag;
use Illuminate\Support\Facades\Auth;

$user = Auth::user();

$canSeeOpenTickets = in_array('tickets', $allowedLinks) && $user?->can('viewAny', Ticket::class);
if ($canSeeOpenTickets) {
    $numOpenTickets = countOpenTickets(
        !$isViewingOfficial,
        TicketFilters::Default,
        null,
        null,
        null,
        $game->id,
    );
}

$ticketManagerUrlParams = [
    'g' => $game->id,
    'f' => $isViewingOfficial ? null : AchievementFlag::Unofficial,
];
$ticketManagerUrl = url('/ticketmanager.php') . '?' . http_build_query($ticketManagerUrlParams);
?>

<ul class="flex @if ($variant === 'stacked') flex-col @endif gap-2">
    @if (in_array('forum-topic', $allowedLinks))
        @if ($game->ForumTopicID)
            <x-game.link-buttons.game-link-button
                icon="💬"
                href="{{ '/viewtopic.php?t=' . $game->ForumTopicID }}"
            >
                Official Forum Topic
            </x-game.link-buttons.game-link-button>
        @else
            @can('createForumTopic', App\Models\Game::class)
                <x-game.link-buttons.create-forum-topic-button :gameId="$game->id" />
            @endcan
        @endif
    @endif

    @if (in_array('guide', $allowedLinks) && $game->GuideURL)
        <x-game.link-buttons.game-link-button
            icon="📖"
            href="{{ $game->GuideURL }}"
        >
            Guide
        </x-game.link-buttons.game-link-button>
    @endif

    @if (in_array('game-files', $allowedLinks))
        @can('viewAny', App\Models\GameHash::class)
            <x-game.link-buttons.game-link-button
                icon="💾"
                :href="route('game.hash', ['game' => $game])"
            >
                Supported Game Files
            </x-game.link-buttons.game-link-button>
        @endcan
    @endif

    @if (in_array('manage-hashes', $allowedLinks))
        @can('manage', App\Models\GameHash::class)
            <x-game.link-buttons.game-link-button
                icon="💾"
                :href="route('game.hash.manage', ['game' => $game])"
            >
                Manage Hashes
            </x-game.link-buttons.game-link-button>
        @endcan
    @endif

    @if (in_array('code-notes', $allowedLinks))
        @can('viewAny', App\Models\MemoryNote::class)
            <x-game.link-buttons.game-link-button
                icon="📑"
                href="{{ '/codenotes.php?g=' . $game->id }}"
            >
                Code Notes
            </x-game.link-buttons.game-link-button>
        @endcan
    @endif

    @if ($canSeeOpenTickets)
        <x-game.link-buttons.game-link-button
            icon="🎫"
            href="{!! $ticketManagerUrl !!}"
        >
            Open @if (!$isViewingOfficial) Unofficial @endif Tickets ({{ $numOpenTickets }})
        </x-game.link-buttons.game-link-button>
    @endif

    @if (in_array('set-requestors', $allowedLinks) && $game->achievements->isEmpty())
        @can('viewAny', App\Models\Game::class)
            <x-game.link-buttons.game-link-button
                icon="📜"
                href="{{ '/setRequestors.php?g=' . $game->id }}"
            >
                Set Requestors
            </x-game.link-buttons.game-link-button>
        @endcan
    @endif

    @if (in_array('suggested-games', $allowedLinks) && $user)
        <x-game.link-buttons.game-link-button
            icon="🕹️"
            href="{{ route('game.suggest', $game->id) }}"
        >
            Find Something Similar to Play
        </x-game.link-buttons.game-link-button>
    @endif
</ul>
