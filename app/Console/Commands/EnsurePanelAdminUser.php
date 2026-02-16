<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class EnsurePanelAdminUser extends Command
{
    protected $signature = 'panel:ensure-admin-user
                            {--shell : Output shell variable assignments}';

    protected $description = 'Create or update the initial panel admin user';

    public function handle(): int
    {
        $name = trim((string) (getenv('PANEL_ADMIN_NAME') ?: 'Panel Admin'));
        $email = trim((string) (getenv('PANEL_ADMIN_EMAIL') ?: getenv('PANEL_EMAIL') ?: ''));
        $providedPassword = (string) (getenv('PANEL_ADMIN_PASSWORD') ?: '');
        $passwordMode = strtolower((string) (getenv('PANEL_ADMIN_PASSWORD_MODE') ?: ''));

        if (! in_array($passwordMode, ['keep', 'regenerate', 'provided'], true)) {
            $passwordMode = $providedPassword !== '' ? 'provided' : 'regenerate';
        }

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('PANEL_ADMIN_EMAIL (or PANEL_EMAIL) must be a valid email address.');

            return self::FAILURE;
        }

        $existingUser = User::query()->where('email', $email)->first();

        if ($passwordMode === 'keep') {
            if (! $existingUser instanceof User) {
                $this->error("Cannot keep existing password because no user was found for {$email}.");

                return self::FAILURE;
            }

            $existingUser->name = $name !== '' ? $name : 'Panel Admin';
            $existingUser->save();

            if ($this->option('shell')) {
                $this->line($this->toShellAssignments([
                    'PANEL_ADMIN_NAME' => $existingUser->name,
                    'PANEL_ADMIN_EMAIL' => $existingUser->email,
                    'PANEL_ADMIN_PASSWORD' => '',
                    'PANEL_ADMIN_PASSWORD_GENERATED' => '0',
                    'PANEL_ADMIN_PASSWORD_REUSED' => '1',
                    'PANEL_ADMIN_PASSWORD_AVAILABLE' => '0',
                    'PANEL_ADMIN_PASSWORD_MODE' => 'keep',
                ]));

                return self::SUCCESS;
            }

            $this->info("Panel admin user is ready: {$existingUser->email}");

            return self::SUCCESS;
        }

        if ($passwordMode === 'provided' && $providedPassword === '') {
            $this->error('PANEL_ADMIN_PASSWORD must be provided when PANEL_ADMIN_PASSWORD_MODE=provided.');

            return self::FAILURE;
        }

        $password = $passwordMode === 'provided' ? $providedPassword : Str::password(24);
        $wasGenerated = $passwordMode === 'regenerate' ? '1' : '0';

        $user = $existingUser instanceof User ? $existingUser : User::query()->firstOrNew(['email' => $email]);
        $user->name = $name !== '' ? $name : 'Panel Admin';
        $user->email = $email;
        $user->password = $password;
        $user->save();

        if ($this->option('shell')) {
            $this->line($this->toShellAssignments([
                'PANEL_ADMIN_NAME' => $user->name,
                'PANEL_ADMIN_EMAIL' => $user->email,
                'PANEL_ADMIN_PASSWORD' => $password,
                'PANEL_ADMIN_PASSWORD_GENERATED' => $wasGenerated,
                'PANEL_ADMIN_PASSWORD_REUSED' => '0',
                'PANEL_ADMIN_PASSWORD_AVAILABLE' => '1',
                'PANEL_ADMIN_PASSWORD_MODE' => $passwordMode,
            ]));

            return self::SUCCESS;
        }

        $this->info("Panel admin user is ready: {$user->email}");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $settings
     */
    protected function toShellAssignments(array $settings): string
    {
        $lines = [];

        foreach ($settings as $key => $value) {
            $lines[] = $key.'='.$this->shellQuote($value);
        }

        return implode(PHP_EOL, $lines);
    }

    protected function shellQuote(string $value): string
    {
        return "'".str_replace("'", "'\"'\"'", $value)."'";
    }
}
