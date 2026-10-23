<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * T-19 · Los artículos I, II y III, comprobados.
 *
 * En Python esto lo haría import-linter; en PHP no hay una herramienta
 * equivalente instalada, así que el contrato se escribe aquí: se leen los
 * archivos de cada capa y se mira qué importan. Falla en cuanto alguien
 * introduce una importación prohibida.
 */
final class LayerDependenciesTest extends TestCase
{
    /** Artículo I · El dominio no depende de nada. */
    public function test_el_dominio_no_importa_otras_capas_ni_el_framework(): void
    {
        $offenders = $this->importsMatching('app/Domain', [
            'App\Application',
            'App\Adapters',
            'App\Providers',
            'Illuminate',
            'Laravel',
            'Symfony',
        ]);

        $this->assertSame([], $offenders, "El dominio importa algo que no debe:\n".implode("\n", $offenders));
    }

    /** Artículo II · La aplicación declara puertos y no conoce adaptadores. */
    public function test_la_aplicacion_no_importa_adaptadores_ni_el_framework(): void
    {
        $offenders = $this->importsMatching('app/Application', [
            'App\Adapters',
            'App\Providers',
            'Illuminate',
            'Laravel',
            'Symfony',
        ]);

        $this->assertSame([], $offenders, "La aplicación importa algo que no debe:\n".implode("\n", $offenders));
    }

    /** Artículo III · Fuera del punto de composición no se instancia infraestructura. */
    public function test_solo_el_proveedor_instancia_adaptadores(): void
    {
        $adapters = $this->classNamesIn('app/Adapters');
        $offenders = [];

        foreach ($this->filesIn('app') as $file) {
            $path = $this->relative($file);

            if (str_starts_with($path, 'app/Providers/')) {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());

            foreach ($adapters as $adapter) {
                // Un adaptador puede componer sus propias piezas internas; lo
                // que no puede es aparecer instanciado en dominio o aplicación.
                if (str_starts_with($path, 'app/Adapters/')) {
                    continue;
                }

                if (preg_match('/new\s+'.preg_quote($adapter, '/').'\s*\(/', $contents) === 1) {
                    $offenders[] = $path.' instancia '.$adapter;
                }
            }
        }

        $this->assertSame([], $offenders, "Hay infraestructura instanciada fuera de la composición:\n".implode("\n", $offenders));
    }

    /**
     * @param  list<string>  $forbiddenPrefixes
     * @return list<string>
     */
    private function importsMatching(string $directory, array $forbiddenPrefixes): array
    {
        $offenders = [];

        foreach ($this->filesIn($directory) as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            preg_match_all('/^use\s+([^;]+);/m', $contents, $matches);

            foreach ($matches[1] as $import) {
                foreach ($forbiddenPrefixes as $prefix) {
                    if (str_starts_with(trim($import), $prefix)) {
                        $offenders[] = $this->relative($file).' importa '.trim($import);
                    }
                }
            }
        }

        return $offenders;
    }

    /** @return list<string> */
    private function classNamesIn(string $directory): array
    {
        $names = [];

        foreach ($this->filesIn($directory) as $file) {
            $names[] = $file->getBasename('.php');
        }

        return $names;
    }

    /** @return list<SplFileInfo> */
    private function filesIn(string $directory): array
    {
        $root = dirname(__DIR__, 2).'/'.$directory;
        $files = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = clone $file;
            }
        }

        return $files;
    }

    private function relative(SplFileInfo $file): string
    {
        return str_replace([dirname(__DIR__, 2).DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], ['', '/'], $file->getPathname());
    }
}
