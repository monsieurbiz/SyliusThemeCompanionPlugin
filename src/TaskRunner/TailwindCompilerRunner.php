<?php

/*
 * This file is part of Monsieur Biz' Theme Companion plugin for Sylius.
 *
 * (c) Monsieur Biz <sylius@monsieurbiz.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MonsieurBiz\SyliusThemeCompanionPlugin\TaskRunner;

use Exception;
use MonsieurBiz\SyliusThemeCompanionPlugin\Process\ProcessOutputAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

/**
 * Eavily inspired by SymfonyCasts TailwindBundle but rewrite to be more flexible
 * and to be able to use it in a more generic way.
 */
#[AutoconfigureTag('monsieurbiz_theme_companion.task_runner')]
class TailwindCompilerRunner implements TaskRunnerInterface
{
    use ProcessOutputAwareTrait;

    public const string IDENTIFIER = 'tailwind';

    private const string DEFAULT_BINARY_VERSION = 'v3.4.17';

    public const string DEFAULT_TAILWIND_DIR = '/var/theme_companion/tailwind';

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly Environment $twig,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public static function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function process(string $input, string $output, ?array $config = null): bool
    {
        $config = $config ?? [];
        $binaryPath = $this->initBinary($config);
        $configFilePath = $this->generateConfigFile($config);

        $arguments = [$binaryPath, '-c', $configFilePath, '-i', $input, '-o', $output];
        if (true === ($config['minify'] ?? false)) {
            $arguments[] = '----minify';
        }

        $this->getProcessOutputHandler()?->handle(\sprintf('TailwindCSS build command %s', implode(' ', $arguments)));
        $process = new Process($arguments, $this->projectDir);
        $process->start();
        foreach ($process as $data) {
            $this->getProcessOutputHandler()?->handle($data);
        }

        if (!$process->isSuccessful()) {
            return false;
        }

        return true;
    }

    private function generateConfigFile(array $config): string
    {
        $configFileDir = $config['config_file_generation_dir'] ?? Path::join($this->projectDir, '/var/tailwind');
        $configFileTemplate = $config['config_file_template'] ?? '@MonsieurBizSyliusThemeCompanionPlugin/tailwind.config.js.twig';
        $tailwindConfigContent = $this->twig->render($configFileTemplate, [
            'presets' => $config['presets'] ?? [],
            'files' => $config['files'] ?? [],
        ]);
        $configFilePath = Path::join($configFileDir, 'tailwind.config.js');
        $filesystem = new Filesystem();
        $filesystem->dumpFile($configFilePath, $tailwindConfigContent);

        return $configFilePath;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function downloadBinary(string $binaryDir, string $binaryVersion, string $binaryName): void
    {
        $url = \sprintf('https://github.com/tailwindlabs/tailwindcss/releases/download/%s/%s', $binaryVersion, $binaryName);
        $this->getProcessOutputHandler()?->handle(\sprintf('Downloading TailwindCSS binary from %s', $url));

        $versionnedBinaryDir = Path::join($binaryDir, $binaryVersion);
        if (!is_dir($versionnedBinaryDir)) {
            mkdir($versionnedBinaryDir, 0777, true);
        }

        $binaryPath = Path::join($versionnedBinaryDir, $binaryName);
        if (is_file($binaryPath)) {
            unlink($binaryPath);
        }

        $response = $this->httpClient->request('GET', $url);

        $fileHandler = fopen($binaryPath, 'w');
        if (false === $fileHandler) {
            throw new Exception(\sprintf('Cannot open file %s', $binaryPath));
        }

        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }
        fclose($fileHandler);
        chmod($binaryPath, 0777);

        $this->getProcessOutputHandler()?->handle(\sprintf('TailwindCSS binary is now available here: %s', $binaryPath));
    }

    private function initBinary(array $config): string
    {
        $binaryDir = Path::canonicalize($config['binary_dir'] ?? Path::join($this->projectDir, self::DEFAULT_TAILWIND_DIR));
        $binaryVersion = Path::canonicalize($config['binary_version'] ?? self::DEFAULT_BINARY_VERSION);
        $binaryName = self::getBinaryName();
        $binaryPath = Path::join($binaryDir, $binaryVersion, $binaryName);

        if (!is_file($binaryPath)) {
            $this->downloadBinary($binaryDir, $binaryVersion, $binaryName);
        }

        return $binaryPath;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @see https://github.com/SymfonyCasts/tailwind-bundle/blob/81c9e6ff2bb1a95e67fc6af04ca87fccdcf55aa4/src/TailwindBinary.php#L118
     */
    public static function getBinaryName(): string
    {
        $os = strtolower(\PHP_OS);
        $machine = strtolower(php_uname('m'));

        if (str_contains($os, 'darwin')) {
            if ('arm64' === $machine) {
                return 'tailwindcss-macos-arm64';
            }
            if ('x86_64' === $machine) {
                return 'tailwindcss-macos-x64';
            }

            throw new Exception(\sprintf('No matching machine found for Darwin platform (Machine: %s).', $machine));
        }

        if (str_contains($os, 'linux')) {
            if ('arm64' === $machine || 'aarch64' === $machine) {
                return 'tailwindcss-linux-arm64';
            }
            if ('armv7' === $machine) {
                return 'tailwindcss-linux-armv7';
            }
            if ('x86_64' === $machine) {
                return 'tailwindcss-linux-x64';
            }

            throw new Exception(\sprintf('No matching machine found for Linux platform (Machine: %s).', $machine));
        }

        if (str_contains($os, 'win')) {
            if ('arm64' === $machine) {
                return 'tailwindcss-windows-arm64.exe';
            }
            if ('x86_64' === $machine || 'amd64' === $machine) {
                return 'tailwindcss-windows-x64.exe';
            }

            throw new Exception(\sprintf('No matching machine found for Windows platform (Machine: %s).', $machine));
        }

        throw new Exception(\sprintf('Unknown platform or architecture (OS: %s, Machine: %s).', $os, $machine));
    }
}
