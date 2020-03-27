<?php

declare(strict_types=1);

namespace FixedWidth\Annotation;

use FixedWidth\Annotation\FixedWidth;
use Doctrine\Common\Annotations\{AnnotationReader, FileCacheReader};
use ReflectionClass;
use DateTime;
use Exception;

/**
 * Reads FixedWidth class and property annotations and performs
 * data transformations based on those annotations.
 * The FileCacheReader used in these methods caches the annotations
 * so that they don't have to be parsed from the docblocks for every
 * call, which is very expensive and causes reading/writing to stall
 * for larger data sets.
 * @link https://www.doctrine-project.org/projects/doctrine-annotations/en/1.8/annotations.html#setup-and-configuration
 */
class Parser
{
    const CACHE_DIR = '/tmp';

    /**
     * Collect the property level FixedWidth annotations from the specified object.
     * @param mixed $object
     * @return FixedWidth[]|null
     */
    public static function parseProperties($object): ?array
    {
        if (!is_object($object)) {
            return null;
        }

        $annotations = [];
        $reflectionClass = new ReflectionClass($object);
        $reader = new FileCacheReader(new AnnotationReader(), self::CACHE_DIR, false);

        foreach ($reflectionClass->getProperties() as $property) {
            $annotation = self::mergeDefaults(
                $object,
                $reader->getPropertyAnnotation($property, FixedWidth::class)
            );
            $annotation->name = $property->getName();
            $annotations[] = $annotation;
        }

        return $annotations;
    }

    /**
     * Get the class level FixedWidth annotation from the specified object.
     * @param mixed $object
     * @return FixedWidth|null
     */
    public static function parseClass($object): ?FixedWidth
    {
        if (!is_object($object)) {
            return null;
        }

        $reflectionClass = new ReflectionClass($object);
        $reader = new FileCacheReader(new AnnotationReader(), self::CACHE_DIR, false);

        $annotation = $reader->getClassAnnotation($reflectionClass, FixedWidth::class);

        return $annotation;
    }

    /**
     * Format object properties as fixed width strings based on the FixedWidth
     * annotations defined on the property.
     * @param FixedWidth $annotation
     * @param mixed $value
     * @return string
     */
    public static function format(FixedWidth $annotation, $value): string
    {
        switch ($annotation->type) {
            case FixedWidth::TYPE_DATE:
                $value = $value->format($annotation->format);
                break;
            case FixedWidth::TYPE_DECIMAL:
                $value = sprintf($annotation->format, $value);
                break;
        }
        $value = (string) $value;

        if ($annotation->length < strlen($value)) {
            throw new Exception(sprintf(
                'The value "%s" with length %d is too long for field %s with length %d.',
                $value,
                strlen($value),
                $annotation->name,
                $annotation->length
            ));
        }

        return str_pad(
            $value,
            $annotation->length,
            $annotation->padCharacter,
            $annotation->padPosition
        );
    }

    /**
     * Transform strings read from fixed width format into other datatypes based on
     * the FixedWidth annotations defined on the property.
     * @param FixedWidth $annotation
     * @param string $value
     * @return mixed
     */
    public static function transform(FixedWidth $annotation, string $value)
    {
        $value = trim($value);

        switch ($annotation->type) {
            case FixedWidth::TYPE_INTEGER:
                $value = (int) $value;
                break;
            case FixedWidth::TYPE_DATE:
                $value = DateTime::createFromFormat($annotation->format, $value);
                break;
            case FixedWidth::TYPE_DECIMAL:
                $value = (float) $value;
                break;
        }

        return $value;
    }

    /**
     * Merge default annotation values set on the class with the values set on the
     * annotated property. Property annotations take precedence over class annotations.
     * @param mixed $object
     * @param FixedWidth $annotation
     * @return FixedWidth
     */
    private static function mergeDefaults($object, FixedWidth $annotation): FixedWidth
    {
        $default = self::parseClass($object);

        // annotation properties defined by FixedWidth
        $properties = [
            'length',
            'type',
            'padCharacter',
            'padPosition',
            'format'
        ];

        foreach ($properties as $property) {
            $annotation->$property = !is_null($annotation->$property) ? $annotation->$property : $default->$property;
        }
        return $annotation;
    }
}
