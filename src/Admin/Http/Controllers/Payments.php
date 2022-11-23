<?php

namespace Igniter\Admin\Http\Controllers;

use Exception;
use Igniter\Admin\Classes\PaymentGateways;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Models\Payment;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\System\Helpers\ValidationHelper;
use Illuminate\Support\Arr;

class Payments extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\Payment::class,
            'title' => 'lang:igniter::admin.payments.text_title',
            'emptyMessage' => 'lang:igniter::admin.payments.text_empty',
            'defaultSort' => ['updated_at', 'DESC'],
            'configFile' => 'payment',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.payments.text_form_name',
        'model' => \Igniter\Admin\Models\Payment::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'payments/edit/{code}',
            'redirectClose' => 'payments',
            'redirectNew' => 'payments/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'payments/edit/{code}',
            'redirectClose' => 'payments',
            'redirectNew' => 'payments/create',
        ],
        'delete' => [
            'redirect' => 'payments',
        ],
        'configFile' => 'payment',
    ];

    protected $requiredPermissions = 'Admin.Payments';

    protected $gateway;

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('payments', 'sales');
    }

    public function index()
    {
        Payment::syncAll();

        $this->asExtension('ListController')->index();
    }

    /**
     * Finds a Model record by its primary identifier, used by edit actions. This logic
     * can be changed by overriding it in the controller.
     *
     * @param string $paymentCode
     *
     * @return Model
     * @throws \Exception
     */
    public function formFindModelObject($paymentCode = null)
    {
        if (!strlen($paymentCode)) {
            throw new Exception(lang('igniter::admin.payments.alert_setting_missing_id'));
        }

        $model = $this->formCreateModelObject();

        // Prepare query and find model record
        $query = $model->newQuery();
        $this->fireEvent('admin.controller.extendFormQuery', [$query]);
        $this->formExtendQuery($query);
        $result = $query->whereCode($paymentCode)->first();

        if (!$result)
            throw new Exception(sprintf(lang('igniter::admin.form.not_found'), $paymentCode));

        $result = $this->formExtendModel($result) ?: $result;

        return $result;
    }

    protected function getGateway($code)
    {
        if ($this->gateway !== null) {
            return $this->gateway;
        }

        if (!$gateway = resolve(PaymentGateways::class)->findGateway($code)) {
            throw new Exception(sprintf(lang('igniter::admin.payments.alert_code_not_found'), $code));
        }

        return $this->gateway = $gateway;
    }

    public function formExtendModel($model)
    {
        if (!$model->exists)
            $model->applyGatewayClass();

        return $model;
    }

    public function formExtendFields($form)
    {
        $model = $form->model;
        if ($model->exists) {
            $configFields = $model->getConfigFields();
            $form->addTabFields($configFields);
        }

        if ($form->context != 'create') {
            $field = $form->getField('code');
            $field->disabled = true;
        }
    }

    public function formBeforeCreate($model)
    {
        if (!strlen($code = post('Payment.payment')))
            throw new ApplicationException(lang('igniter::admin.payments.alert_invalid_code'));

        $paymentGateway = resolve(PaymentGateways::class)->findGateway($code);

        $model->class_name = $paymentGateway['class'];
    }

    public function formValidate($model, $form)
    {
        $rules = [
            'payment' => ['sometimes', 'required', 'alpha_dash'],
            'name' => ['required', 'min:2', 'max:128'],
            'code' => ['sometimes', 'required', 'alpha_dash', 'unique:payments,code'],
            'priority' => ['required', 'integer'],
            'description' => ['max:255'],
            'is_default' => ['required', 'integer'],
            'status' => ['required', 'integer'],
        ];

        $messages = [];

        $attributes = [
            'payment' => lang('igniter::admin.payments.label_payments'),
            'name' => lang('igniter::admin.label_name'),
            'code' => lang('igniter::admin.payments.label_code'),
            'priority' => lang('igniter::admin.payments.label_priority'),
            'description' => lang('igniter::admin.label_description'),
            'is_default' => lang('igniter::admin.payments.label_default'),
            'status' => lang('lang:igniter::admin.label_status'),
        ];

        if ($form->model->exists) {
            $parsedRules = ValidationHelper::prepareRules($form->model->getConfigRules());

            if ($mergeRules = Arr::get($parsedRules, 'rules', $parsedRules))
                $rules = array_merge($rules, $mergeRules);

            if ($mergeMessages = $form->model->getConfigValidationMessages())
                $messages = array_merge($messages, $mergeMessages);

            if ($mergeAttributes = Arr::get($parsedRules, 'attributes', $form->model->getConfigValidationAttributes()))
                $attributes = array_merge($attributes, $mergeAttributes);
        }

        return $this->validatePasses($form->getSaveData(), $rules, $messages, $attributes);
    }
}
