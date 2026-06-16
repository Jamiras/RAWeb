<?php

use App\Models\Game;
use App\Models\System;

authenticateFromCookie($user, $permissions, $userDetails);

$consoleID = requestInputSanitized('c', 0, 'integer');
$filterID = requestInputSanitized('f', 0, 'integer');

// only show active consoles
$query = System::query();
if ($consoleID !== -1) {
    $query->where('active', 1);
}
$consoleList = $query
    ->orderBy('name')
    ->get(['ID', 'Name'])
    ->keyBy('ID')
    ->map(fn ($system) => $system['Name']);

$query = Game::query()->with('system');

switch ($filterID)
{
    case 0:
        $query->where(function($query2) {
            $query2->where('image_icon_asset_path', '/Images/000001.png')
                ->orWhere('image_title_asset_path', '/Images/000002.png')
                ->orWhere('image_ingame_asset_path', '/Images/000002.png')
                ->orWhere('image_box_art_asset_path', '/Images/000002.png');
        });
        break;

    case 1:
        $query->where('achievements_published', 0);
        break;
}

if ($consoleID > 0) {
    $query->where('system_id', $consoleID);
} else {
    $query->where('system_id', '<', 100);
    if ($consoleID === 0) {
        $query->whereRelation('system', 'active', 1);
    }
}

$gamesList = $query->inRandomOrder()->limit(10)->get()->sortBy('sort_title');

$title = match($filterID)
{
    0 => "Games Missing Media",
    1 => "Games Missing Achievements",
}

?>
<x-app-layout
    pageTitle="{{ $title }}"
    pageDescription="{{ $title }}"
>
    <div>
        <h2 class="flex gap-x-2 items-center $headingSizeClassName">
            <img src="<?= getSystemIconUrl(0) ?>" alt="Console icon" width="32" height="32">
            <span>Games Missing Media</span>
        </h2>
        <div class='w-full flex flex-col sm:flex-row sm:items-center lg:items-start gap-2 justify-between'>
            <div class='flex items-center gap-x-2'>
                <p>Show:</p>
                <select class='w-full sm:w-auto' onchange='window.location = "/test/missingImages.php?f={{ $filterID }}&c=" + this.options[this.selectedIndex].value'>
                    @if ($consoleID === 0)
                        <option value='0' selected>Active consoles</option>
                    @else
                        <option value='0'>Active consoles</option>
                    @endif
                    @if ($consoleID === -1)
                        <option value='-1' selected>All consoles</option>
                    @else
                        <option value='-1'>All consoles</option>
                    @endif
                    @foreach ($consoleList as $nextConsoleID => $nextConsoleName)
                        @if ($nextConsoleID == $consoleID)
                            <option value='{{ $nextConsoleID }}' selected>{{ $nextConsoleName }}</option>
                        @else
                            <option value='{{ $nextConsoleID }}'>{{ $nextConsoleName }}</option>
                        @endif
                    @endforeach
                </select>
                <p>Filter:</p>
                <select class='w-full sm:w-auto' onchange='window.location = "/test/missingImages.php?c={{ $consoleID }}&f=" + this.options[this.selectedIndex].value'>
                    @if ($filterID == 0)
                        <option value='0' selected>Missing Media</option>
                    @else
                        <option value='0'>Missing Media</option>
                    @endif
                    @if ($filterID == 1)
                        <option value='1' selected>Missing Achievements</option>
                    @else
                        <option value='1'>Missing Achievements</option>
                    @endif
                </select>
            </div>

            <div><button type='button' class='btn' onClick='window.location.reload()'>Refresh</button></div>
        </div>

        <br/>

        <table>
        @foreach ($gamesList as $gameEntry)
            <tr><td class="py-2">
            @php
                $content = Blade::render('
                    <x-game.multiline-avatar
                        :gameId="$gameId"
                        :gameTitle="$gameTitle"
                        :gameImageIcon="$gameImageIcon"
                        :consoleName="$consoleName"
                    />
                ', [
                    'gameId' => $gameEntry->id,
                    'gameTitle' => $gameEntry->title,
                    'gameImageIcon' => $gameEntry->image_icon_asset_path,
                    'consoleName' => $gameEntry->system->name,
                ]);

                echo str_replace('http://localhost:64000/', 'https://retroachievements.org/', $content);
            @endphp
            </td>
            <td>{{ $gameEntry->genre }}</td>
            </tr>
        @endforeach
        </table>

        <br />
    </div>
</x-app-layout>
