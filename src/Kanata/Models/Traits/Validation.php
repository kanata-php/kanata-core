<?php

namespace Kanata\Models\Traits;

use Exception;
use Symfony\Component\Validator\Validation as SymfonyValidation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait Validation
{
    protected ?ValidatorInterface $validator = null;

    /**
     * @param $value
     * @param array $rules
     * @return void
     * @throws Exception
     */
    public function validateField($value, array $rules): void
    {
        if (null === $this->validator) {
            $this->validator = SymfonyValidation::createValidator();
        }

        $violations = $this->validator->validate($value, $rules);

        if (0 !== count($violations)) {
            $message = [];
            foreach ($violations as $violation) {
                $message[] = $violation->getMessage();
            }
            throw new Exception(implode('|', $message));
        }
    }

    /**
     * @param array $validationData Format: ['field_key' => ['value' => value, 'rules' => []]]
     * @return array Validation errors.
     */
    public function validateFields(array $validationData): array
    {
        $errors = [];

        foreach ($validationData as $key => $value) {
            try {
                $this->validateField($value['value'], $value['rules']);
            } catch (Exception $e) {
                $errors[$key] = $e->getMessage();
            }
        }

        return $errors;
    }
}