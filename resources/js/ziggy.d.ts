/* This file is generated by Ziggy. */
declare module 'ziggy-js' {
  interface RouteList {
    "forum.post.edit": [
        {
            "name": "forumTopicComment",
            "required": true,
            "binding": "ID"
        }
    ],
    "forum-topic.create": [
        {
            "name": "forum",
            "required": true,
            "binding": "ID"
        }
    ],
    "demo": [],
    "playground.index": [],
    "home": [],
    "tickets.index": [],
    "ranking.beaten-games": [],
    "message-thread.index": [],
    "message.create": [],
    "achievement.tickets": [
        {
            "name": "achievement",
            "required": true,
            "binding": "ID"
        }
    ],
    "achievement.create-ticket": [
        {
            "name": "achievement",
            "required": true,
            "binding": "ID"
        }
    ],
    "achievement.comments": [
        {
            "name": "achievement",
            "required": true,
            "binding": "ID"
        }
    ],
    "redirect": [],
    "developer.tickets": [
        {
            "name": "user",
            "required": true,
            "binding": "User"
        }
    ],
    "reporter.tickets": [
        {
            "name": "user",
            "required": true,
            "binding": "User"
        }
    ],
    "developer.tickets.resolved": [
        {
            "name": "user",
            "required": true,
            "binding": "User"
        }
    ],
    "user.tickets.created": [
        {
            "name": "user",
            "required": true,
            "binding": "User"
        }
    ],
    "developer.feed": [
        {
            "name": "user",
            "required": true,
            "binding": "User"
        }
    ],
    "developer.claims": [
        {
            "name": "user",
            "required": true,
            "binding": "User"
        }
    ],
    "developer.sets": [
        {
            "name": "user",
            "required": true,
            "binding": "User"
        }
    ],
    "user.comments": [
        {
            "name": "user",
            "required": true,
            "binding": "User"
        }
    ],
    "user.game.activity": [
        {
            "name": "user",
            "required": true,
            "binding": "User"
        },
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "game.compare-unlocks": [
        {
            "name": "user",
            "required": true,
            "binding": "User"
        },
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "user.moderation-comments": [
        {
            "name": "user",
            "required": true,
            "binding": "User"
        }
    ],
    "user.completion-progress": [
        {
            "name": "user",
            "required": true,
            "binding": "User"
        }
    ],
    "leaderboard.comments": [
        {
            "name": "leaderboard",
            "required": true,
            "binding": "ID"
        }
    ],
    "game.hash.manage": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "game.hashes.comments": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "game.tickets": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "game.dev-interest": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "game.modification-comments": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "game.masters": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "game.suggest": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "game.claims": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "game.claims.comments": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "system.game.index": [
        {
            "name": "system",
            "required": true,
            "binding": "ID"
        }
    ],
    "ticket.show": [
        {
            "name": "ticket",
            "required": true,
            "binding": "ID"
        }
    ],
    "message-thread.show": [
        {
            "name": "messageThread",
            "required": true,
            "binding": "id"
        }
    ],
    "games.suggest": [],
    "claims.expiring": [],
    "claims.completed": [],
    "claims.active": [],
    "pulse": [],
    "api.game.index": [],
    "game.hashes.index": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "game.index": [],
    "game.random": [],
    "api.user.game.destroy": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "api.user.achievement.destroy": [
        {
            "name": "achievement",
            "required": true,
            "binding": "ID"
        }
    ],
    "api.user.game-achievement-set.preference.update": [
        {
            "name": "gameAchievementSet",
            "required": true,
            "binding": "id"
        }
    ],
    "game-hash.update": [
        {
            "name": "gameHash",
            "required": true,
            "binding": "hash"
        }
    ],
    "game-hash.destroy": [
        {
            "name": "gameHash",
            "required": true,
            "binding": "hash"
        }
    ],
    "player.games.resettable": [],
    "player.game.achievements.resettable": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "achievement.report-issue.index": [
        {
            "name": "achievement",
            "required": true,
            "binding": "ID"
        }
    ],
    "api.game.comment.store": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "api.game.comment.destroy": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        },
        {
            "name": "comment",
            "required": true,
            "binding": "ID"
        }
    ],
    "api.subscription.store": [
        {
            "name": "subjectType",
            "required": true
        },
        {
            "name": "subjectId",
            "required": true
        }
    ],
    "api.subscription.destroy": [
        {
            "name": "subjectType",
            "required": true
        },
        {
            "name": "subjectId",
            "required": true
        }
    ],
    "api.user-game-list.index": [],
    "api.user-game-list.store": [
        {
            "name": "game",
            "required": true
        }
    ],
    "api.user-game-list.destroy": [
        {
            "name": "game",
            "required": true
        }
    ],
    "game.comment.index": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "forum.recent-posts": [],
    "user.posts.index": [
        {
            "name": "user",
            "required": true,
            "binding": "User"
        }
    ],
    "settings.show": [],
    "user.comment.destroyAll": [
        {
            "name": "user",
            "required": true
        }
    ],
    "game-list.play.index": [],
    "achievement-set-claim.create": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "achievement-set-claim.delete": [
        {
            "name": "game",
            "required": true,
            "binding": "ID"
        }
    ],
    "achievement-set-claim.update": [
        {
            "name": "claim",
            "required": true,
            "binding": "ID"
        }
    ],
    "message.store": [],
    "message-thread.destroy": [
        {
            "name": "messageThread",
            "required": true,
            "binding": "id"
        }
    ],
    "api.settings.profile.update": [],
    "api.settings.locale.update": [],
    "api.settings.preferences.update": [],
    "api.settings.password.update": [],
    "api.settings.email.update": [],
    "api.settings.keys.web.destroy": [],
    "api.settings.keys.connect.destroy": [],
    "login": [],
    "logout": [],
    "password.confirmation": [],
    "password.confirm": [],
    "download.index": [],
    "user.show": [
        {
            "name": "user",
            "required": true
        }
    ],
    "achievement.show": [
        {
            "name": "achievement",
            "required": true
        },
        {
            "name": "slug",
            "required": false
        }
    ],
    "game.show": [
        {
            "name": "game",
            "required": true
        },
        {
            "name": "slug",
            "required": false
        }
    ],
    "leaderboard.show": [
        {
            "name": "leaderboard",
            "required": true
        },
        {
            "name": "slug",
            "required": false
        }
    ],
    "contact": [],
    "rss.index": [],
    "terms": [],
    "user.permalink": [
        {
            "name": "hashId",
            "required": true
        }
    ],
    "api.user.delete-request.store": [],
    "api.user.delete-request.destroy": [],
    "api.user.avatar.store": [],
    "api.user.avatar.destroy": []
}
}
export {};
