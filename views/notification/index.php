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
 *         open_url: string
 *     }>,
 *     total: int,
 *     page: int,
 *     pages: int,
 *     page_size: int
 * } $inbox
 * @var int $unreadCount
 * @var string $markAllPath
 * @var string $indexPath
 */

$items = $inbox['items'] ?? [];
$page = (int)($inbox['page'] ?? 1);
$pages = (int)($inbox['pages'] ?? 1);
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
            <?php if (!$migrationMissing && $unreadCount > 0) : ?>
                <form method="post" action="<?= $this->escape($markAllPath) ?>">
                <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                    <button class="btn btn-sm btn-secondary" type="submit">
                    <?= $this->escape(__('Označi sve pročitanima')) ?>
                    </button>
                </form>
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
                $isRead = (bool)($notification['is_read'] ?? false);
                $itemClass = $isRead ? '' : 'border-start border-4 border-primary';
                ?>
                    <a
                        class="list-group-item list-group-item-action py-3 <?= $itemClass ?>"
                        href="<?= $this->escape($href) ?>"
                    >
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="<?= $isRead ? '' : 'fw-semibold' ?>">
                                <?= $this->escape((string)($notification['title'] ?? '')) ?>
                                </div>
                                <div class="text-body-secondary mt-1">
                                <?= nl2br(
                                    $this->escape((string)($notification['message'] ?? '')),
                                ) ?>
                                </div>
                            </div>
                            <time class="small text-body-secondary text-nowrap">
                            <?= $this->escape((string)($notification['created_at'] ?? '')) ?>
                            </time>
                        </div>
                    </a>
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
