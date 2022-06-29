<?php

use RA\ClaimFilters;
use RA\ClaimSorting;
use RA\ClaimSpecial;
use RA\ClaimType;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$userPage = requestInputSanitized('ID');
if ($userPage == null || mb_strlen($userPage) == 0 || ctype_alnum($userPage) == false) {
    header("Location: " . getenv('APP_URL') . "?e=urlissue");
    exit;
}

authenticateFromCookie($user, $permissions, $userDetails);

getUserPageInfo($userPage, $userData, 0, 0, $user);
if (!$userData) {
    http_response_code(404);
    echo "user not found";
    exit;
}

$gamesCount = getGamesListByDev($userPage, 0, $gamesList, 1);
$achievementCount = 0;
$leaderboardCount = 0;
foreach ($gamesList as $game) {
    $achievementCount += $game['NumAchievements'];
    $leaderboardCount += $game['NumLBs'];
}

if (getActiveClaimCount($userPage, true, true) > 0) {
    $userClaimData = getFilteredClaimData(0, ClaimFilters::Default, ClaimSorting::GameAscending, false, $userPage); // Active claims sorted by game title
}

RenderHtmlStart(true);

?>
<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader(); ?>
    <?php RenderTitleTag($userPage); ?>
</head>
<body>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php RenderUserHeader($userData, 'Development', $userPage == $user); ?>
        <div style='margin-top: 4px'>
            <div style='overflow: hidden'>
            <?php
            RenderPageStatistic('Sets<br/>Authored', (string) $gamesCount);
            RenderPageStatistic('Achievements<br/>Created', (string) $achievementCount);
            RenderPageStatistic('Leaderboards<br/>Created', (string) $leaderboardCount);
            RenderPageStatistic('Achievements<br/>Awarded', (string) $userData['ContribCount']);
            RenderPageStatistic('Points</br>Awarded', (string) $userData['ContribYield']);
            ?>
            </div>
        </div>
        <div class='commentscomponent left' style='margin-top:10px'>
            <a href='/gameList.php?d=<?= $userPage ?>'>View all achievement sets <b><?= $userPage ?></b> has worked on.</a>
            <br/>
            <a href='/individualdevstats.php?u=<?= $userPage ?>'>View detailed developer stats</a>
            <br/>
            <?php if ($permissions >= Permissions::Registered) {
                $openTicketsData = countOpenTicketsByDev($userPage);
                echo "<a href='/ticketmanager.php?u=$userPage'>Open Tickets: <b>" . array_sum($openTicketsData) . "</b></a><br>";
            } ?>
        </div>
        <div class='commentscomponent left' style='margin-top:10px'>
            <h4>Active Claims</h4>
            <?php
            if (isset($userClaimData) && count($userClaimData) > 0) {
                foreach ($userClaimData as $claim) {
                    $details = "";
                    $isCollab = $claim['ClaimType'] == ClaimType::Collaboration;
                    $isSpecial = $claim['Special'] != ClaimSpecial::None;
                    if ($isCollab) {
                        $details = " (" . ClaimType::toString(ClaimType::Collaboration) . ")";
                    } else {
                        if (!$isSpecial) {
                            $details = "*";
                        }
                    }
                    echo GetGameAndTooltipDiv($claim['GameID'], $claim['GameTitle'], $claim['GameIcon'], $claim['ConsoleName'], false, 22) . $details . '<br>';
                }
                echo "* Counts against reservation limit";
            } else {
                echo "No active claims";
            }
            ?>
            <br/><br/>
            <a href='/claimlist.php?u=<?= $userPage ?>'>View all claims</a>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
