# Notification Module Guide

## Responsibilities

`NotificationService` is the public business API. It owns persistence, unread
counts, pagination, read state, and per-user deduplication. Callers do not need
to know the database schema.

`NotificationNavigationProvider` exposes only the unread count and inbox path
to Auth navigation. The badge therefore remains a small optional integration
rather than coupling Auth to the inbox database.

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

## Optional E-mail

When module-email is installed and notification copies are enabled, the first
in-app notification also enters the SMTP outbox. Every error in this auxiliary
bridge is isolated: an unavailable mail server must never prevent the in-app
message or the business workflow that created it.

## Data and Privacy

Store only the metadata needed to present or route the notification. The
`data_json` field is useful for IDs and version numbers, but it should not hold
passwords, tokens, full documents, or other secrets.
