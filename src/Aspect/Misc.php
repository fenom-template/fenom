<?php
namespace Aspect;


class Misc {

    /**
     * Create bit-mask from associative array use fully associative array possible keys with bit values
     * @static
     * @param array $values custom assoc array, ["a" => true, "b" => false]
     * @param array $options possible values, ["a" => 0b001, "b" => 0b010, "c" => 0b100]
     * @param int $mask the initial value of the mask
     * @return int result, ( $mask | a ) & ~b
     * @throws \RuntimeException if key from custom assoc doesn't exists into possible values
     */
    public static function makeMask(array $values, array $options, $mask = 0) {
        foreach($values as $value) {
            if(isset($options[$value])) {
                if($options[$value]) {
                    $mask |= $options[$value];
                } else {
                    $mask &= ~$options[$value];
                }
            } else {
                throw new \RuntimeException("Undefined parameter $value");
            }
        }
        return $mask;
    }

    /**
     * Clean directory from files
     *
     * @param string $path
     */
    public static function clean($path) {
        if(is_file($path)) {
            unlink($path);
        } elseif(is_dir($path)) {
            $iterator = iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path,
                \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST));
            foreach($iterator as $file) {
                /* @var \splFileInfo $file*/
                if($file->isFile()) {
                    unlink($file->getRealPath());
                } elseif($file->isDir()) {
                    rmdir($file->getRealPath());
                }
            }
        }
    }

    /**
     * Recursive remove directory
     *
     * @param string $path
     */
    public static function rm($path) {
        self::clean($path);
        if(is_dir($path)) {
            rmdir($path);
        }
    }

    public static function put($path, $content) {
        file_put_contents($path, $content);
    }
}
