<?php
namespace Reacon\Sdk\Model;
use Reacon\Sdk\ObjectSerializer;

/** Typed generated alternatives. Instances serialize as their selected payload. */
abstract class ObjectUnion implements \JsonSerializable
{
    protected const VARIANTS = [];
    private ModelInterface|\stdClass $value;
    public function __construct(ModelInterface|\stdClass $value)
    {
        foreach (static::VARIANTS as $variant) {
            if (($variant['empty'] && $value instanceof \stdClass && !get_object_vars($value)) ||
                (!$variant['empty'] && get_class($value) === $variant['class'] && $value->valid())) {
                $this->value = $value;
                return;
            }
        }
        throw new \InvalidArgumentException('Value must be a valid declared ' . static::class . ' alternative');
    }
    public function actualInstance(): ModelInterface|\stdClass { return $this->value; }
    public function jsonSerialize(): mixed { return ObjectSerializer::sanitizeForSerialization($this->value); }
    public static function fromWire(mixed $data): static
    {
        if (is_string($data)) $data = json_decode($data, false, 512, JSON_THROW_ON_ERROR);
        if (!$data instanceof \stdClass) throw new \InvalidArgumentException('Union input must be a JSON object');
        $fields = get_object_vars($data);
        foreach (static::VARIANTS as $variant) {
            if ($variant['closed'] && array_diff(array_keys($fields), $variant['keys'])) continue;
            if (array_diff($variant['required'], array_keys($fields))) continue;
            foreach ($variant['tags'] as $key => $value) if (($fields[$key] ?? null) !== $value) continue 2;
            if ($variant['empty']) return new static(new \stdClass());
            try {
                $value = ObjectSerializer::deserialize($data, $variant['class']);
                if (!$value->valid()) continue;
                return new static($value);
            } catch (\InvalidArgumentException $error) { /* Try another declared anyOf shape. */ }
        }
        throw new \InvalidArgumentException('Value does not match a declared ' . static::class . ' alternative');
    }
}
