<?php

namespace App\ValueResolver;

use App\Attribute\QueryParameter;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class QueryParameterValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        $attribute = $argument->getAttributesOfType(QueryParameter::class)[0] ?? null;
        if (!$attribute) {
            return [];
        }

        $name = $attribute->name ?? $argument->getName();
        if (!$request->query->has($name)) {
            if ($argument->isNullable() || $argument->hasDefaultValue()) {
                return [];
            }

            throw new BadRequestException(sprintf('Missing query parameter "%s".', $name));
        }

        $value = $request->query->all()[$name];

        $filter = match ($argument->getType()) {
            'string' => \FILTER_DEFAULT,
            'int' => \FILTER_VALIDATE_INT,
            'float' => \FILTER_VALIDATE_FLOAT,
            'bool' => \FILTER_VALIDATE_BOOL,
            default => throw new \LogicException(sprintf('#[QueryParameter] cannot be used on controller argument "$%s" of type "%s".', $argument->getName(), $argument->getType() ?? 'mixed'))
        };

        $value = filter_var($value, $filter, ['flags' => FILTER_NULL_ON_FAILURE]);

        if ($value === null) {
            throw new BadRequestException(sprintf('Invalid query parameter "%s".', $name));
        }

        return [$value];
    }
}
