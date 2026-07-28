<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $title
 * @var bool $migrationMissing
 * @var array{
 *     items: list<array{
 *         id: int,
 *         uuid: string,
 *         user_id: int,
 *         notification_key: string,
 *         title: string,
 *         message: string,
 *         link_url: string|null,
 *         source_module: string,
 *         source_reference: string|null,
 *         dedup_key: string|null,
 *         data: array<string, mixed>,
 *         read_at: string|null,
 *         created_at: string,
 *         updated_at: string,
 *         is_read: bool,
 *         open_url: string,
 *         delete_url: string
 *     }>,
 *     total: int,
 *     page: int,
 *     pages: int,
 *     page_size: int
 * } $inbox
 * @var int $unreadCount
 * @var string $markAllPath
 * @var string $deleteAllReadPath
 * @var string $indexPath
 */

$items = $inbox['items'] ?? [];
$total = (int)($inbox['total'] ?? 0);
$page = (int)($inbox['page'] ?? 1);
$pages = (int)($inbox['pages'] ?? 1);
$readCount = max(0, $total - $unreadCount);
?>
<section class="card shadow-sm">
    <div class="card-body">
        <header class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h1 class="h3 mb-1"><?= $this->escape($title) ?></h1>
                <p class="text-body-secondary mb-0">
                    <?= $this->escape(
                        $unreadCount > 0
                            ? sprintf(__('Nepročitane poruke: %d'), $unreadCount)
                            : __('Nema nepročitanih poruka.'),
                    ) ?>
                </p>
            </div>
            <?php if (!$migrationMissing && ($unreadCount > 0 || $readCount > 0)) : ?>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <?php if ($unreadCount > 0) : ?>
                        <form method="post" action="<?= $this->escape($markAllPath) ?>">
                            <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                            <button class="btn btn-sm btn-secondary" type="submit">
                                <?= $this->escape(__('Označi sve pročitanima')) ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if ($readCount > 0) : ?>
                        <form method="post" action="<?= $this->escape($deleteAllReadPath) ?>">
                            <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                            <button class="btn btn-sm btn-danger" type="submit">
                                <?= $this->escape(__('Ukloni pročitane')) ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </header>

        <?php if ($migrationMissing) : ?>
            <div class="alert alert-warning mb-0" role="alert">
                <strong><?= $this->escape(__('Nedostaje migracija obavijesti.')) ?></strong>
                <div><?= $this->escape(
                    __('Instalirajte početnu migraciju i pokrenite ORM migracije.'),
                ) ?></div>
            </div>
        <?php elseif ($items === []) : ?>
            <div class="text-body-secondary py-4 text-center">
            <?= $this->escape(__('Još nema obavijesti.')) ?>
            </div>
        <?php else : ?>
            <div class="list-group list-group-flush border-top border-bottom">
            <?php foreach ($items as $notification) : ?>
                <?php
                $href = is_scalar($notification['open_url'] ?? null)
                    ? (string)$notification['open_url']
                    : '';
                $deleteUrl = is_scalar($notification['delete_url'] ?? null)
                    ? (string)$notification['delete_url']
                    : '';
                $isRead = (bool)($notification['is_read'] ?? false);
                $itemClass = $isRead ? '' : 'border-start border-4 border-primary';
                ?>
                    <div class="list-group-item py-3 <?= $itemClass ?>">
                        <div class="d-flex align-items-center gap-3">
                            <a
                                class="flex-grow-1 overflow-hidden text-body text-decoration-none"
                                href="<?= $this->escape($href) ?>"
                            >
                                <div class="<?= $isRead ? '' : 'fw-semibold' ?>">
                                    <?= $this->escape((string)($notification['title'] ?? '')) ?>
                                </div>
                                <div class="text-body-secondary mt-1">
                                    <?= nl2br(
                                        $this->escape((string)($notification['message'] ?? '')),
                                    ) ?>
                                </div>
                            </a>
                            <div class="d-flex flex-shrink-0 align-items-center gap-2">
                                <time class="small text-body-secondary text-nowrap">
                                    <?= $this->escape((string)($notification['created_at'] ?? '')) ?>
                                </time>
                                <?php if ($isRead) : ?>
                                    <form method="post" action="<?= $this->escape($deleteUrl) ?>">
                                        <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                                        <button
                                            class="btn btn-sm btn-danger p-2 lh-1"
                                            type="submit"
                                            title="<?= $this->escape(__('Ukloni obavijest')) ?>"
                                            aria-label="<?= $this->escape(__('Ukloni obavijest')) ?>"
                                        >
                                            <svg
                                                aria-hidden="true"
                                                width="16"
                                                height="16"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="M3 6h18"/>
                                                <path d="M8 6V4h8v2"/>
                                                <path d="M19 6l-1 14H6L5 6"/>
                                                <path d="M10 11v5"/>
                                                <path d="M14 11v5"/>
                                            </svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
            <?php endforeach; ?>
            </div>

            <?php if ($pages > 1) : ?>
                <nav
                    class="d-flex align-items-center justify-content-center gap-2 mt-3"
                    aria-label="<?= $this->escape(__('Stranice obavijesti')) ?>"
                >
                    <a
                        class="btn btn-sm btn-secondary <?= $page <= 1 ? 'disabled' : '' ?>"
                        href="<?= $this->escape($indexPath . '?page=' . max(1, $page - 1)) ?>"
                    ><?= $this->escape(__('Prethodna')) ?></a>
                    <span class="small text-body-secondary">
                    <?= $this->escape(sprintf(__('%d od %d'), $page, $pages)) ?>
                    </span>
                    <a
                        class="btn btn-sm btn-secondary <?= $page >= $pages ? 'disabled' : '' ?>"
                        href="<?= $this->escape($indexPath . '?page=' . min($pages, $page + 1)) ?>"
                    ><?= $this->escape(__('Sljedeća')) ?></a>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
