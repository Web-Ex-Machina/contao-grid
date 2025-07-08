<?php


namespace WEM\GridBundle\Classes;

class StringUtil{
	public static function log($message): void
    {
        if (!\is_string($message)) {
            $message = print_r($message, true);
        }
        $message = sprintf('[%s] %s',date('Y-m-d H:i:s'),$message);
        file_put_contents('debug.log', $message.\PHP_EOL, \FILE_APPEND);
    }

    public static function logException(\Exception $e): void
    {
        $filename = 'debug_'.date('Y-m-d').'.log';
        file_put_contents($filename, '>>>>>>>>>>>>>>>>> '.date('Y-m-d H:i:s').\PHP_EOL, \FILE_APPEND);
        file_put_contents($filename, \sprintf('[%s] %s', date('Y-m-d H:i:s'), $e->getMessage()).\PHP_EOL, \FILE_APPEND);
        foreach ($e->getTrace() as $index => $trace) {
            if (!str_contains($trace['file'], 'acqpa-certification-platform-bundle')) {
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
        $keysToRemove = [
            'identity_picture',
            'identity_piece',
            'cv',
            'employer_certificate',
            'file',
            'file_en',
            'signature_morning',
            'signature_afternoon',
            'examiner_signature',
            'training_center_signature',
            // 'generated_data',
        ];

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
