<?php

namespace App\Prompts\Renderers;

use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsArt;
use Laravel\Prompts\Note;
use Laravel\Prompts\Themes\Default\Renderer;

class InstallerNoteRenderer extends Renderer
{
    use Aligns;
    use DrawsArt;

    public function __invoke(Note $note): string
    {
        if ($note->type === 'intro') {
            $this->renderIntro($note);

            return $this;
        }

        $lines = explode(PHP_EOL, $note->message);

        foreach ($lines as $line) {
            $this->line(match ($note->type) {
                'warning' => $this->yellow(" {$line}"),
                'error' => $this->red(" {$line}"),
                'info' => $this->green(" {$line}"),
                'outro' => $this->cyan(" {$line}"),
                default => " {$line}",
            });
        }

        return $this;
    }

    protected function renderIntro(Note $note): void
    {
        $width = $note->terminal()->cols();

        if (file_exists(resource_path('art/panel-installer.txt'))) {
            $this->centerHorizontally($this->artLines('panel-installer'), $width)
                ->each(function (string $line): void {
                    $this->line($this->cyan($line));
                });
        }

        $this->newLine();

        $this->centerHorizontally(explode(PHP_EOL, $note->message), $width)
            ->each(function (string $line): void {
                $this->line($this->bgCyan($this->black(" {$line} ")));
            });
    }
}
