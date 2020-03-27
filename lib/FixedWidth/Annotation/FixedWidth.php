<?php

declare(strict_types=1);

namespace FixedWidth\Annotation;

use Doctrine\Common\Annotations\{Annotation, AnnotationException};

/**
 * Annotation that can be applied to objects and properties
 * for reading and writing fixed width files.
 * @Annotation
 */
class FixedWidth
{
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'int';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_DATE = 'date';

    /**
     * The start position of the field within the record
     * @var int
     * @Required
     */
    public $start = 1;

    /**
     * The length of the field within the record
     * @var int
     * @Required
     */
    public $length;

    /**
     * The datatype of the field value
     * @var string
     * @Required
     * @Enum({self::TYPE_STRING, self::TYPE_INTEGER, self::TYPE_DECIMAL, self::TYPE_DATE})
     */
    public $type;

    /**
     * The character to use to pad the value to match $length
     * @var string
     * @Required
     */
    public $padCharacter;

    /**
     * The position of the padding in relation to the value (left or right)
     * @var int
     * @Required
     * @Enum({STR_PAD_RIGHT, STR_PAD_LEFT})
     */
    public $padPosition;

    /**
     * A formatting string for the field value used to format the
     * object property value as a string when writing a file, or to
     * transform the string to an object property value when reading
     * i.e. a date format ('Ymd') or decimal format ('%.2f')
     * @var string
     */
    public $format;

    /**
     * The annotation parser will set the name of the property where the
     * annotation exists on this variable so it is available where the
     * annotation is being used.
     * @var string
     */
    public $name;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        foreach ($values as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }

        $requireFormat = ['date', 'decimal'];
        if (in_array($this->type, $requireFormat) && empty($this->format)) {
            throw new AnnotationException(sprintf(
                'Annotation with type "%s" requires a "format"',
                $this->type
            ));
        }
    }
}
