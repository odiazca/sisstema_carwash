<?php
/** Utilidades SQL: ejecutar archivos .sql y generar volcados sin depender de mysqldump */
class Sql {
    /** Ejecuta un archivo .sql (una sentencia termina en línea que acaba con ';') */
    public static function runFile(PDO $pdo, string $file): int {
        $fh = fopen($file, 'r');
        if (!$fh) throw new RuntimeException('No se pudo abrir el archivo SQL.');
        $buf = ''; $n = 0;
        while (($line = fgets($fh)) !== false) {
            $t = trim($line);
            if ($buf === '' && ($t === '' || str_starts_with($t, '--') || str_starts_with($t, '/*'))) continue;
            $buf .= $line;
            if (substr(rtrim($line), -1) === ';') {
                $pdo->exec($buf); $n++; $buf = '';
            }
        }
        fclose($fh);
        if (trim($buf) !== '') { $pdo->exec($buf); $n++; }
        return $n;
    }

    public static function tables(PDO $pdo): array {
        return $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Genera un respaldo completo en $dest. Devuelve nº de tablas. */
    public static function dump(PDO $pdo, string $dest): int {
        $fh = fopen($dest, 'w');
        if (!$fh) throw new RuntimeException('No se pudo escribir el respaldo.');
        fwrite($fh, "-- CarWash Pro - respaldo\n-- Fecha: " . date('Y-m-d H:i:s') . "\n-- Base de datos: " . DB_NAME . "\n\n");
        fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");
        $tables = array_values(array_diff(self::tables($pdo), ['ia_config']));   // la clave API cifrada no viaja en los respaldos
        foreach ($tables as $t) {
            $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1];
            fwrite($fh, "DROP TABLE IF EXISTS `$t`;\n" . str_replace("\n", "\n", $create) . ";\n\n");
        }
        // Las sentencias CREATE son multilínea; terminan con ");" al final de línea => compatibles con runFile.
        foreach ($tables as $t) {
            $st = $pdo->query("SELECT * FROM `$t`", PDO::FETCH_NUM);
            $rows = []; $cols = null;
            $flush = function () use (&$rows, $t, $fh) {
                if (!$rows) return;
                fwrite($fh, "INSERT INTO `$t` VALUES " . implode(",", $rows) . ";\n");
                $rows = [];
            };
            while ($r = $st->fetch(PDO::FETCH_NUM)) {
                $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $r);
                $rows[] = '(' . implode(',', $vals) . ')';
                if (count($rows) >= 100) $flush();
            }
            $flush();
            fwrite($fh, "\n");
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
        return count($tables);
    }
}
