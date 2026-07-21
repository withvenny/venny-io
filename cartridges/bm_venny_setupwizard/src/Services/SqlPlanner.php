<?php

declare(strict_types=1);

final class SqlPlanner
{
    public function __construct(
        private string $schemaPath,
        private string $constraintsPath,
        private string $indexesPath,
    ) {}

    public function buildPlan(array $tables): array
    {
        $tables = array_values(array_unique(array_map('strtolower', $tables)));
        return [
            'tables' => $tables,
            'schema_sql' => $this->schemaSql($tables),
            'constraints_sql' => $this->constraintsSql($tables),
            'indexes_sql' => $this->indexesSql($tables),
        ];
    }

    private function schemaSql(array $tables): string
    {
        $sql = file_get_contents($this->schemaPath) ?: '';
        $out = [];

        if (preg_match_all('/CREATE\s+EXTENSION\s+IF\s+NOT\s+EXISTS\s+[^;]+;/i', $sql, $m)) {
            $out = array_merge($out, $m[0]);
        }
        // The schema may use CITEXT even if the extension line is missing.
        $out[] = 'CREATE EXTENSION IF NOT EXISTS citext;';

        foreach ($tables as $table) {
            $pattern = '/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+' . preg_quote($table, '/') . '\s*\(.*?\);/is';
            if (preg_match($pattern, $sql, $match)) {
                $out[] = "\n-- table: {$table}\n" . trim($match[0]);
            }
        }
        return implode("\n\n", array_unique($out)) . "\n";
    }

    private function constraintsSql(array $tables): string
    {
        $sql = file_get_contents($this->constraintsPath) ?: '';
        $out = [];

        // Include setup DO blocks for installations/steps and the helper function.
        if (preg_match_all('/DO\s+\$\$.*?END\s+\$\$;/is', $sql, $blocks)) {
            foreach ($blocks[0] as $block) {
                if ($this->blockMentionsSelectedTable($block, $tables)) {
                    $out[] = trim($block);
                }
            }
        }
        if (preg_match('/CREATE\s+OR\s+REPLACE\s+FUNCTION\s+venny_add_constraint\s*\(.*?LANGUAGE\s+plpgsql\s*;/is', $sql, $fn)) {
            $out[] = trim($fn[0]);
        }

        // Include helper calls whose child table exists and referenced parent tables are selected.
        if (preg_match_all('/SELECT\s+venny_add_constraint\s*\(\s*\'([^\']+)\'\s*,\s*\'([^\']+)\'\s*,\s*\'(.*?)\'\s*\)\s*;/is', $sql, $calls, PREG_SET_ORDER)) {
            foreach ($calls as $call) {
                $table = strtolower($call[1]);
                $definition = $this->unescapeSqlLiteral($call[3]);
                if (!in_array($table, $tables, true)) {
                    continue;
                }
                $refs = $this->referencedTables($definition);
                $missing = array_diff($refs, $tables);
                if ($missing) {
                    continue;
                }
                $out[] = trim($call[0]);
            }
        }
        return implode("\n\n", array_unique($out)) . "\n";
    }

    private function indexesSql(array $tables): string
    {
        $sql = file_get_contents($this->indexesPath) ?: '';
        $out = [];
        if (preg_match_all('/CREATE\s+(UNIQUE\s+)?INDEX\s+IF\s+NOT\s+EXISTS\s+.*?;/is', $sql, $idxs)) {
            foreach ($idxs[0] as $idx) {
                if (preg_match('/\bON\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/i', $idx, $m)) {
                    if (in_array(strtolower($m[1]), $tables, true)) {
                        $out[] = trim($idx);
                    }
                }
            }
        }
        return implode("\n\n", array_unique($out)) . "\n";
    }

    private function blockMentionsSelectedTable(string $block, array $tables): bool
    {
        foreach ($tables as $table) {
            if (preg_match('/\bALTER\s+TABLE\s+' . preg_quote($table, '/') . '\b/i', $block)) {
                return true;
            }
        }
        return false;
    }

    private function referencedTables(string $definition): array
    {
        preg_match_all('/REFERENCES\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/i', $definition, $m);
        return array_values(array_unique(array_map('strtolower', $m[1] ?? [])));
    }

    private function unescapeSqlLiteral(string $s): string
    {
        return str_replace("''", "'", $s);
    }
}
