<?php
/**
 * Provides utility methods for manipulating files.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_FileUtility
{
    /**
     * Private constructor to prevent instances from being created.
     *
     * @codeCoverageIgnore
     */
    final private function __construct()
    {
    }

    /**
     * Delete a directory (and all its contents) recursively.
     *
     * @param   string  $path   the path to the directory to remove.
     * @return  bool    true on success, false on failure.
     */
    public static function deleteRecursive($path)
    {
        // nothing to do if path doesn't exist.
        if (!file_exists($path)) {
            return;
        }

        // only delete directories.
        if (!is_dir($path)) {
            throw new InvalidArgumentException(
                "Failed to delete path. Path is not a directory."
            );
        }

        // delete contents of the given directory.
        $entries = new DirectoryIterator($path);
        foreach ($entries as $entry) {

            // skip '.' and '..'
            if ($entry->isDir() && $entry->isDot()) {
                continue;
            }

            // delete file entries, recurse for directories.
            if ($entry->isFile()) {
                @chmod($entry->getPathname(), 0777);
                @unlink($entry->getPathname());
            } else {
                static::deleteRecursive($entry->getPathname());
            }
        }

        // delete the directory itself.
        @chmod($path, 0777);
        return @rmdir($path);
    }

    /**
     * Copy a file or folder recursively.
     *
     * @param   string      $source         the source path to copy.
     * @param   string      $target         the target path to copy to.
     * @throws  InvalidArgumentException    if the source does not exist or if the
     *                                      target's containing folder doesn't exist
     * @throws  Exception                   if the copy fails
     */
    public static function copyRecursive($source, $target)
    {
        // strip trailing slashes on source/target.
        $source = rtrim($source, '/\\');
        $target = rtrim($target, '/\\');

        // verify source exists.
        if (!file_exists($source)) {
            throw new InvalidArgumentException("Cannot copy from non-existent source.");
        }

        // verify parent of target exists.
        if (!file_exists(dirname($target))) {
            throw new InvalidArgumentException("Cannot copy to target with non-existent parent.");
        }

        // deal with copying a single file.
        $error = "Failed to copy from $source to $target";
        if (!is_dir($source)) {
            $result = @copy($source, $target);
            if (!$result) {
                throw new Exception($error);
            }
            return;
        }

        // copying a folder - start by creating target folder
        if (!@mkdir($target, fileperms($source))) {
            throw new Exception($error);
        }

        // recursively copy contents of source
        $sourceList = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $source,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($sourceList as $item) {
            if ($item->isDir()) {
                $result = @mkdir($target . '/' . $sourceList->getSubPathName(), fileperms($item->getPathname()));
            } else {
                $result = @copy($item->getPathname(), $target . '/' . $sourceList->getSubPathName());
            }
            if (!$result) {
                throw new Exception($error);
            }
        }
    }

    /**
     * Calculate the md5sum of a directory (and all its contents) recursively.
     *
     * @param   string  $path       the path to the directory to examine.
     * @param   string  $basePath   the base path for the action
     * @param   array   $exclude    a list of file(s) including relative path to exclude
     */
    public static function md5Recursive($path, $basePath = null, array $exclude = null)
    {
        // only md5sum files in a directory, md5 can be used for individual files
        if (!is_dir($path)) {
            throw new InvalidArgumentException('Provided path is not a valid directory.');
        }

        // if no base path provided, set to the provided path
        $basePath   = ($basePath === null ) ? $path : $basePath;

        $path       = rtrim($path, '/');
        $md5List    = array();

        // calculate md5 for contents of the given directory.
        // @todo: what about sort order?  Will it differ on different systems?
        $entries = new DirectoryIterator($path);
        foreach ($entries as $entry) {
            // skip '.' and '..'
            if ($entry->isDir() && $entry->isDot()) {
                continue;
            }

            // calculate md5 for file entries, recurse for directories.
            if ($entry->isFile()) {
                // strip off base path and remove the leading slash/backslashes
                if (strpos($path, $basePath) === 0) {
                    $relativePath = ltrim(substr($path, strlen($basePath)), '/\\');
                }

                // on Windows platform, replace backslashes with forward slashes
                if (P4_Environment::isWindows()) {
                    $relativePath = str_replace('\\', '/', $relativePath);
                }

                $filename = $entry->getFilename();
                $relativeFile = !empty($relativePath)
                    ? $relativePath . '/' . $filename
                    : $filename;

                // calculate md5 for non-excluded files
                if ($exclude === null || !in_array($relativeFile, $exclude)) {
                    $md5List[] = md5_file($path . '/' . $filename) . '  ' . $relativeFile;
                }
            } else {
                $md5List = array_merge($md5List, static::md5Recursive($entry->getPathname(), $basePath, $exclude));
            }
        }

        // return the list
        return $md5List;
    }

    /**
     * Create the given path and make it writable.
     *
     * @param   string  $path   the path to create and make writable.
     */
    public static function createWritablePath($path)
    {
        if (!is_dir($path)) {
            if (!@mkdir($path, 0755, true)) {
                throw new Exception(
                    basename($path) . " directory does not exist and could not be created ('$path')."
                );
            }
        }
        if (!is_writable($path)) {
            if (!@chmod($path, 0755)) {
                throw new Exception(
                    "Unable to make " . basename($path) . " directory writable ('$path')."
                );
            }
        }
    }

    /**
     * Detect the mime-type of the given file.
     *
     * @param   string      $file           the file to detect the mime type of.
     * @throws  InvalidArgumentException    if the given file does not exist.
     */
    public static function getMimeType($file)
    {
        if (!is_file($file)) {
            throw new InvalidArgumentException(
                "Cannot get mime-type of non-existent file ('$file')."
            );
        }

        return P4Cms_Validate_File_MimeType::getTypeOfFile($file);
    }
}
