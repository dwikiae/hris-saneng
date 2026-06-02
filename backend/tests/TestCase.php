<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetPostgresSequences();
    }

    private function resetPostgresSequences(): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'pgsql') {
            return;
        }

        if (! app()->environment('testing') || ! str_ends_with($connection->getDatabaseName(), '_test')) {
            return;
        }

        $sequences = $connection->select(
            "select sequence_schema, sequence_name
             from information_schema.sequences
             where sequence_schema = 'public'"
        );

        foreach ($sequences as $sequence) {
            $schema = str_replace('"', '""', (string) $sequence->sequence_schema);
            $name = str_replace('"', '""', (string) $sequence->sequence_name);

            $connection->statement('alter sequence "'.$schema.'"."'.$name.'" restart with 1');
        }
    }
}
