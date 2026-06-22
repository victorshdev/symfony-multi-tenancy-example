<?php

namespace Domain\Shared\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ContextAware
{
    public function __construct(
        // field name to use in the join
        public readonly string $fieldName, // <- the trick field
    ) {
        // Marked as context-aware but with no field to scope on = misconfiguration.
        // Fail loud and early instead of silently leaking every row later.
        if ('' === trim($this->fieldName)) {
            throw new \InvalidArgumentException('ContextAware requires a non-empty fieldName.');
        }
    }
}
