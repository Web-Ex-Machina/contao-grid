<?php

declare(strict_types=1);

/**
 * GRID for Contao Open Source CMS
 * Copyright (c) 2015-2025 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-grid
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-grid/
 */

namespace WEM\GridBundle\Classes;

class DebugUtil
{
    public static function log($message): void
    {
        if (!\is_string($message)) {
            $message = print_r($message, true);
        }
        file_put_contents('debug.log', $message.\PHP_EOL, \FILE_APPEND);
    }

    public static function logException(\Exception $e): void
    {
        $filename = 'debug_'.date('Y-m-d').'.log';
        file_put_contents($filename, '>>>>>>>>>>>>>>>>>'.\PHP_EOL, \FILE_APPEND);
        file_put_contents($filename, \sprintf('[%s] %s', date('Y-m-d H:i:s'), $e->getMessage()).\PHP_EOL, \FILE_APPEND);
        foreach ($e->getTrace() as $index => $trace) {
            if (!str_contains($trace['file'], 'contao-grid')) {
                continue;
            }
            if (\is_array($trace['args'])) {
                self::cleanArgs($trace['args']);
            }
            file_put_contents($filename, \sprintf('Index : %s', $index).\PHP_EOL, \FILE_APPEND);
            file_put_contents($filename, \sprintf('File : "%s"', $trace['file']).\PHP_EOL, \FILE_APPEND);
            file_put_contents($filename, \sprintf('Line : %s', $trace['line']).\PHP_EOL, \FILE_APPEND);
            file_put_contents($filename, \sprintf('Function : "%s"', $trace['function']).\PHP_EOL, \FILE_APPEND);
            file_put_contents($filename, \sprintf('Args : %s', print_r($trace['args'], true)).\PHP_EOL, \FILE_APPEND);
            file_put_contents($filename, '--------'.\PHP_EOL, \FILE_APPEND);
        }
        file_put_contents($filename, '<<<<<<<<<<<<<<<<<'.\PHP_EOL, \FILE_APPEND);
    }

    public static function cleanArgs(array &$args): void
    {
        $keysToRemove = [];

        foreach ($keysToRemove as $key) {
            if (\array_key_exists($key, $args) && !empty($args[$key])) {
                $args[$key] = '<cut>';
            }
        }

        foreach ($args as $key => $value) {
            if (\is_array($value)) {
                self::cleanArgs($args[$key]);
            }
        }
    }
}
