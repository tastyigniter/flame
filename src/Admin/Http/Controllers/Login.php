<?php

namespace Igniter\Admin\Http\Controllers;

use Igniter\Admin\Facades\Admin;
use Igniter\Admin\Facades\AdminAuth;
use Igniter\Admin\Facades\Template;
use Igniter\Admin\Models\User;
use Igniter\Admin\Traits\ControllerUtils;
use Igniter\Admin\Traits\ValidatesForm;
use Igniter\Flame\Exception\ValidationException;
use Igniter\System\Traits\ViewMaker;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;

class Login extends Controller
{
    use ViewMaker;
    use ValidatesForm;
    use ControllerUtils;

    public $bodyClass = 'page-login';

    public function __construct()
    {
        $this->middleware('throttle:'.config('igniter.auth.rateLimiter', '6,1'));

        $this->layout = $this->layout ?: 'default';
        $this->layoutPath[] = 'igniter.admin::_layouts';
    }

    public function index()
    {
        if (AdminAuth::isLogged())
            return Admin::redirect('dashboard');

        if ($handler = Admin::getAjaxHandler()) {
            Admin::validateAjaxHandler($handler);

            return $this->runHandler($handler);
        }

        Template::setTitle(lang('igniter::admin.login.text_title'));

        return $this->makeView('igniter.admin::auth.login');
    }

    public function reset()
    {
        if (AdminAuth::isLogged()) {
            return Admin::redirect('dashboard');
        }

        $code = input('code');
        if (strlen($code) && !User::whereResetCode(input('code'))->first()) {
            flash()->error(lang('igniter::admin.login.alert_failed_reset'));

            return Admin::redirect('login');
        }

        Template::setTitle(lang('igniter::admin.login.text_password_reset_title'));

        $this->vars['resetCode'] = input('code');

        return $this->makeView('auth/reset');
    }

    public function onLogin()
    {
        $data = $this->validate(post(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ], [], [
            'email' => lang('igniter::admin.login.label_email'),
            'password' => lang('igniter::admin.login.label_password'),
        ]);

        if (!AdminAuth::attempt(array_only($data, ['email', 'password']), true))
            throw new ValidationException(['username' => lang('igniter::admin.login.alert_login_failed')]);

        session()->regenerate();

        $redirectUrl = ($redirectUrl = input('redirect'))
            ? Admin::redirect($redirectUrl)
            : Admin::redirectIntended('dashboard');

        return request()->ajax()
            ? ['X_IGNITER_REDIRECT' => $redirectUrl->getTargetUrl()]
            : $redirectUrl;
    }

    public function onRequestResetPassword()
    {
        $data = post();

        $this->validate($data, [
            'email' => ['required', 'email:filter', 'max:96'],
        ], [], [
            'email' => lang('igniter::admin.label_email'),
        ]);

        if ($user = User::whereEmail(post('email'))->first()) {
            if (!$user->resetPassword())
                throw new ValidationException(['email' => lang('igniter::admin.login.alert_failed_reset')]);
            $data = [
                'staff_name' => $user->name,
                'reset_link' => admin_url('login/reset?code='.$user->reset_code),
            ];
            Mail::queueTemplate('igniter.admin::_mail.password_reset_request', $data, $user);
        }

        flash()->success(lang('igniter::admin.login.alert_email_sent'));

        return $this->redirect('login');
    }

    public function onResetPassword()
    {
        $data = post();

        $this->validate($data, [
            'code' => ['required'],
            'password' => ['required', 'min:6', 'max:32', 'same:password_confirm'],
            'password_confirm' => ['required'],
        ], [], [
            'code' => lang('igniter::admin.login.label_reset_code'),
            'password' => lang('igniter::admin.login.label_password'),
            'password_confirm' => lang('igniter::admin.login.label_password_confirm'),
        ]);

        $code = array_get($data, 'code');
        $user = User::whereResetCode($code)->first();

        if (!$user || !$user->completeResetPassword($code, post('password')))
            throw new ValidationException(['password' => lang('igniter::admin.login.alert_failed_reset')]);

        $data = [
            'staff_name' => $user->name,
        ];

        Mail::queueTemplate('igniter.admin::_mail.password_reset', $data, $user);

        flash()->success(lang('igniter::admin.login.alert_success_reset'));

        return $this->redirect('login');
    }
}
