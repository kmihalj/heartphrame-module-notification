# Notification Module Guide

## Responsibilities

`NotificationService` is the public business API. It owns persistence, unread
counts, pagination, read state, and per-user deduplication. Callers do not need
to know the database schema.

`NotificationNavigationProvider` exposes only the unread count and inbox path
to Auth navigation. The badge therefore remains a small optional integration
rather than coupling Auth to the inbox database.

The inbox formats timestamps for the active application language while keeping
the original machine-readable value in the HTML `datetime` attribute.

## Creating a Notification

```php
$notifications->notifyUser(
    $userId,
    'workspace.review_requested',
    'Page awaiting review',
    'The page "Guide" was submitted for review.',
    '/workspace/team/guide?draft=preview',
    'workspace',
    '42:en',
    'workspace:review:42:en:7',
    ['node_id' => 42, 'version_number' => 7],
    true,
);
```

The link must be a local absolute path. The inbox controller rejects external
or protocol-relative redirects. When a non-empty dedup key already exists for
the same user, the existing row is refreshed and becomes unread again.

Use `notifyUsers()` for a list of recipients. Duplicate and invalid IDs are
ignored.

## Read State and Removal

Opening a notification marks it as read before following its safe local link.
The inbox can also mark every notification as read. A user may permanently
remove one of their read notifications or clear all their read notifications;
unread notifications and messages owned by another user cannot be removed by
these actions.

## Optional E-mail

The user's Auth account screen contains **Send application notifications by
e-mail**. The preference defaults to off. When module-email is installed and
the recipient opted in, an in-app notification also enters the SMTP outbox.
Every error in this auxiliary bridge is isolated: an unavailable mail server
must never prevent the in-app message or the business workflow that created it.

## Optional HTTP API

`config/api.php` contributes `notifications:read` and `notifications:write`
without importing API classes. When the API module is also enabled, its routes
operate only on the key owner's inbox. There is deliberately no endpoint for
creating arbitrary notifications; domain workflows create messages.

## Data and Privacy

Store only the metadata needed to present or route the notification. The
`data_json` field is useful for IDs and version numbers, but it should not hold
passwords, tokens, full documents, or other secrets.

## Large inboxes

Inbox pagination uses one `COUNT` and one bounded page query regardless of the
number of stored notifications. The initial migration includes a composite
`user_id`, `read_at`, `created_at` index, and the regression suite verifies the
two-query contract with 250 unread notifications. No cross-request unread-count
cache is used, so new and read messages are visible immediately.
