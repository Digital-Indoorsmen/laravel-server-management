<?php

namespace App\Prompts\Renderers;

use Chewie\Art;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsArt;
use Chewie\Output\Lines;
use Illuminate\Support\Collection;
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
        $projectName = mb_strtolower((string) config('app.name', 'Laravel Server Manager'));

        if (is_dir(resource_path('art/characters'))) {
            Art::setDirectory(resource_path('art/characters'));

            $messageLines = wordwrap(
                string: $projectName,
                width: max((int) floor($width / 7), 1),
                cut_long_words: true,
            );

            collect(explode(PHP_EOL, $messageLines))
                ->map(fn (string $line): array => mb_str_split($line))
                ->map(function (array $letters): array {
                    return array_map(function (string $letter): array {
                        return match ($letter) {
                            ' ' => array_fill(0, 7, str_repeat(' ', 4)),
                            '.' => $this->glyphLines('period'),
                            ',' => $this->glyphLines('comma'),
                            '?' => $this->glyphLines('question-mark'),
                            '!' => $this->glyphLines('exclamation-point'),
                            "'" => $this->glyphLines('apostrophe'),
                            default => file_exists(resource_path("art/characters/{$letter}.txt"))
                                ? $this->glyphLines($letter)
                                : array_fill(0, 7, str_repeat(' ', 4)),
                        };
                    }, $letters);
                })
                ->flatMap(fn (array $letters): array => Lines::fromColumns($letters)->lines()->all())
                ->pipe(function ($lines) use ($width): void {
                    $this->centerHorizontally($lines->toArray(), $width)
                        ->each(function (string $line): void {
                            $this->line($this->cyan($line));
                        });
                });
        }

        $this->newLine();

        $this->centerHorizontally(explode(PHP_EOL, $note->message), $width)
            ->each(function (string $line): void {
                $this->line($this->bgCyan($this->black(" {$line} ")));
            });
    }

    /**
     * @return array<int, string>
     */
    protected function glyphLines(string $glyph): array
    {
        $lines = $this->artLines($glyph);

        if ($lines instanceof Collection) {
            return $lines->toArray();
        }

        return $lines;
    }
}
