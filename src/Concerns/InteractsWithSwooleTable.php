<?php

namespace SwooleTW\Http\Concerns;

use Illuminate\Contracts\Console\Application as ConsoleApp;
use Swoole\Table;
use SwooleTW\Http\Table\SwooleTable;

/**
 * Trait InteractsWithSwooleTable
 *
 * @property \Illuminate\Contracts\Container\Container $container
 * @property \Illuminate\Contracts\Container\Container $app
 */
trait InteractsWithSwooleTable
{
    /**
     * @var \SwooleTW\Http\Table\SwooleTable
     */
    protected $currentTable;

    /**
     * Register customized swoole talbes.
     */
    protected function createTables()
    {
        $this->currentTable = new SwooleTable;
        $this->registerTables();
    }

    /**
     * Register user-defined swoole tables.
     */
    protected function registerTables()
    {
        $tables = $this->container->make('config')->get('swoole_http.tables', []);

        foreach ($tables as $key => $value) {
            $table = new Table($value['size']);
            $columns = $value['columns'] ?? [];
            foreach ($columns as $column) {
                if (isset($column['size'])) {
                    $table->column($column['name'], $column['type'], $column['size']);
                } else {
                    $table->column($column['name'], $column['type']);
                }
            }
            $table->create();

            $this->currentTable->add($key, $table);
        }
    }

    /**
     * Bind swoole table to Laravel app container.
     */
    protected function bindSwooleTable()
    {
        if (! $this->app instanceof ConsoleApp) {
            $this->app->singleton(SwooleTable::class, function () {
                return $this->currentTable;
            });

            $this->app->alias(SwooleTable::class, 'swoole.table');
        }
    }
}
