<?php

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

// Get user's list of played games and pct completion
$userCompletedGamesList = getUsersCompletedGamesAndMax($userPage);
$userCompletedGamesListCount = count($userCompletedGamesList);

// Merge all elements of $userCompletedGamesList into one unique list
$userCompletedGames = [];
for ($i = 0; $i < $userCompletedGamesListCount; $i++) {
    $gameID = $userCompletedGamesList[$i]['GameID'];

    if ($userCompletedGamesList[$i]['HardcoreMode'] == 0) {
        $userCompletedGames[$gameID] = $userCompletedGamesList[$i];
    }

    $userCompletedGames[$gameID]['NumAwardedHC'] = 0; // Update this later, but fill in for now
}

for ($i = 0; $i < $userCompletedGamesListCount; $i++) {
    $gameID = $userCompletedGamesList[$i]['GameID'];
    if ($userCompletedGamesList[$i]['HardcoreMode'] == 1) {
        $userCompletedGames[$gameID]['NumAwardedHC'] = $userCompletedGamesList[$i]['NumAwarded'];
    }
}

// Custom sort, then overwrite $userCompletedGamesList
usort($userCompletedGames, fn ($a, $b) => ($b['PctWon'] ?? 0) <=> ($a['PctWon'] ?? 0));

$userCompletedGamesList = $userCompletedGames;

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
        <?php RenderUserHeader($userData, 'Awards', $userPage == $user); ?>
        <div style='margin-top: 4px'>
            <div class='gamescreenshots' style='margin-top: 20px; width=100%'>
                <?php RenderSiteAwards(getUsersSiteAwards($userPage), 15); ?>
            </div>
            <div class='gamescreenshots' style='margin-top: 20px; width=100%'>
                <?php RenderCompletedGamesList($userCompletedGamesList); ?>
            </div>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
