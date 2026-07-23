<?php

declare(strict_types=1);

namespace AaiEduHr\HeartPhrameModuleNotification\Command;

use HeartPhrame\Config\ConfigInterface;
use InvalidArgumentException;
use RuntimeException;

use function array_slice;
use function array_values;
use function date;
use function is_dir;
use function is_file;
use function is_scalar;
use function mkdir;
use function preg_replace;
use function rtrim;
use function str_starts_with;
use function strtolower;
use function trim;

/**
 * HR: Pruža CLI instalaciju jedine početne Notification migracije.
 * EN: Provides CLI installation of the single initial Notification migration.
 */
final readonly class HpNotificationCommand
{
    private const DEFAULT_MIGRATIONS_PATH = 'database/migrations';

    private const TEMPLATE_FILE = 'resources/migrations/initial_notification_schema.php';

    /**
     * HR: Prima konfiguraciju host aplikacije za cilj migracije.
     * EN: Receives host-application configuration for the migration target.
     */
    public function __construct(private ConfigInterface $config)
    {
    }

    /**
     * HR: Usmjerava glavnu naredbu na instalaciju ili pomoć.
     * EN: Routes the main command to installation or help.
     *
     * @param array<int, string> $arguments
     * @param array<string, mixed> $options
     */
    public function run(array $arguments = [], array $options = []): int
    {
        $subcommand = strtolower(trim((string)($arguments[0] ?? 'help')));

        return match ($subcommand) {
            'install', 'migration:install', 'install-migration', 'scaffold' =>
            $this->installMigration(array_values(array_slice($arguments, 1)), $options),
            'help', '--help', '-h' => $this->help(),
            default => $this->unknownSubcommand($subcommand),
        };
    }

    /**
     * HR: Kopira početnu migraciju u host aplikaciju.
     * EN: Copies the initial migration into the host application.
     *
     * @param array<int, string> $arguments
     * @param array<string, mixed> $options
     */
    public function installMigration(array $arguments = [], array $options = []): int
    {
        $directory = $this->targetDirectory($options);
        $template = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . self::TEMPLATE_FILE;
        if (!is_file($template)) {
            throw new RuntimeException(__('Predložak Notification migracije nije pronađen.'));
        }

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException(__('Nije moguće kreirati direktorij migracija.'));
        }

        $name = $this->migrationSuffix($arguments, $options);
        $target = rtrim($directory, DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR
        . date('YmdHis')
        . '_'
        . $name
        . '.php';
        $content = file_get_contents($template);
        if (!is_string($content) || $content === '' || file_put_contents($target, $content) === false) {
            throw new RuntimeException(__('Nije moguće kopirati Notification migraciju.'));
        }

        $this->write(__('Kreirana je početna Notification migracija: ') . $target);
        $this->write(__('Sljedeći korak: pokreni `vendor/bin/hph orm-migrate up`.'));

        return 0;
    }

    /**
     * HR: Ispisuje kratku pomoć.
     * EN: Prints concise help.
     */
    public function help(): int
    {
        $this->write('hph notification <install|help>');
        $this->write('  vendor/bin/hph notification install');

        return 0;
    }

    /**
     * HR: Vraća status greške za nepoznatu podnaredbu.
     * EN: Returns an error status for an unknown subcommand.
     */
    private function unknownSubcommand(string $subcommand): int
    {
        $this->write(sprintf(__('Nepoznata Notification podnaredba: %s'), $subcommand));

        return 1;
    }

    /**
     * HR: Razrješava ciljni direktorij iz opcije ili app roota.
     * EN: Resolves the target directory from an option or application root.
     *
     * @param array<string, mixed> $options
     */
    private function targetDirectory(array $options): string
    {
        $path = $this->option($options, ['path', 'p']);
        if ($path === null) {
            return rtrim($this->config->getAppRootDir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . self::DEFAULT_MIGRATIONS_PATH;
        }

        return str_starts_with($path, DIRECTORY_SEPARATOR)
        ? rtrim($path, DIRECTORY_SEPARATOR)
        : rtrim($this->config->getAppRootDir(), DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR
        . rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * HR: Normalizira naziv generirane migracije.
     * EN: Normalizes the generated migration name.
     *
     * @param array<int, string> $arguments
     * @param array<string, mixed> $options
     */
    private function migrationSuffix(array $arguments, array $options): string
    {
        $name = $this->option($options, ['name']) ?? trim((string)($arguments[0] ?? ''));
        $name = $name !== '' ? $name : 'install_notification_module_schema';
        $name = trim((string)preg_replace('/[^a-z0-9_]+/i', '_', strtolower($name)), '_');
        if ($name === '') {
            throw new InvalidArgumentException(__('Naziv migracije ne smije biti prazan.'));
        }

        return $name;
    }

    /**
     * HR: Čita prvu nepraznu skalarnu CLI opciju.
     * EN: Reads the first non-empty scalar CLI option.
     *
     * @param array<string, mixed> $options
     * @param list<string> $keys
     */
    private function option(array $options, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $options[$key] ?? null;
            if (is_scalar($value) && trim((string)$value) !== '') {
                return trim((string)$value);
            }
        }

        return null;
    }

    /**
     * HR: Ispisuje jednu CLI poruku.
     * EN: Prints one CLI message.
     */
    private function write(string $message): void
    {
        echo $message . PHP_EOL;
    }
}
