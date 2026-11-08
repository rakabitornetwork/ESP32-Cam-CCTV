<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Throwable;

class ApplicationUpdater
{
    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        if (! is_dir(base_path('.git'))) {
            return $this->unavailable('Folder aplikasi belum menjadi repositori Git.');
        }

        $branch = $this->git(['rev-parse', '--abbrev-ref', 'HEAD'], 20);
        $commit = $this->git(['rev-parse', '--short=12', 'HEAD'], 20);
        $subject = $this->git(['log', '-1', '--pretty=%s'], 20);
        $remote = $this->git(['remote', 'get-url', 'origin'], 20);
        $porcelain = $this->git(['status', '--porcelain'], 20, true);

        if (! $branch['ok'] || ! $commit['ok']) {
            return $this->unavailable(trim($branch['output'].$commit['output']) ?: 'Git tidak dapat membaca repositori.');
        }

        $dirtyFiles = $this->porcelainLines($porcelain['output']);
        $upstream = $this->git(['rev-parse', '--abbrev-ref', '--symbolic-full-name', '@{upstream}'], 20);
        $ahead = null;
        $behind = null;

        if ($upstream['ok']) {
            $counts = $this->git(['rev-list', '--left-right', '--count', 'HEAD...@{upstream}'], 20);
            if ($counts['ok'] && preg_match('/^(\d+)\s+(\d+)$/', trim($counts['output']), $matches) === 1) {
                $ahead = (int) $matches[1];
                $behind = (int) $matches[2];
            }
        }

        return [
            'available' => true,
            'branch' => trim($branch['output']),
            'commit' => trim($commit['output']),
            'subject' => trim($subject['output']),
            'remote' => $this->redact(trim($remote['output'])),
            'dirty' => $dirtyFiles !== [],
            'dirty_files' => array_slice($dirtyFiles, 0, 20),
            'upstream' => $upstream['ok'] ? trim($upstream['output']) : null,
            'ahead' => $ahead,
            'behind' => $behind,
            'message' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function check(): array
    {
        $fetch = $this->git(['fetch', '--prune', 'origin'], 90);

        return [
            'ok' => $fetch['ok'],
            'message' => $fetch['ok'] ? 'Pemeriksaan selesai.' : 'Gagal menghubungi repositori.',
            'steps' => [$fetch],
            'status' => $this->status(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(): array
    {
        $status = $this->status();

        if (! $status['available']) {
            return $this->result(false, $status['message'] ?? 'Pembaruan tidak dapat dijalankan.', []);
        }

        if ($status['upstream'] === null) {
            return $this->result(false, 'Cabang ini belum terhubung ke remote. Atur upstream terlebih dahulu.', []);
        }

        $lock = null;
        try {
            $lock = Cache::lock('cctv-app-update', 900);
            if (! $lock->get()) {
                return $this->result(false, 'Pembaruan lain sedang berjalan.', []);
            }
        } catch (Throwable) {
            $lock = null;
        }

        set_time_limit(0);

        try {
            $steps = [];
            $restore = $this->git(['restore', '--source=HEAD', '--worktree', '--staged', '--', 'public/build'], 60);
            if (! $restore['ok'] && str_contains($restore['output'], 'did not match')) {
                $restore['ok'] = true;
            }
            $steps[] = $restore;

            $pull = $this->git(['pull', '--ff-only', '--autostash'], 180);
            $steps[] = $pull;
            if (! $pull['ok']) {
                return $this->result(false, 'Gagal mengambil kode terbaru.', $steps);
            }

            $composer = [config('cctv.composer_binary'), 'install', '--no-interaction', '--prefer-dist', '--optimize-autoloader'];
            if (app()->environment('production')) {
                $composer[] = '--no-dev';
            }
            $composerStep = $this->run($composer, 600);
            $steps[] = $composerStep;
            if (! $composerStep['ok']) {
                return $this->result(false, 'Gagal memasang dependensi PHP.', $steps);
            }

            $npm = config('cctv.npm_binary');
            $install = is_file(base_path('package-lock.json'))
                ? [$npm, 'ci', '--no-audit', '--no-fund']
                : [$npm, 'install', '--no-audit', '--no-fund'];
            $npmInstall = $this->run($install, 600);
            $steps[] = $npmInstall;
            if (! $npmInstall['ok']) {
                return $this->result(false, 'Gagal memasang dependensi tampilan.', $steps);
            }

            $build = $this->run([$npm, 'run', 'build'], 600);
            $steps[] = $build;
            if (! $build['ok']) {
                return $this->result(false, 'Gagal membangun tampilan.', $steps);
            }

            $php = config('cctv.php_binary');
            $migrate = $this->run([$php, 'artisan', 'migrate', '--force', '--no-interaction'], 180);
            $steps[] = $migrate;
            if (! $migrate['ok']) {
                return $this->result(false, 'Migrasi database gagal.', $steps);
            }

            $link = $this->run([$php, 'artisan', 'storage:link', '--no-interaction'], 60);
            if (! $link['ok'] && str_contains(strtolower($link['output']), 'already exist')) {
                $link['ok'] = true;
            }
            $steps[] = $link;

            $optimize = $this->run([$php, 'artisan', 'optimize', '--no-interaction'], 180);
            $steps[] = $optimize;
            if (! $optimize['ok']) {
                return $this->result(false, 'Kode terpasang, tetapi cache aplikasi gagal diperbarui.', $steps);
            }

            return $this->result(true, 'Aplikasi sudah diperbarui.', $steps);
        } finally {
            $lock?->release();
        }
    }

    /**
     * @param  list<string>  $args
     * @return array{command: string, ok: bool, output: string}
     */
    private function git(array $args, int $timeout, bool $stdoutOnly = false): array
    {
        return $this->run([
            'git',
            '-c',
            'safe.directory='.base_path(),
            '-c',
            'core.filemode=false',
            ...$args,
        ], $timeout, $stdoutOnly);
    }

    /**
     * @return list<string>
     */
    private function porcelainLines(string $output): array
    {
        $lines = [];
        foreach (preg_split("/\r\n|\n|\r/", trim($output)) ?: [] as $line) {
            if (preg_match('/^[ MADRCU?!]{2} /', $line) !== 1) {
                continue;
            }
            $path = trim(substr($line, 3), '"');
            if ($path === 'public/build' || str_starts_with($path, 'public/build/')) {
                continue;
            }
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * @param  list<string>  $command
     * @return array{command: string, ok: bool, output: string}
     */
    private function run(array $command, int $timeout, bool $stdoutOnly = false): array
    {
        $environment = getenv();
        if (! is_array($environment)) {
            $environment = [];
        }
        $environment['GIT_TERMINAL_PROMPT'] = '0';
        $environment['COMPOSER_NO_INTERACTION'] = '1';

        $result = Process::path(base_path())
            ->timeout($timeout)
            ->env($environment)
            ->run($command);

        $output = $stdoutOnly ? $result->output() : $result->output()."\n".$result->errorOutput();
        $output = $this->redact(trim($output));
        if (strlen($output) > 12000) {
            $output = substr($output, -12000);
        }

        return [
            'command' => implode(' ', $command),
            'ok' => $result->successful(),
            'output' => $output,
        ];
    }

    /**
     * @param  list<array{command: string, ok: bool, output: string}>  $steps
     * @return array<string, mixed>
     */
    private function result(bool $ok, string $message, array $steps): array
    {
        return [
            'ok' => $ok,
            'message' => $message,
            'steps' => $steps,
            'status' => $this->status(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function unavailable(string $message): array
    {
        return [
            'available' => false,
            'branch' => null,
            'commit' => null,
            'subject' => null,
            'remote' => null,
            'dirty' => false,
            'dirty_files' => [],
            'upstream' => null,
            'ahead' => null,
            'behind' => null,
            'message' => $message,
        ];
    }

    private function redact(string $output): string
    {
        return (string) preg_replace('#https://[^/\s:]+:[^/\s@]+@#', 'https://***@', $output);
    }
}
