<?php

declare(strict_types=1);

namespace CuyZ\Valinor\Type\Types;

use CuyZ\Valinor\Definition\ClassSignature;
use CuyZ\Valinor\Type\CombiningType;
use CuyZ\Valinor\Type\ObjectType;
use CuyZ\Valinor\Type\Type;

use function is_subclass_of;

final class InterfaceType implements ObjectType
{
    private ClassSignature $signature;

    /**
     * @param class-string $interfaceName
     * @param array<string, Type> $generics
     */
    public function __construct(string $interfaceName, array $generics = [])
    {
        $this->signature = new ClassSignature($interfaceName, $generics);
    }

    public function signature(): ClassSignature
    {
        return $this->signature;
    }

    public function accepts($value): bool
    {
        $name = $this->signature->className();

        return $value instanceof $name;
    }

    public function matches(Type $other): bool
    {
        if ($other instanceof MixedType) {
            return true;
        }

        if ($other instanceof CombiningType) {
            return $other->isMatchedBy($this);
        }

        if (! $other instanceof ObjectType) {
            return false;
        }

        $className = $this->signature->className();
        $otherClassName = $other->signature()->className();

        /** @phpstan-ignore-next-line @see https://github.com/phpstan/phpstan-src/pull/397 */
        return $className === $otherClassName || is_subclass_of($otherClassName, $className);
    }

    public function __toString(): string
    {
        return $this->signature->toString();
    }
}