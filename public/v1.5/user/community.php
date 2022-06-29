<?php

use RA\ArticleType;

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

$setRequestList = getUserRequestList($userPage);
$userSetRequestInformation = getUserRequestsInformation($userPage, $setRequestList);

$numPostsFound = getRecentForumPosts(0, 10, 90, $permissions, $recentPostsData, $userPage);


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
        <?php RenderUserHeader($userData, 'Community', $userPage == $user); ?>
        <div style='margin-top: 4px'>
            <?php if (isset($user) && ($user !== $userPage)) {
                echo "<div class='friendbox'>";
                echo "<div class='buttoncollection'>";

                if ($userData['Friendship'] == 1) {
                    if ($userData['FriendReciprocation'] == 1) {
                        echo "<span class='clickablebutton'><a href='/request/friend/update.php?f=$userPage&amp;a=0'>Remove friend</a></span>";
                    } elseif ($userData['FriendReciprocation'] == 0) {
                        // They haven't accepted yet
                        echo "<span class='clickablebutton'><a href='/request/friend/update.php?f=$userPage&amp;a=0'>Cancel friend request</a></span>";
                    } elseif ($userData['FriendReciprocation'] == -1) {
                        // They blocked us
                        echo "<span class='clickablebutton'><a href='/request/friend/update.php?f=$userPage&amp;a=0'>Remove friend</a></span>";
                    }
                } elseif ($userData['Friendship'] == 0) {
                    if ($userData['FriendReciprocation'] == 1) {
                        echo "<span class='clickablebutton'><a href='/request/friend/update.php?f=$userPage&amp;a=1'>Confirm friend request</a></span>";
                    } elseif ($userData['FriendReciprocation'] == 0) {
                        echo "<span class='clickablebutton'><a href='/request/friend/update.php?f=$userPage&amp;a=1'>Add friend</a></span>";
                    }
                }

                if ($userData['Friendship'] !== -1) {
                    echo "<span class='clickablebutton'><a href='/request/friend/update.php?f=$userPage&amp;a=-1'>Block user</a></span>";
                } else {
                    echo "<span class='clickablebutton'><a href='/request/friend/update.php?f=$userPage&amp;a=0'>Unblock user</a></span>";
                }

                echo "<span class='clickablebutton'><a href='/createmessage.php?t=$userPage'>Send Private Message</a></span>";

                echo "</div>"; // buttoncollection
                echo "</div>"; // friendbox
            }
            ?>
            <div style='margin-top:10px; margin-right:266px; overflow: hidden'>
                <a href='/setRequestList.php?u=<?= $userPage ?>'>Requested Sets</a> - 
                    <?= $userSetRequestInformation['used'] ?> of <?= $userSetRequestInformation['total'] ?> Requests Made
                <br/>
            </div>
            <div class='commentscomponent left' style='margin-top:10px'>
                <h4>Forum Post History</h4>
                <div class='table-wrapper'>
                    <table class='table-forum-history'><tbody>
                    <tr><th class='fullwidth'>Message</th><th class='text-nowrap'>Posted At</th></tr>

                    <?php
                    foreach ($recentPostsData as $topicPostData) {
                        $postMessage = $topicPostData['ShortMsg'];
                        $forumTopicID = $topicPostData['ForumTopicID'];
                        $forumTopicTitle = $topicPostData['ForumTopicTitle'];
                        $forumCommentID = $topicPostData['CommentID'];
                        $nicePostTime = getNiceDate(strtotime($topicPostData['PostedAt']));

                        sanitize_outputs($forumTopicTitle, $postMessage);

                        echo "<tr>";
                        echo "<td><a href='/viewtopic.php?t=$forumTopicID&c=$forumCommentID'>$forumTopicTitle</a><br>$postMessage...</td>";
                        echo "<td class='smalldate'>$nicePostTime</td>";
                        echo "</tr>";
                    }
                    ?>
                    </tbody></table>
                </div>
                <div class='rightalign'><a href='/forumposthistory.php?u=<?= $userPage ?>'>more...</a></div>
            </div>
            <div class='commentscomponent left' style='margin-top:10px'>
                <h4>User Wall</h4>

                <?php if ($userData['UserWallActive']) {
                    // passing 'null' for $user disables the ability to add comments
                    $numArticleComments = getArticleComments(ArticleType::User, $userData['ID'], 0, 100, $commentData);
                    RenderCommentsComponent(
                        ($userData['FriendReciprocation'] !== -1) ? $user : null,
                        $numArticleComments,
                        $commentData,
                        $userData['ID'],
                        ArticleType::User,
                        $permissions
                    );
                } ?>
            </div>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
