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

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('PANEL_ADMIN_EMAIL (or PANEL_EMAIL) must be a valid email address.');

            return self::FAILURE;
        }

        $password = $providedPassword !== '' ? $providedPassword : Str::password(24);
        $wasGenerated = $providedPassword === '' ? '1' : '0';

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $name !== '' ? $name : 'Panel Admin';
        $user->password = $password;
        $user->save();

        if ($this->option('shell')) {
            $this->line($this->toShellAssignments([
                'PANEL_ADMIN_NAME' => $user->name,
                'PANEL_ADMIN_EMAIL' => $user->email,
                'PANEL_ADMIN_PASSWORD' => $password,
                'PANEL_ADMIN_PASSWORD_GENERATED' => $wasGenerated,
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
