<?php

namespace App\ValueResolver\Api;

use App\Dto\InputDtoInterface;
use App\Exception\ApiHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DtoValueResolver implements ValueResolverInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();
        if (
            !$argumentType
            || !is_subclass_of($argumentType, InputDtoInterface::class, true)
        ) {
            return [];
        }

        $value = $request->getContent();
        $decoded_value = json_decode($value, true);
        if (!is_string($value) || !$decoded_value) {
            throw new ApiHttpException(Response::HTTP_BAD_REQUEST);
        }

        // Check if value is present in a nested array with key 'form_data'.
        if (array_key_exists('form_data', $decoded_value)) {
            $context = [UnwrappingDenormalizer::UNWRAP_PATH => '[form_data]'];
        }

        try {
            $commentInputDto = $this->serializer->deserialize(
                $value,
                $argumentType,
                'json',
                $context ?? [],
            );
        } catch (\Exception $exception) {
            throw new ApiHttpException(Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->validator->validate($commentInputDto);
        if ($errors->count() !== 0) {
            $validationErrors = [];
            foreach ($errors as $error) {
                $validationErrors[$error->getPropertyPath()][] = $error->getMessage();
            }
            throw new ApiHttpException(statusCode: Response::HTTP_BAD_REQUEST, violations: $validationErrors);
        }

        return [$commentInputDto];
    }
}
