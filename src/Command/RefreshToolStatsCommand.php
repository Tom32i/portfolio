<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Writes the GitHub star count and the registry downloads over the last year
 * into each content/tool/*.yaml; a failed fetch keeps the value already there.
 */
#[AsCommand(name: 'app:refresh-tool-stats', description: 'Fetch GitHub stars and npm/Packagist downloads into content/tool/*.yaml')]
class RefreshToolStatsCommand extends Command
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%kernel.project_dir%/content/tool')]
        private string $toolsDirectory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $failures = 0;

        foreach ((new Finder())->files()->in($this->toolsDirectory)->name('*.yaml')->sortByName() as $file) {
            $path = $file->getRealPath();
            $tool = Yaml::parseFile($path);
            $values = [];

            if (!\is_array($tool)) {
                throw new \UnexpectedValueException(\sprintf('%s does not hold a tool.', $file->getFilename()));
            }

            try {
                $values['stars'] = $this->fetchStars($this->stringField($tool, 'url') ?? '');
            } catch (\Throwable $exception) {
                ++$failures;
                $io->warning(\sprintf('%s: stars kept, %s', $file->getFilename(), $exception->getMessage()));
            }

            try {
                $npm = $this->stringField($tool, 'npm');
                $packagist = $this->stringField($tool, 'packagist');

                if ($npm !== null) {
                    $values['downloads'] = $this->fetchNpmDownloads($npm);
                } elseif ($packagist !== null) {
                    $values['downloads'] = $this->fetchPackagistDownloads($packagist);
                }
            } catch (\Throwable $exception) {
                ++$failures;
                $io->warning(\sprintf('%s: downloads kept, %s', $file->getFilename(), $exception->getMessage()));
            }

            $this->write($path, $values);
            $io->text(\sprintf('%s: %s', $file->getFilename(), json_encode($values)));
        }

        return $failures === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * @param array<mixed> $tool
     */
    private function stringField(array $tool, string $key): ?string
    {
        if (!isset($tool[$key])) {
            return null;
        }

        if (!\is_string($tool[$key])) {
            throw new \UnexpectedValueException(\sprintf('"%s" should be a string.', $key));
        }

        return $tool[$key];
    }

    private function fetchStars(string $repositoryUrl): int
    {
        if (preg_match('#^https://github\.com/([^/]+/[^/]+)/?$#', $repositoryUrl, $matches) !== 1) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a GitHub repository URL.', $repositoryUrl));
        }

        $repository = $this->httpClient->request('GET', 'https://api.github.com/repos/' . $matches[1])->toArray();
        $stars = $repository['stargazers_count'] ?? null;

        if (!\is_int($stars)) {
            throw new \UnexpectedValueException('GitHub returned no star count.');
        }

        return $stars;
    }

    private function fetchNpmDownloads(string $package): int
    {
        $point = $this->httpClient->request('GET', 'https://api.npmjs.org/downloads/point/last-year/' . $package)->toArray();
        $downloads = $point['downloads'] ?? null;

        if (!\is_int($downloads)) {
            throw new \UnexpectedValueException('npm returned no download count.');
        }

        return $downloads;
    }

    private function fetchPackagistDownloads(string $package): int
    {
        $today = new \DateTimeImmutable('today');
        $stats = $this->httpClient->request('GET', \sprintf('https://packagist.org/packages/%s/stats/all.json', $package), [
            'query' => [
                'average' => 'daily',
                'from' => $today->modify('-1 year')->format('Y-m-d'),
                'to' => $today->format('Y-m-d'),
            ],
        ])->toArray();
        $dailyInstalls = \is_array($stats['values'] ?? null) ? ($stats['values'][$package] ?? null) : null;

        if (!\is_array($dailyInstalls)) {
            throw new \UnexpectedValueException('Packagist returned no daily installs.');
        }

        return (int) array_sum($dailyInstalls);
    }

    /**
     * Replaces the given keys in place, or inserts them before "priority", leaving the rest of the file untouched.
     *
     * @param array<string, int> $values
     */
    private function write(string $path, array $values): void
    {
        $yaml = (string) file_get_contents($path);

        foreach ($values as $key => $value) {
            $line = \sprintf('%s: %d', $key, $value);
            $yaml = preg_match(\sprintf('/^%s: .*$/m', $key), $yaml) === 1
                ? (string) preg_replace(\sprintf('/^%s: .*$/m', $key), $line, $yaml)
                : (string) preg_replace('/^priority:/m', $line . "\npriority:", $yaml, 1);
        }

        file_put_contents($path, $yaml);
    }
}
