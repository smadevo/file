<?php
namespace Smadevo\Cache;

/**
 * Caches to the local filesystem.
 *
 * @inheritDoc
 */
abstract class File implements \Smadevo\Cache
{
    /**
     * @var string
     */
    private $path;

    /**
     * Constructor.
     *
     * @param string $path
     */
    final public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @inheritDoc
     */
    final public function getData(string $group, string $key): ?string
    {
        $base = $this->path
              . '/'
              . md5($group);
        $path = $base
              . '/'
              . md5($key);

        if (!is_file($path)) {
            return null;
        }
        if (!$file = fopen($path, 'r')) {
            return null;
        }
        if (!flock($file, LOCK_SH)) {
            fclose($file);
            return null;
        }
        if (($data = file_get_contents($path)) === false) {
            flock($file, LOCK_UN);
            fclose($file);
            return null;
        }
        flock($file, LOCK_UN);
        fclose($file);
        return $data;
    }

    /**
     * @inheritDoc
     */
    final public function setData(string $data, string $group, string $key): void
    {
        $base = $this->path
              . '/'
              . md5($group);
        $path = $base
              . '/'
              . md5($key);

        if (!is_dir($base)) {
            mkdir($base, 0770, true);
        }
        file_put_contents($path, $data, LOCK_EX);
    }

    /**
     * @inheritDoc
     */
    final public function expire(string $group): void
    {
        $this->delete($this->path . '/' . md5($group));
    }

    /**
     * Deletes a file or directory under a given path.
     *
     * @param string $path
     *
     * @return void
     */
    final private function delete(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) as $file) {
            if ($file === '.') {
                continue;
            }
            if ($file === '..') {
                continue;
            }
            $this->delete("{$path}/{$file}");
        }
        rmdir($path);
    }
}
