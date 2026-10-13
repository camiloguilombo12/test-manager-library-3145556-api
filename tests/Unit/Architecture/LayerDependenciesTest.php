<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Reglas de dependencia de la constitución (artículos I y II): se revisan los `use` de cada archivo.
 */
class LayerDependenciesTest extends TestCase
{
    public function test_domain_depends_on_nothing_outside_itself(): void
    {
        $this->assertNoForbiddenImports('app/Domain', ['App\\Application', 'App\\Adapters', 'Illuminate', 'Carbon', 'Symfony']);
    }

    public function test_application_only_knows_the_domain(): void
    {
        $this->assertNoForbiddenImports('app/Application', ['App\\Adapters', 'Illuminate', 'Carbon', 'Symfony']);
    }

    /**
     * @param string[] $forbiddenPrefixes
     */
    private function assertNoForbiddenImports(string $directory, array $forbiddenPrefixes): void
    {
        $violations = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 3).'/'.$directory));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            preg_match_all('/^use\s+([^;\s]+)/m', file_get_contents($file->getPathname()), $matches);

            foreach ($matches[1] as $import) {
                foreach ($forbiddenPrefixes as $prefix) {
                    if (str_starts_with($import, $prefix)) {
                        $violations[] = $file->getFilename().' usa '.$import;
                    }
                }
            }
        }

        $this->assertSame([], $violations);
    }
}
