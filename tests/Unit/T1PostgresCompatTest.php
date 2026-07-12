<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class T1PostgresCompatTest extends TestCase
{
    private function root(string $path): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $path;
    }

    public function testPostgresCompatibilityScriptsExistAndDeclareRequiredFunctions(): void
    {
        $compat = file_get_contents($this->root('scripts/pg/0001_compat_functions.sql'));
        $sync = file_get_contents($this->root('scripts/pg/0002_sync_sequences.sql'));

        $this->assertStringContainsString('CREATE EXTENSION IF NOT EXISTS pgcrypto', $compat);
        $this->assertStringContainsString('sha2(input text, bits integer)', $compat);
        $this->assertStringContainsString('sha2(input bigint, bits integer)', $compat);
        $this->assertStringContainsString('unix_timestamp()', $compat);

        $this->assertStringContainsString('pg_get_serial_sequence', $sync);
        $this->assertStringContainsString('apikamgusv2', $sync);
        $this->assertStringContainsString('proyecto_legacy', $sync);
        $this->assertStringContainsString('attname', $sync);
    }

    public function testCodeDoesNotKeepMySqlOnlyDateFunctionsRequiredByT1Gate(): void
    {
        $files = $this->phpFiles(['app', 'routes']);
        $pattern = '/TIMESTAMPDIFF|UNIX_TIMESTAMP|TIMEDIFF|current_date\(\)|test_refund/i';

        foreach ($files as $file) {
            $this->assertDoesNotMatchRegularExpression($pattern, file_get_contents($file), $file);
        }
    }

    public function testMysqlSqlInventoryDocumentsEveryResolution(): void
    {
        $inventory = file_get_contents($this->root('scripts/pg/mysql-sql-inventory.md'));

        foreach ([
            'SHA2',
            'UNIX_TIMESTAMP',
            'TIMESTAMPDIFF',
            'TIMEDIFF',
            'CURRENT_DATE',
            'MONTH/YEAR/DAY',
            'ADDTIME',
            'TIME_TO_SEC',
            'IF',
            'CONCAT',
            'backticks',
        ] as $needle) {
            $this->assertStringContainsString($needle, $inventory);
        }
    }

    public function testCheckConstrainedLiteralsMatchDumps(): void
    {
        $sources = [
            file_get_contents($this->root('app/Http/Controllers/V2/Customers/ServiceController.php')),
            file_get_contents($this->root('app/Http/Controllers/V2/Drivers/ServicesController.php')),
            file_get_contents($this->root('app/Http/Controllers/V2/Inviteds/ServiceController.php')),
            file_get_contents($this->root('app/Classes/K_MigrateDBV1ToV2Class.php')),
        ];
        $combined = implode("\n", $sources);

        $this->assertStringContainsString("'ACTIVO'", $combined);
        $this->assertStringContainsString("'TERMINADO'", $combined);
        $this->assertStringContainsString("'En curso'", $combined);
        $this->assertStringContainsString("'Terminado'", $combined);
        $this->assertStringContainsString("'NO'", $combined);
        $this->assertStringContainsString("'SIMPLE'", $combined);
        $this->assertStringContainsString("'MUDANZA'", $combined);

        $this->assertStringNotContainsString('$service->estado = "Activo"', $combined);
        $this->assertStringNotContainsString('$driverService->confirmed = "No"', $combined);
        $this->assertStringNotContainsString('$traslado = \'Simple\'', $combined);
        $this->assertStringNotContainsString('$traslado = \'Mudanza\'', $combined);
    }

    public function testLegacyMysqlGuardPrecedesEveryMysqlOldConnection(): void
    {
        $source = file_get_contents($this->root('app/Classes/K_HelpersV1.php'));
        preg_match_all('/function\s+[A-Za-z0-9_]+\s*\([^)]*\)\s*\{.*?(?=\n\s*(?:public|private|protected)\s+function|\n\s*\}\s*$)/s', $source, $matches);

        foreach ($matches[0] as $methodSource) {
            if (!str_contains($methodSource, 'DB::connection("mysql_old")')) {
                continue;
            }

            $guardPos = strpos($methodSource, 'if(!self::ENABLE)');
            $connectionPos = strpos($methodSource, 'DB::connection("mysql_old")');

            $this->assertNotFalse($guardPos, $methodSource);
            $this->assertLessThan($connectionPos, $guardPos, $methodSource);
        }
    }

    private function phpFiles(array $dirs): array
    {
        $files = [];
        foreach ($dirs as $dir) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->root($dir), \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
