<?php

namespace App\Adapter\Doctrine\Filter;

use App\Entity\Employee;
use Domain\Shared\Attribute\ContextAware;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class AccessContextFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        $className = $targetEntity->getName();

        /**
         * @var ContextAware|null $contextAware
         *
         * If the entity is not marked with the attribute - skip it.
         */
        if (!($contextAware = $this->getAttributeFromClass($className, ContextAware::class))) {
            return '';
        }

        $orgId = $this->getParameter('org_id'); // organization id

        // fieldName is guaranteed non-empty by the attribute's constructor.
        return $this->getQuery($className, $targetTableAlias, $contextAware->fieldName, $orgId);
    }

    /**
     * @param string $className        Doctrine entity class name
     * @param string $targetTableAlias Alias of the entity table, e.g. d0, t1, or other auto-generated aliases
     * @param string $fieldName        Field that holds knowledge about the related organization/context
     * @param string $orgId            Organization id / context id
     */
    private function getQuery(
        string $className,
        string $targetTableAlias,
        string $fieldName,
        string $orgId
    ): string {
        return match ($className) {
            Employee::class, // contains team_id instead of organization_id
            => sprintf('
                EXISTS (
                    SELECT 1
                    FROM public.team t
                    WHERE t.id = %s.%s
                    AND t.organization_id = %s
                )', $targetTableAlias, $fieldName, $orgId),
            // Any other #[ContextAware] entity (e.g. Document): the marked field maps
            // straight to the org/context id -> simple equality.
            default => sprintf('%s.%s = %s', $targetTableAlias, $fieldName, $orgId),
        };
    }

    private function getAttributeFromClass(string $className, string $attributeClassName): mixed
    {
        $reflectionClass = new \ReflectionClass($className);
        $attributes = $reflectionClass->getAttributes($attributeClassName);
        if (empty($attributes)) {
            return null;
        }

        $firstAttribute = reset($attributes);

        return $firstAttribute->newInstance();
    }
}
