<?php

declare(strict_types=1);

namespace FixedWidth;

use FixedWidth\Annotation\Parser;
use SplFileObject;
use Exception;

class Writer
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
     * Open the specified file for writing.
     * @param string $filename
     * @return bool
     */
    public function open(string $filename): bool
    {
        if ($this->file = new SplFileObject($filename, 'w')) {
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
     * Add a record to the file.
     * @param mixed $object
     * @return void
     */
    public function addRecord($object): void
    {
        if (!$this->file) {
            throw new Exception('You must call open($filename) before adding records.');
        }

        $record = '';
        $annotations = Parser::parseProperties($object);
        foreach ($annotations as $annotation) {
            $property = $annotation->name;
            $record .= Parser::format($annotation, $object->$property);
        }

        $classAnnotation = Parser::parseClass($object);
        if ($classAnnotation->length && $classAnnotation->length !== strlen($record)) {
            throw new Exception(sprintf(
                'Record length of %d does not match defined length of %d.',
                strlen($record),
                $classAnnotation->length
            ));
        }

        $this->file->fwrite($record . PHP_EOL);
    }
}
