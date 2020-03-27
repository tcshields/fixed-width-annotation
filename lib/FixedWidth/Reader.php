<?php

declare(strict_types=1);

namespace FixedWidth;

use FixedWidth\Annotation\Parser;
use SplFileObject;

class Reader
{
    /**
     * @var SplFileObject
     */
    protected $file;

    public function __construct()
    {
        // empty
    }

    /**
     * Open the specified file for reading.
     * @param string $filename
     * @return bool
     */
    public function open(string $filename): bool
    {
        if ($this->file = new SplFileObject($filename, 'r')) {
            return true;
        }
        return false;
    }

    /**
     * Close the file.
     * @return bool
     */
    public function close(): bool
    {
        $this->file = null;
        return true;
    }

    /**
     * Move the file pointer to the next record.
     */
    public function next()
    {
        $this->file->next();
    }

    /**
     * Return the character string of $length from $start position of the current record
     * @param int $start
     * @param int $length
     * @return string|null
     */
    public function getRecordIdentifier(int $start, int $length): ?string
    {
        $str = $this->file->current();
        if ($start - 1 > strlen($str)) {
            return null;
        }
        return substr($str, $start - 1, $length);
    }

    /**
     * Read the current record, transform it into the specified $object,
     * and move the pointer to the next line in the file.
     * @param mixed $object
     * @param bool $movePointer
     * @return mixed $object
     */
    public function getRecord($object, bool $movePointer = true)
    {
        $record = $this->file->current();

        $annotations = Parser::parseProperties($object);
        foreach ($annotations as $annotation) {
            $property = $annotation->name;

            $value = substr(
                $record,
                $annotation->start - 1,
                $annotation->length
            );
            $object->$property = Parser::transform($annotation, $value);
        }

        if ($movePointer) {
            $this->next();
        }

        return $object;
    }
}
