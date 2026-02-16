<?php

it('uses character art to render installer intro from app name', function () {
    $rendererPath = app_path('Prompts/Renderers/InstallerNoteRenderer.php');
    $charactersPath = resource_path('art/characters');

    expect(file_exists($rendererPath))->toBeTrue();
    expect(is_dir($charactersPath))->toBeTrue();
    expect(file_exists($charactersPath.'/a.txt'))->toBeTrue();
    expect(file_exists($charactersPath.'/space.txt'))->toBeTrue();

    $renderer = file_get_contents($rendererPath);

    expect($renderer)->toContain("config('app.name'");
    expect($renderer)->toContain("Art::setDirectory(resource_path('art/characters'))");
    expect($renderer)->toContain('Lines::fromColumns');
});
