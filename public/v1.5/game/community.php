<?php

use RA\ArticleType;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputSanitized('ID', null, 'integer');
if ($gameID == null || $gameID == 0) {
    header("Location: " . getenv('APP_URL') . "?e=urlissue");
    exit;
}

authenticateFromCookie($user, $permissions, $userDetails);

$gameData = getGameData($gameID);

if ($gameData['ForumTopicID']) {
    $topicData = getTopicSummaries([$gameData['ForumTopicID']], $permissions)[0];
} else {
    $topicData = null;
}

$numArticleComments = getArticleComments(ArticleType::Game, $gameID, 0, 20, $commentData);

RenderHtmlStart(true);

?>
<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader(); ?>
    <?php RenderTitleTag($gameData['Title'] . ' (' . $gameData['ConsoleName'] . ')'); ?>
</head>
<body>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php RenderGameHeader($gameData, 'Community'); ?>
        <div style='margin-top: 4px'>
            <?php if ($topicData) {
    $postedNiceDate = getNiceDate(strtotime($topicData['ForumTopicPostedDate']));
    $lastCommentPostedNiceDate = getNiceDate(strtotime($topicData['LatestCommentPostedDate']));
    $lastCommentID = $topicData['LatestCommentID'];
    $topicID = $topicData['ForumTopicID']; ?>
                <table><tbody>
                    <tr class='forumsheader'><th></th><th class='fullwidth'>Topics</th><th>Author</th><th>Replies</th><th class='text-nowrap'>Last post</th></tr>
                    <tr>
                        <td class='unreadicon p-1'><img src='<?= asset('Images/ForumTopicUnread32.gif') ?>' width='20' height='20' /></td>
                        <td class='topictitle'><a alt='Posted <?= $postedNiceDate ?>' title='Posted on <?= $postedNiceDate ?>' href='/viewtopic.php?t=<?= $topicID ?>'>Official Forum Topic</a></td>
                        <td class='author'><?= GetUserAndTooltipDiv($topicData['Author'], false); ?></td>
                        <td class='replies'><?= $topicData['NumTopicReplies'] ?></td>
                        <td class='lastpost'>
                            <div class='lastpost'>
                                <span class='smalldate'><?= $lastCommentPostedNiceDate ?></span><br>
                                <?= GetUserAndTooltipDiv($topicData['LatestCommentAuthor'], false) ?>
                                <a href='/viewtopic.php?t=<?= $topicID ?>&amp;c=<?= $lastCommentID ?>#<?= $lastCommentID ?>' title='View latest post' alt='View latest post'>[View]</a>
                            </div>
                        </td>
                    </tr>
                </tbody></table>
            <?php
} ?>
        </div>
        <div style='margin-top: 20px'>
            <?php RenderCommentsComponent($user, $numArticleComments, $commentData, $gameID, ArticleType::Game, $permissions); ?>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
