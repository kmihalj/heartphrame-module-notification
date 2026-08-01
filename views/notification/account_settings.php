<?php

declare(strict_types=1);

/**
 * HR: Osobna postavka e-mail kopija za prijavljenog korisnika.
 * EN: Personal e-mail-copy preference for the authenticated user.
 *
 * @var \HeartPhrame\View\View $this
 * @var bool $emailEnabled
 * @var string $savePath
 */

$emailEnabled = (bool)($emailEnabled ?? false);
$savePath = is_string($savePath ?? null) ? $savePath : '';
?>
<div class="card shadow-sm">
    <div class="card-body p-4">
        <h2 class="h5 mb-2"><?= __('Obavijesti') ?></h2>
        <p class="text-body-secondary mb-3">
            <?= __('Odaberite želite li uz obavijest u aplikaciji primiti i e-mail kopiju.') ?>
        </p>

        <form method="post" action="<?= $this->escape($savePath) ?>">
            <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
            <div class="form-check form-switch">
                <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    id="notification-email-enabled"
                    name="email_enabled"
                    value="1"
                    <?= $emailEnabled ? 'checked' : '' ?>
                >
                <label class="form-check-label" for="notification-email-enabled">
                    <?= __('Šalji mi e-mail kopije obavijesti') ?>
                </label>
            </div>
            <p class="small text-body-secondary mt-2 mb-3">
                <?= __('E-mail se šalje samo kada je E-mail modul instaliran i SMTP ispravno podešen.') ?>
            </p>
            <button type="submit" class="btn btn-primary">
                <?= __('Spremi postavke obavijesti') ?>
            </button>
        </form>
    </div>
</div>
