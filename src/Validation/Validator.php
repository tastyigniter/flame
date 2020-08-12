<?php

namespace Igniter\Flame\Validation;

use Illuminate\Validation\Validator as BaseValidator;

/**
 * October CMS wrapper for the Laravel Validator class.
 *
 * The only difference between this and the BaseValidator is that it resets the email validation rule to use the
 * `filter` method by default.
 */
class Validator extends BaseValidator
{
    use \Igniter\Flame\Validation\Concerns\ValidatesEmail;
}
